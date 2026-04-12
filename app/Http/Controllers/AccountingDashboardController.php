<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Supplier;
use App\Support\DefaultLedgerAccounts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $expenseSupplierId = $request->query('expense_supplier_id');
        $expenseSupplierId = ($expenseSupplierId !== null && $expenseSupplierId !== '') ? (int) $expenseSupplierId : null;
        if ($expenseSupplierId && ! Supplier::query()->whereKey($expenseSupplierId)->exists()) {
            $expenseSupplierId = null;
        }
        $expenseDateFrom = $request->query('expense_date_from');
        $expenseDateTo = $request->query('expense_date_to');
        $expenseFiltersActive = $expenseSupplierId || filled($expenseDateFrom) || filled($expenseDateTo);

        $expenseSuppliers = Supplier::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $cashAccount = Account::where('code', DefaultLedgerAccounts::CODE_CASH)->first();
        $bankAccount = Account::where('code', DefaultLedgerAccounts::CODE_BANK)->first();
        $arAccount = Account::where('code', DefaultLedgerAccounts::CODE_AR)->first();
        $apAccount = Account::where('code', DefaultLedgerAccounts::CODE_AP)->first();

        $cashLedgerBalance = $cashAccount ? $this->ledgerSignedBalance($cashAccount->id) : 0.0;
        $bankLedgerBalance = $bankAccount ? $this->ledgerSignedBalance($bankAccount->id) : 0.0;

        // أرصدة من الأستاذ العام (مدين سالب للخصوم الطبيعية → نعرضها كمبلغ مستحق موجب)
        $receivableAgingTotal = $arAccount ? max(0.0, $this->ledgerSignedBalance($arAccount->id)) : 0.0;
        $payableAgingTotal = $apAccount ? $this->liabilityPayableDisplay($apAccount->id) : 0.0;

        $unsettledCount = 0;

        $expenseAccountIds = Account::where('type', Account::TYPE_EXPENSE)->pluck('id')->toArray();
        $revenueAccountIds = Account::where('type', Account::TYPE_REVENUE)->pluck('id')->toArray();

        $chartLabels = [];
        $chartRevenue = [];
        $chartExpenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = 0.0;
            if (count($revenueAccountIds) > 0) {
                $revenue = (float) DB::table('journal_items')
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                    ->whereBetween('journal_entries.date', [$monthStart, $monthEnd])
                    ->whereIn('journal_items.account_id', $revenueAccountIds)
                    ->sum(DB::raw('journal_items.credit - journal_items.debit'));
            }

            $expenses = 0.0;
            if ($expenseFiltersActive) {
                $expenses = $this->monthExpenseTotalFromPayments(
                    $monthStart,
                    $monthEnd,
                    $expenseSupplierId,
                    $expenseDateFrom,
                    $expenseDateTo
                );
            } elseif (count($expenseAccountIds) > 0) {
                $expenses = (float) DB::table('journal_items')
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                    ->whereBetween('journal_entries.date', [$monthStart, $monthEnd])
                    ->whereIn('journal_items.account_id', $expenseAccountIds)
                    ->sum(DB::raw('journal_items.debit - journal_items.credit'));
            }

            $chartLabels[] = $month->format('Y-m');
            $chartRevenue[] = $revenue;
            $chartExpenses[] = $expenses;
        }

        $budgetTotal = (float) Account::where('type', Account::TYPE_EXPENSE)->sum('opening_balance');

        $budgetUsed = 0.0;
        if ($budgetTotal > 0) {
            if ($expenseFiltersActive) {
                $budgetUsed = $this->yearToDateExpenseFromPayments(
                    $today,
                    $expenseSupplierId,
                    $expenseDateFrom,
                    $expenseDateTo
                );
            } elseif (count($expenseAccountIds) > 0) {
                $yearStart = $today->copy()->startOfYear();
                $yearEnd = $today->copy()->endOfYear();

                $budgetUsed = (float) DB::table('journal_items')
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                    ->whereBetween('journal_entries.date', [$yearStart, $yearEnd])
                    ->whereIn('journal_items.account_id', $expenseAccountIds)
                    ->sum(DB::raw('journal_items.debit - journal_items.credit'));
            }
        }

        $budgetRemaining = max($budgetTotal - $budgetUsed, 0);
        $budgetVsActualPercent = $budgetTotal > 0 ? min(100, ($budgetUsed / $budgetTotal) * 100) : 0.0;

        $latestJournals = JournalEntry::with('items')->latest('date')->limit(5)->get();

        return view('finance.dashboard.index', compact(
            'cashLedgerBalance',
            'bankLedgerBalance',
            'receivableAgingTotal',
            'payableAgingTotal',
            'unsettledCount',
            'budgetVsActualPercent',
            'chartLabels',
            'chartRevenue',
            'chartExpenses',
            'budgetTotal',
            'budgetUsed',
            'budgetRemaining',
            'latestJournals',
            'expenseSupplierId',
            'expenseDateFrom',
            'expenseDateTo',
            'expenseFiltersActive',
            'expenseSuppliers'
        ));
    }

    /**
     * Sum expense payments (amount + tax) whose dates fall in the month window intersected with optional filters.
     */
    private function monthExpenseTotalFromPayments(
        Carbon $monthStart,
        Carbon $monthEnd,
        ?int $supplierId,
        mixed $dateFrom,
        mixed $dateTo,
    ): float {
        $rangeStart = $monthStart->copy()->startOfDay();
        $rangeEnd = $monthEnd->copy()->endOfDay();

        if (filled($dateFrom)) {
            $df = Carbon::parse($dateFrom)->startOfDay();
            if ($df->gt($rangeStart)) {
                $rangeStart = $df;
            }
        }
        if (filled($dateTo)) {
            $dt = Carbon::parse($dateTo)->endOfDay();
            if ($dt->lt($rangeEnd)) {
                $rangeEnd = $dt;
            }
        }

        if ($rangeStart->gt($rangeEnd)) {
            return 0.0;
        }

        return (float) Payment::query()
            ->accountingPostedExpenses()
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->whereDate('date', '>=', $rangeStart->toDateString())
            ->whereDate('date', '<=', $rangeEnd->toDateString())
            ->selectRaw('COALESCE(SUM(amount + COALESCE(tax_amount, 0)), 0) as t')
            ->value('t');
    }

    /**
     * Year-to-date total from expense payments, intersected with optional supplier / date filters.
     */
    private function yearToDateExpenseFromPayments(
        Carbon $today,
        ?int $supplierId,
        mixed $dateFrom,
        mixed $dateTo,
    ): float {
        $yearStart = $today->copy()->startOfYear()->startOfDay();
        $yearEnd = $today->copy()->endOfYear()->endOfDay();

        $rangeStart = $yearStart->copy();
        $rangeEnd = $yearEnd->copy();

        if (filled($dateFrom)) {
            $df = Carbon::parse($dateFrom)->startOfDay();
            if ($df->gt($rangeStart)) {
                $rangeStart = $df;
            }
        }
        if (filled($dateTo)) {
            $dt = Carbon::parse($dateTo)->endOfDay();
            if ($dt->lt($rangeEnd)) {
                $rangeEnd = $dt;
            }
        }

        if ($rangeStart->gt($rangeEnd)) {
            return 0.0;
        }

        return (float) Payment::query()
            ->accountingPostedExpenses()
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->whereDate('date', '>=', $rangeStart->toDateString())
            ->whereDate('date', '<=', $rangeEnd->toDateString())
            ->selectRaw('COALESCE(SUM(amount + COALESCE(tax_amount, 0)), 0) as t')
            ->value('t');
    }

    /**
     * رصيد موقّع: الرصيد الافتتاحي + (مدين − دائن) على القيود.
     */
    private function ledgerSignedBalance(int $accountId): float
    {
        $opening = (float) (Account::where('id', $accountId)->value('opening_balance') ?? 0);

        $movement = (float) DB::table('journal_items')
            ->where('account_id', $accountId)
            ->sum(DB::raw('COALESCE(debit,0) - COALESCE(credit,0)'));

        return $opening + $movement;
    }

    /**
     * مبلغ الذمم الدائنة كرقم موجب (طبيعة الحساب دائن).
     */
    private function liabilityPayableDisplay(int $accountId): float
    {
        return max(0.0, -$this->ledgerSignedBalance($accountId));
    }
}
