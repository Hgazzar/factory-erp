<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Services\Sales\SalesReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SalesPaymentWebController extends Controller
{
    public function __construct(
        private readonly SalesReceiptService $receiptService,
    ) {}
    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $query = SalesPayment::with(['customer', 'creator'])
            ->withSum('allocations', 'amount_allocated')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'allocated') {
                $query->whereHas('allocations', fn ($q) => $q->where('amount_allocated', '>', 0));
            } elseif ($status === 'unallocated') {
                $query->whereDoesntHave('allocations');
            }
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "المرجع,العميل,التاريخ,المبلغ,طريقة الدفع\n";
            foreach ($rows as $p) {
                $csv .= '"' . str_replace('"', '""', $p->reference ?? '') . '","' . str_replace('"', '""', $p->customer?->name ?? '') . '","' . ($p->date?->format('Y-m-d') ?? '') . '",' . (float) $p->amount . ',"' . ($p->payment_method ?? '') . "\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sales-payments-' . date('Y-m-d') . '.csv"',
            ]);
        }

        $payments = $query->paginate(20)->withQueryString();

        $totalPayments = SalesPayment::count();
        $totalAmount = (float) SalesPayment::sum('amount');
        $allocatedAmount = (float) \App\Models\SalesPaymentInvoice::sum('amount_allocated');
        $unallocatedAmount = $totalAmount - $allocatedAmount;

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = SalesPayment::paymentMethodLabels();
        $statuses = [
            '' => 'جميع الحالات',
            'allocated' => 'مخصص',
            'unallocated' => 'غير مخصص',
        ];

        return view('sales.payments.index', [
            'payments' => $payments,
            'totalPayments' => $totalPayments,
            'totalAmount' => $totalAmount,
            'allocatedAmount' => $allocatedAmount,
            'unallocatedAmount' => $unallocatedAmount,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = SalesPayment::paymentMethodLabels();

        return view('sales.payments.create', [
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,card'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', Rule::exists('sales_invoices', 'id')->where('user_id', $uid)],
            'allocations.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $amount = (float) $data['amount'];
        $allocations = collect($data['allocations'] ?? [])
            ->filter(fn ($a) => (float) ($a['amount'] ?? 0) > 0)
            ->map(fn ($a) => [
                'invoice_id' => (int) $a['invoice_id'],
                'amount' => (float) $a['amount'],
            ])
            ->values();

        $allocatedTotal = $allocations->sum('amount');
        if ($allocatedTotal > $amount) {
            return back()->withInput()->with('error', 'مجموع المبالغ المخصصة لا يمكن أن يتجاوز مبلغ الدفعة.');
        }

        $customer = Customer::query()->whereKey($data['customer_id'])->firstOrFail();

        try {
            $this->receiptService->recordWithAllocations($uid, $customer, [
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'allocations' => $allocations->all(),
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.payments.index')
            ->with('success', 'تم حفظ الدفعة وتحديث رصيد الفواتير بنجاح.');
    }

    public function customerOutstanding(Request $request): JsonResponse
    {
        $uid = (int) auth()->id();
        $customerId = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
        ])['customer_id'];

        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->whereColumn('paid_amount', '<', 'total')
            ->orderBy('date')
            ->get(['id', 'date', 'reference', 'total', 'paid_amount']);

        $outstanding = 0;
        $list = [];
        foreach ($invoices as $inv) {
            $total = (float) $inv->total;
            $paid = (float) $inv->paid_amount;
            $balance = $total - $paid;
            $outstanding += $balance;
            $list[] = [
                'id' => $inv->id,
                'invoice_number' => 'SINV-' . $inv->id,
                'date' => $inv->date?->format('Y-m-d'),
                'reference' => $inv->reference,
                'total' => $total,
                'paid_amount' => $paid,
                'balance' => round($balance, 4),
            ];
        }

        return response()->json([
            'outstanding_balance' => round($outstanding, 4),
            'invoices' => $list,
        ]);
    }
}
