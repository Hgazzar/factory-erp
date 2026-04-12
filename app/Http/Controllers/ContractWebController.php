<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = Contract::with(['customer'])->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('contract_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('name_ar', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $contracts = $query->paginate(20)->withQueryString();

        $all = Contract::where('status', 'active')->get();
        $totalContracts = Contract::count();
        $activeCount = (int) $all->count();
        $mrr = $all->sum(function ($c) {
            if ($c->billing_cycle === 'monthly') {
                return (float) $c->total;
            }
            if ($c->billing_cycle === 'quarterly') {
                return (float) $c->total / 3;
            }
            if ($c->billing_cycle === 'yearly') {
                return (float) $c->total / 12;
            }
            return 0;
        });
        $arr = (float) $all->sum('total');
        $renewalsThisMonth = Contract::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereMonth('end_date', now()->month)
            ->whereYear('end_date', now()->year)
            ->count();

        $statuses = [
            '' => 'الكل',
            'active' => 'نشط',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
        ];
        $types = [
            '' => 'الكل',
            'service' => 'خدمة',
            'product' => 'منتج',
        ];

        return view('sales.contracts.index', [
            'contracts' => $contracts,
            'totalContracts' => $totalContracts,
            'activeCount' => $activeCount,
            'mrr' => $mrr,
            'arr' => $arr,
            'renewalsThisMonth' => $renewalsThisMonth,
            'statuses' => $statuses,
            'types' => $types,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $items = Item::where('is_active', true)->orderBy('name_ar')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get();

        return view('sales.contracts.create', [
            'customers' => $customers,
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:service,product'],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reminder_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_renew' => ['nullable', 'boolean'],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $subtotal = 0;
        $taxAmount = 0;
        $linesData = [];
        foreach ($data['lines'] as $i => $line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $lineTax = (float) ($line['tax_percent'] ?? $taxPercent);
            $lineTotal = round($qty * $price * (1 + $lineTax / 100), 4);
            $subtotal += round($qty * $price, 4);
            $taxAmount += $lineTotal - ($qty * $price);
            $linesData[] = [
                'item_id' => $line['item_id'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'tax_percent' => $lineTax,
                'line_total' => $lineTotal,
                'sort_order' => $i,
            ];
        }
        $total = round($subtotal + $taxAmount, 4);
        $nextInvoiceDate = $data['start_date'];

        DB::transaction(function () use ($data, $taxPercent, $subtotal, $taxAmount, $total, $nextInvoiceDate, $linesData, $uid) {
            $contract = Contract::create([
                'user_id' => $uid,
                'contract_number' => Contract::generateContractNumber(),
                'name' => $data['name'],
                'name_ar' => $data['name_ar'] ?? null,
                'type' => $data['type'],
                'customer_id' => $data['customer_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'billing_cycle' => $data['billing_cycle'],
                'currency' => $data['currency'] ?? 'SAR',
                'tax_percent' => $taxPercent,
                'reminder_days' => (int) ($data['reminder_days'] ?? 3),
                'auto_renew' => !empty($data['auto_renew']),
                'next_invoice_date' => $nextInvoiceDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => 'active',
                'warehouse_id' => $data['warehouse_id'] ?? null,
            ]);
            foreach ($linesData as $line) {
                $contract->items()->create($line);
            }
        });

        return redirect()
            ->route('sales.contracts.index')
            ->with('success', 'تم إنشاء العقد بنجاح.');
    }
}
