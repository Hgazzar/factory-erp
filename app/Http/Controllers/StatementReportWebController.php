<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\SalesInvoice;
use App\Models\SalesPaymentInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StatementReportWebController extends Controller
{
    /**
     * كشف حساب العميل – واجهة جديدة تعتمد على فواتير المبيعات والمدفوعات والعقود والأقساط.
     */
    public function index(Request $request): View
    {
        $customers = Customer::orderBy('name')->get();

        $customerId = $request->integer('customer_id') ?: null;
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $range = $request->input('range'); // this_month | this_quarter | this_year | last_12_months

        if ($range && !$fromDate && !$toDate) {
            [$fromDate, $toDate] = $this->resolveRangeDates($range);
        }

        $customer = null;
        $transactions = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $currentBalance = 0.0;
        $remainingInstallments = 0.0;

        if ($customerId) {
            $validated = $request->validate([
                'customer_id' => ['required', 'exists:customers,id'],
                'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            ]);

            $customerId = (int) $validated['customer_id'];
            $fromDate = $validated['from_date'] ?? $fromDate;
            $toDate = $validated['to_date'] ?? $toDate;

            $customer = Customer::find($customerId);

            $transactions = $this->buildCustomerStatement($customerId, $fromDate, $toDate);

            foreach ($transactions as $row) {
                $totalDebit += (float) $row['debit'];
                $totalCredit += (float) $row['credit'];
            }
            $currentBalance = $totalDebit - $totalCredit;

            $remainingInstallments = Installment::whereHas('salesInvoice', fn ($q) => $q->where('customer_id', $customerId))
                ->get()
                ->sum(fn ($i) => $i->balance);
        }

        return view('reports.statement.index', [
            'customers' => $customers,
            'customerId' => $customerId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'range' => $range,
            'customer' => $customer,
            'transactions' => $transactions,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'currentBalance' => $currentBalance,
            'remainingInstallments' => $remainingInstallments,
        ]);
    }

    private function resolveRangeDates(string $range): array
    {
        $today = Carbon::today();

        return match ($range) {
            'this_month' => [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ],
            'this_quarter' => [
                $today->copy()->firstOfQuarter()->toDateString(),
                $today->copy()->lastOfQuarter()->toDateString(),
            ],
            'this_year' => [
                $today->copy()->startOfYear()->toDateString(),
                $today->copy()->endOfYear()->toDateString(),
            ],
            'last_12_months' => [
                $today->copy()->subMonthsNoOverflow(12)->addDay()->toDateString(),
                $today->toDateString(),
            ],
            default => [$today->toDateString(), $today->toDateString()],
        };
    }

    /**
     * يبني كشف حساب العميل من فواتير المبيعات والمدفوعات والعقود.
     */
    private function buildCustomerStatement(int $customerId, ?string $fromDate, ?string $toDate): array
    {
        $rows = [];

        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $inv) {
            $rows[] = [
                'date' => $inv->date?->format('Y-m-d'),
                'type' => 'invoice',
                'ref' => 'SINV-' . $inv->id,
                'desc' => 'فاتورة مبيعات',
                'debit' => (float) $inv->total,
                'credit' => 0.0,
            ];
        }

        $payments = SalesPaymentInvoice::with(['salesPayment', 'salesInvoice'])
            ->whereHas('salesPayment', fn ($q) => $q->where('customer_id', $customerId)
                ->when($fromDate, fn ($qq) => $qq->whereDate('date', '>=', $fromDate))
                ->when($toDate, fn ($qq) => $qq->whereDate('date', '<=', $toDate)))
            ->get();

        foreach ($payments as $alloc) {
            $pay = $alloc->salesPayment;
            if (!$pay) {
                continue;
            }

            $rows[] = [
                'date' => $pay->date?->format('Y-m-d'),
                'type' => 'payment',
                'ref' => 'SPAY-' . $pay->id,
                'desc' => 'سند قبض - سداد فاتورة',
                'debit' => 0.0,
                'credit' => (float) $alloc->amount_allocated,
            ];
        }

        $contracts = Contract::where('customer_id', $customerId)
            ->when($fromDate, fn ($q) => $q->whereDate('start_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('start_date', '<=', $toDate))
            ->get();

        foreach ($contracts as $contract) {
            $rows[] = [
                'date' => $contract->start_date?->format('Y-m-d'),
                'type' => 'contract',
                'ref' => $contract->contract_number ?? ('CON-' . $contract->id),
                'desc' => 'عقد اشتراك',
                'debit' => (float) $contract->total,
                'credit' => 0.0,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $cmp = strcmp((string) $a['date'], (string) $b['date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($a['type'], $b['type']);
        });

        $balance = 0.0;
        foreach ($rows as &$row) {
            $balance += (float) $row['debit'] - (float) $row['credit'];
            $row['balance'] = $balance;
        }
        unset($row);

        return $rows;
    }
}
