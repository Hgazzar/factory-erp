<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = Commission::with(['user', 'salesInvoice.customer'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $commissions = $query->paginate(20)->withQueryString();

        $all = Commission::all();
        $totalCalculated = (float) $all->sum('commission_amount');
        $totalPendingApproval = (float) $all->where('status', 'pending_approval')->sum('commission_amount');
        $totalPendingPayment = (float) $all->where('status', 'pending_payment')->sum('commission_amount');
        $totalPaid = (float) $all->where('status', 'paid')->sum('commission_amount');

        $statuses = [
            '' => 'الكل',
            'pending_approval' => 'في انتظار الاعتماد',
            'approved' => 'معتمد',
            'pending_payment' => 'في انتظار الدفع',
            'paid' => 'مدفوع',
            'rejected' => 'مرفوض',
        ];

        $salesUsers = User::orderBy('name')->get();

        return view('sales.commissions.index', [
            'commissions' => $commissions,
            'totalCalculated' => $totalCalculated,
            'totalPendingApproval' => $totalPendingApproval,
            'totalPendingPayment' => $totalPendingPayment,
            'totalPaid' => $totalPaid,
            'statuses' => $statuses,
            'salesUsers' => $salesUsers,
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $invoices = SalesInvoice::whereBetween('date', [$data['start_date'], $data['end_date']])
            ->whereDoesntHave('commissions')
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $base = (float) $invoice->total;
            if ($base <= 0) {
                continue;
            }

            $rule = CommissionRule::activeForDate($invoice->date?->toDateString());
            $rate = $rule ? (float) $rule->rate_percent : 1.0;
            $commissionAmount = $rule ? $rule->computeAmount($base) : round($base * $rate / 100, 4);
            $ruleId = $rule?->id;

            Commission::create([
                'user_id' => $request->user()?->id,
                'sales_invoice_id' => $invoice->id,
                'commission_rule_id' => $ruleId,
                'base_amount' => $base,
                'rate_percent' => $rate,
                'commission_amount' => $commissionAmount,
                'calculated_at' => now()->toDateString(),
                'status' => 'pending_approval',
            ]);
            $count++;
        }

        $message = $count > 0
            ? "تم حساب العمولات لـ {$count} فاتورة."
            : 'لا توجد فواتير بدون عمولات في الفترة المحددة.';

        return redirect()
            ->route('sales.commissions.index')
            ->with('success', $message);
    }
}

