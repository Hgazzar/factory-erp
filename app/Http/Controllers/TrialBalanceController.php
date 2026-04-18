<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        $tenantId = (int) auth()->id();

        $periodRows = DB::table('journal_items as ji')
            ->join('journal_entries as je', 'je.id', '=', 'ji.journal_entry_id')
            ->where('je.user_id', $tenantId)
            ->select(
                'ji.account_id',
                DB::raw('COALESCE(SUM(ji.debit), 0) as period_debit'),
                DB::raw('COALESCE(SUM(ji.credit), 0) as period_credit')
            )
            ->when($fromDate, fn ($query) => $query->whereDate('je.date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('je.date', '<=', $toDate))
            ->groupBy('ji.account_id')
            ->get()
            ->keyBy('account_id');

        $openingRows = collect();
        if ($fromDate) {
            $openingRows = DB::table('journal_items as ji')
                ->join('journal_entries as je', 'je.id', '=', 'ji.journal_entry_id')
                ->where('je.user_id', $tenantId)
                ->select(
                    'ji.account_id',
                    DB::raw('COALESCE(SUM(ji.debit), 0) as opening_debit'),
                    DB::raw('COALESCE(SUM(ji.credit), 0) as opening_credit')
                )
                ->whereDate('je.date', '<', $fromDate)
                ->when($toDate, fn ($query) => $query->whereDate('je.date', '<=', $toDate))
                ->groupBy('ji.account_id')
                ->get()
                ->keyBy('account_id');
        }

        $accountIds = collect($periodRows->keys())
            ->merge($openingRows->keys())
            ->unique()
            ->values();

        $accounts = Account::query()
            ->where('user_id', $tenantId)
            ->whereIn('id', $accountIds)
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $rows = $accounts->map(function (Account $account) use ($periodRows, $openingRows) {
            $period = $periodRows->get($account->id);
            $opening = $openingRows->get($account->id);

            $openingBalance = (float) ($opening->opening_debit ?? 0) - (float) ($opening->opening_credit ?? 0);
            $periodDebit = (float) ($period->period_debit ?? 0);
            $periodCredit = (float) ($period->period_credit ?? 0);
            $closingBalance = $openingBalance + ($periodDebit - $periodCredit);

            return [
                'account_id' => (int) $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name_ar ?: ($account->name_en ?? '—'),
                'debit' => $periodDebit,
                'credit' => $periodCredit,
                'closing_balance' => $closingBalance,
            ];
        })->values();

        $totalDebit = (float) $rows->sum('debit');
        $totalCredit = (float) $rows->sum('credit');
        $isBalanced = abs($totalDebit - $totalCredit) < 0.0001;

        if ($request->query('export') === 'excel') {
            return $this->exportExcel($rows->all(), $fromDate, $toDate);
        }

        return view('finance.reports.trial-balance', [
            'rows' => $rows,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'isBalanced' => $isBalanced,
        ]);
    }

    private function exportExcel(array $rows, ?string $fromDate, ?string $toDate): StreamedResponse
    {
        $fileName = 'trial-balance-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($rows, $fromDate, $toDate): void {
            echo "\xEF\xBB\xBF";
            echo "ميزان المراجعة\n";
            echo 'الفترة: من '.($fromDate ?: '—').' إلى '.($toDate ?: '—')."\n\n";
            echo "كود الحساب\tاسم الحساب\tمدين\tدائن\tالرصيد النهائي\n";

            foreach ($rows as $row) {
                echo $row['account_code']."\t"
                    .$row['account_name']."\t"
                    .number_format((float) $row['debit'], 2, '.', '')."\t"
                    .number_format((float) $row['credit'], 2, '.', '')."\t"
                    .number_format((float) $row['closing_balance'], 2, '.', '')."\n";
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
