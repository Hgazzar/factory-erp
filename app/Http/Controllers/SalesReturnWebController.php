<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\DefaultLedgerAccounts;
use Illuminate\View\View;

class SalesReturnWebController extends Controller
{
    public function index(Request $request): View|Response
    {
        $query = SalesReturn::with(['customer', 'salesInvoice'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "المرجع,العميل,التاريخ,الإجمالي,الحالة\n";
            foreach ($rows as $r) {
                $csv .= '"' . str_replace('"', '""', $r->reference ?? '') . '","' . str_replace('"', '""', $r->customer?->name ?? '') . '","' . ($r->date?->format('Y-m-d') ?? '') . '",' . (float) ($r->total ?? 0) . ',"' . ($r->status ?? '') . "\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sales-returns-' . date('Y-m-d') . '.csv"',
            ]);
        }

        $returns = $query->paginate(20)->withQueryString();

        $reasonTypes = ['تالف', 'خطأ في الشحن', 'عدم الرضا', 'آخر'];

        return view('sales.returns.index', [
            'returns' => $returns,
            'reasonTypes' => $reasonTypes,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $reasonTypes = ['تالف', 'خطأ في الشحن', 'عدم الرضا', 'آخر'];

        return view('sales.returns.create', [
            'customers' => $customers,
            'reasonTypes' => $reasonTypes,
        ]);
    }

    /**
     * فواتير العميل (للتعبئة الذكية في نموذج المرتجع).
     */
    public function invoicesByCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('customer_id');
        if (!$customerId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->orderByDesc('date')
            ->get(['id', 'date', 'reference', 'total'])
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'label' => 'SINV-' . $inv->id . ($inv->reference ? ' - ' . $inv->reference : '') . ' (' . $inv->date->format('Y-m-d') . ')',
                'date' => $inv->date->format('Y-m-d'),
                'total' => (float) $inv->total,
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    /**
     * بنود الفاتورة مع أقصى كمية قابلة للإرجاع لكل صنف.
     */
    public function invoiceItems(SalesInvoice $invoice): JsonResponse
    {
        $invoice->load(['items.item']);

        $items = $invoice->items->map(function ($line) use ($invoice) {
            $returnedQty = SalesReturnItem::whereHas('salesReturn', fn ($q) => $q->where('sales_invoice_id', $invoice->id))
                ->where('item_id', $line->item_id)
                ->sum('quantity');
            $maxReturnable = max(0, (float) $line->quantity - (float) $returnedQty);

            return [
                'item_id' => $line->item_id,
                'item_name' => $line->item->name_ar ?? $line->item->code ?? '-',
                'invoice_quantity' => (float) $line->quantity,
                'returned_quantity' => (float) $returnedQty,
                'max_returnable' => $maxReturnable,
                'unit_price' => (float) $line->unit_price,
                'tax_percent' => (float) ($invoice->vat_rate ?? 0),
            ];
        })->filter(fn ($r) => $r['max_returnable'] > 0)->values();

        return response()->json([
            'items' => $items,
            'warehouse_id' => $invoice->warehouse_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'sales_invoice_id' => ['required', Rule::exists('sales_invoices', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'reason_type' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.line_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice = SalesInvoice::with('items')->findOrFail($data['sales_invoice_id']);
        if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
            return back()->withInput()->with('error', 'الفاتورة المختارة لا تخص هذا العميل.');
        }

        $warehouseId = $invoice->warehouse_id;

        // التحقق: الكمية المرتجعة لا تتجاوز المتبقي من كل صنف
        foreach ($data['lines'] as $line) {
            $invoiceLine = $invoice->items->firstWhere('item_id', $line['item_id']);
            if (!$invoiceLine) {
                return back()->withInput()->with('error', 'أحد الأصناف غير موجود في الفاتورة الأصلية.');
            }
            $returnedSoFar = SalesReturnItem::whereHas('salesReturn', fn ($q) => $q->where('sales_invoice_id', $invoice->id))
                ->where('item_id', $line['item_id'])
                ->sum('quantity');
            $maxReturnable = max(0, (float) $invoiceLine->quantity - (float) $returnedSoFar);
            if ((float) $line['quantity'] > $maxReturnable) {
                return back()->withInput()->with('error', 'الكمية المرتجعة تتجاوز الكمية المسموح بإرجاعها للصنف في الفاتورة الأصلية.');
            }
        }

        $lines = collect($data['lines'])->map(function ($line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $tax = (float) ($line['tax_percent'] ?? 0);
            $lineTotal = $qty * $price * (1 + $tax / 100);

            return [
                'item_id' => (int) $line['item_id'],
                'quantity' => $qty,
                'unit_price' => $price,
                'tax_percent' => $tax,
                'line_total' => round($lineTotal, 4),
                'line_reason' => $line['line_reason'] ?? null,
            ];
        })->filter(fn ($l) => $l['quantity'] > 0)->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة بند واحد على الأقل بكمية صحيحة.');
        }

        $total = $lines->sum('line_total');
        $subtotal = $lines->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
        $vatAmount = $total - $subtotal;

        DB::transaction(function () use ($data, $lines, $total, $vatAmount, $warehouseId, $uid) {
            $reference = $data['reference'] ?? 'SR-' . (SalesReturn::max('id') + 1);

            $return = SalesReturn::create([
                'user_id' => $uid,
                'reference' => $reference,
                'date' => $data['date'],
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'],
                'warehouse_id' => $warehouseId,
                'reason_type' => $data['reason_type'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => $total,
                'vat_amount' => $vatAmount,
                'status' => 'معتمد',
            ]);

            foreach ($lines as $line) {
                $return->items()->create($line);

                $pivot = ItemWarehouse::firstOrCreate(
                    ['user_id' => $uid, 'item_id' => $line['item_id'], 'warehouse_id' => $warehouseId],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $pivot->increment('quantity', $line['quantity']);
            }

            // إشعار دائن (Credit Note): تقليل مديونية العميل — أكواد رباعية
            $customersAccount = DefaultLedgerAccounts::accountsReceivable();
            $salesReturnsAccount = DefaultLedgerAccounts::salesReturns();
            $vatAccount = DefaultLedgerAccounts::vatPayable();

            $entry = JournalEntry::create([
                'user_id' => $uid,
                'date' => $data['date'],
                'reference' => 'SR-' . $return->id,
                'description' => 'مرتجع مبيعات - إشعار دائن للعميل #' . $data['customer_id'],
                'total' => $total,
            ]);

            // دائن: العملاء (تقليل المدين)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $customersAccount->id,
                'description' => 'إشعار دائن - مرتجع مبيعات SR-' . $return->id,
                'debit' => 0,
                'credit' => $total,
            ]);

            $netTotal = $total - $vatAmount;
            // مدين: مرتجعات المبيعات (تقليل الإيراد)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $salesReturnsAccount->id,
                'description' => 'مرتجع مبيعات',
                'debit' => $netTotal,
                'credit' => 0,
            ]);

            if ($vatAmount > 0) {
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $vatAccount->id,
                    'description' => 'ضريبة مرتجع مبيعات',
                    'debit' => $vatAmount,
                    'credit' => 0,
                ]);
            }

            $return->journal_entry_id = $entry->id;
            $return->save();
        });

        return redirect()
            ->route('sales.returns.index')
            ->with('success', 'تم حفظ المرتجع وتحديث المخزون وإصدار إشعار الدائن بنجاح.');
    }
}
