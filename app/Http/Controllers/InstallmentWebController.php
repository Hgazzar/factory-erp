<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstallmentWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = Installment::with(['salesInvoice.customer'])
            ->orderBy('due_date')
            ->orderBy('sales_invoice_id');

        if ($request->boolean('overdue_only')) {
            $query->where('due_date', '<', now()->startOfDay())
                ->whereColumn('paid_amount', '<', 'amount');
        }
        if ($request->filled('status') && $request->status !== '') {
            $status = $request->status;
            if ($status === 'متأخر') {
                $query->where('due_date', '<', now()->startOfDay())
                    ->whereColumn('paid_amount', '<', 'amount');
            } elseif ($status === 'مدفوع') {
                $query->whereColumn('paid_amount', '>=', 'amount');
            } elseif ($status === 'مستحق هذا الأسبوع') {
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                $query->whereBetween('due_date', [$start, $end])
                    ->whereColumn('paid_amount', '<', 'amount');
            } else {
                $query->where('due_date', '>=', now()->startOfDay())
                    ->whereColumn('paid_amount', '<', 'amount');
            }
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('salesInvoice', fn ($i) => $i->where('id', 'like', "%{$q}%"))
                    ->orWhereHas('salesInvoice.customer', fn ($c) => $c->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%"));
            });
        }

        $installments = $query->paginate(20)->withQueryString();

        $allInstallments = Installment::with('salesInvoice')->get();
        $totalDue = 0;
        $totalOverdue = 0;
        $dueThisWeek = 0;
        $totalPaid = 0;
        $overdueCount = 0;
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        foreach ($allInstallments as $inst) {
            $balance = (float) $inst->amount - (float) $inst->paid_amount;
            if ($balance <= 0) {
                $totalPaid += (float) $inst->paid_amount;
                continue;
            }
            $totalDue += $balance;
            if ($inst->due_date->isPast()) {
                $totalOverdue += $balance;
                $overdueCount++;
            } elseif ($inst->due_date->between($startOfWeek, $endOfWeek)) {
                $dueThisWeek += $balance;
            }
        }

        $totalPaidSum = (float) Installment::sum('paid_amount');

        $statuses = [
            '' => 'الكل',
            'متأخر' => 'متأخر',
            'مدفوع' => 'مدفوع',
            'مستحق هذا الأسبوع' => 'مستحق هذا الأسبوع',
        ];

        return view('sales.installments.index', [
            'installments' => $installments,
            'totalDue' => $totalDue,
            'totalOverdue' => $totalOverdue,
            'dueThisWeek' => $dueThisWeek,
            'totalPaid' => $totalPaidSum,
            'overdueCount' => $overdueCount,
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.installments.create', [
            'customers' => $customers,
        ]);
    }

    /**
     * فواتير العميل ذات الرصيد والبدون خطة أقساط (لنموذج إنشاء الأقساط).
     */
    public function invoicesForCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('customer_id');
        if (!$customerId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->whereColumn('paid_amount', '<', 'total')
            ->whereDoesntHave('installments')
            ->orderByDesc('date')
            ->get(['id', 'date', 'reference', 'total', 'paid_amount'])
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'label' => 'SINV-' . $inv->id . ' (' . $inv->date->format('Y-m-d') . ') - رصيد: ' . number_format((float) $inv->total - (float) $inv->paid_amount, 2) . ' SAR',
                'balance' => (float) $inv->total - (float) $inv->paid_amount,
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.due_date' => ['required', 'date'],
            'rows.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $invoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
        $balance = (float) $invoice->total - (float) $invoice->paid_amount;
        $rowsSum = collect($data['rows'])->sum(fn ($r) => (float) $r['amount']);

        if (abs($rowsSum - $balance) > 0.01) {
            return back()->withInput()->with('error', 'مجموع مبالغ الأقساط يجب أن يساوي الرصيد المستحق للفاتورة (SAR ' . number_format($balance, 2) . ').');
        }

        if ($invoice->installments()->exists()) {
            return back()->withInput()->with('error', 'هذه الفاتورة لديها خطة أقساط مسبقاً.');
        }

        DB::transaction(function () use ($data) {
            $number = 1;
            foreach ($data['rows'] as $row) {
                Installment::create([
                    'sales_invoice_id' => $data['sales_invoice_id'],
                    'installment_number' => $number++,
                    'due_date' => $row['due_date'],
                    'amount' => (float) $row['amount'],
                    'paid_amount' => 0,
                ]);
            }
        });

        return redirect()
            ->route('sales.installments.index')
            ->with('success', 'تم إنشاء خطة الأقساط بنجاح.');
    }

    /**
     * إرسال تذكير (نموذج مبدئي: رسالة نجاح فقط).
     */
    public function sendReminder(Installment $installment): JsonResponse
    {
        $balance = (float) $installment->amount - (float) $installment->paid_amount;
        if ($balance <= 0) {
            return response()->json(['success' => false, 'message' => 'القسط مدفوع بالكامل.']);
        }
        if ($installment->due_date->isFuture()) {
            return response()->json(['success' => false, 'message' => 'القسط لم يحن موعد استحقاقه بعد.']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال التذكير بنجاح.',
        ]);
    }
}
