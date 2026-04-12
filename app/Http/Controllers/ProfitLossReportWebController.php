<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\SalesPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfitLossReportWebController extends Controller
{
    /**
     * تقرير الأرباح والخسائر: تحصيل المبيعات − COGS − مصاريف إدارية (سندات صرف مصروف).
     */
    public function index(Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate === null && $toDate === null) {
            $fromDate = Carbon::today()->startOfMonth()->toDateString();
            $toDate = Carbon::today()->toDateString();
        }

        $receiptsTotal = (float) Receipt::query()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');

        $salesPaymentsTotal = (float) SalesPayment::query()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');

        $salesCollections = $receiptsTotal + $salesPaymentsTotal;

        $cogsCode = (string) config('accounting.cogs_code', '5000');
        $cogsAccountId = Account::query()->where('code', $cogsCode)->value('id');

        $cogs = 0.0;
        if ($cogsAccountId) {
            $cogs = (float) JournalItem::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->where('journal_items.account_id', $cogsAccountId)
                ->when($fromDate, fn ($q) => $q->whereDate('journal_entries.date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('journal_entries.date', '<=', $toDate))
                ->sum(DB::raw('journal_items.debit - journal_items.credit'));
            $cogs = max(0, $cogs);
        }

        $adminExpenses = (float) Payment::query()
            ->accountingPostedExpenses()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');

        $netProfit = $salesCollections - $cogs - $adminExpenses;

        $receiptsCount = Receipt::query()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->count();

        $salesPaymentsCount = SalesPayment::query()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->count();

        $expensePaymentsCount = Payment::query()
            ->accountingPostedExpenses()
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->count();

        return view('finance.reports.profit-loss', compact(
            'fromDate',
            'toDate',
            'receiptsTotal',
            'salesPaymentsTotal',
            'salesCollections',
            'cogs',
            'cogsCode',
            'adminExpenses',
            'netProfit',
            'receiptsCount',
            'salesPaymentsCount',
            'expensePaymentsCount'
        ));
    }
}
