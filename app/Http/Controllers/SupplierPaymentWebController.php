<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\SalesPayment;
use App\Models\Supplier;
use App\Services\Purchasing\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SupplierPaymentWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly SupplierPaymentService $payments,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = Payment::with(['supplier', 'creator', 'purchaseInvoices', 'journalEntry'])
            ->where('type', 'supplier')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            if ($q !== '') {
                $query->where(function ($qb) use ($q) {
                    $qb->where('reference', 'like', '%'.$q.'%')
                        ->orWhere('notes', 'like', '%'.$q.'%')
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$q.'%')
                            ->orWhere('code', 'like', '%'.$q.'%'));
                });
            }
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "المرجع,المورد,التاريخ,المبلغ,طريقة الدفع\n";
            foreach ($rows as $p) {
                $method = SalesPayment::paymentMethodLabels()[$p->payment_method ?? ''] ?? ($p->payment_method ?? '');
                $csv .= '"'.str_replace('"', '""', $p->reference ?? 'PMT-'.$p->id).'","'
                    .str_replace('"', '""', $p->supplier?->getLocalizedDisplayName() ?? '').'","'
                    .($p->date?->format('Y-m-d') ?? '').'",'.(float) $p->amount.',"'.$method."\"\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="supplier-payments-'.date('Y-m-d').'.csv"',
            ]);
        }

        $payments = $query->paginate(20)->withQueryString();

        $totalPayments = (int) Payment::where('type', 'supplier')->count();
        $totalAmount = (float) Payment::where('type', 'supplier')->sum('amount');
        $thisMonthAmount = (float) Payment::where('type', 'supplier')
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');

        $suppliers = Supplier::query()
            ->where(function ($sub) {
                $sub->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $paymentMethods = SalesPayment::paymentMethodLabels();

        return view('purchases.payments.index', compact(
            'payments',
            'totalPayments',
            'totalAmount',
            'thisMonthAmount',
            'suppliers',
            'paymentMethods',
        ));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = SalesPayment::paymentMethodLabels();

        return view('purchases.payments.create', compact('suppliers', 'paymentMethods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $tenantUserId)],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,card'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'purchase_invoice_id' => ['nullable', Rule::exists('purchase_invoices', 'id')->where('user_id', $tenantUserId)],
        ]);

        $supplier = Supplier::query()->findOrFail((int) $data['supplier_id']);

        try {
            $this->payments->record($tenantUserId, $supplier, [
                'amount' => (float) $data['amount'],
                'date' => $data['date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'purchase_invoice_id' => ! empty($data['purchase_invoice_id']) ? (int) $data['purchase_invoice_id'] : null,
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.payments.index')
            ->with('success', 'تم تسجيل سند الصرف وإنشاء القيد المحاسبي بنجاح.');
    }

    /**
     * فواتير المورد غير المسددة (للقائمة المنسدلة عند إنشاء دفعة).
     */
    public function supplierOutstanding(Request $request): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $supplierId = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $tenantUserId)],
        ])['supplier_id'];

        $invoices = PurchaseInvoice::query()
            ->where('supplier_id', $supplierId)
            ->where('status', '!=', PurchaseInvoice::STATUS_DRAFT)
            ->whereRaw('(total - COALESCE(paid_amount, 0)) > 0.0001')
            ->orderBy('date')
            ->get(['id', 'reference', 'date', 'total', 'paid_amount']);

        $outstanding = 0.0;
        $list = [];

        foreach ($invoices as $inv) {
            $balance = max(0, (float) $inv->total - (float) ($inv->paid_amount ?? 0));
            $outstanding += $balance;
            $list[] = [
                'id' => $inv->id,
                'reference' => $inv->reference ?: 'PINV-'.$inv->id,
                'date' => $inv->date?->format('Y-m-d'),
                'total' => (float) $inv->total,
                'paid_amount' => (float) ($inv->paid_amount ?? 0),
                'balance' => round($balance, 4),
            ];
        }

        return response()->json([
            'outstanding_balance' => round($outstanding, 4),
            'invoices' => $list,
        ]);
    }
}
