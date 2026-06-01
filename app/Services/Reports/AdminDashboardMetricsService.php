<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Support\DefaultLedgerAccounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * مؤشرات لوحة تحكم الأدمن: سيولة، أرباح، ومقارنة مبيعات/مشتريات.
 */
final class AdminDashboardMetricsService
{
    public function __construct(
        private readonly ProfitLossReportService $profitLoss,
    ) {}

    /**
     * @return array{
     *     net_profit_mtd: float,
     *     net_sales_mtd: float,
     *     gross_profit_mtd: float,
     *     liquidity: float,
     *     sales_mtd: float,
     *     purchases_mtd: float,
     *     from_date: string,
     *     to_date: string,
     * }
     */
    public function kpis(int $tenantUserId): array
    {
        $today = Carbon::today();
        $from = $today->copy()->startOfMonth()->toDateString();
        $to = $today->toDateString();

        $pl = $this->profitLoss->generate($tenantUserId, $from, $to);

        $salesMtd = (float) SalesInvoice::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereBetween('date', [$from, $to])
            ->whereNotNull('posted_at')
            ->sum('total');

        $purchasesMtd = (float) PurchaseInvoice::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereBetween('date', [$from, $to])
            ->whereNotNull('posted_at')
            ->sum('total');

        return [
            'net_profit_mtd' => (float) $pl['net_profit'],
            'net_sales_mtd' => (float) $pl['net_sales'],
            'gross_profit_mtd' => (float) $pl['gross_profit'],
            'liquidity' => $this->liquidityBalance($tenantUserId),
            'sales_mtd' => round($salesMtd, 4),
            'purchases_mtd' => round($purchasesMtd, 4),
            'from_date' => $from,
            'to_date' => $to,
        ];
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     sales: list<float>,
     *     purchases: list<float>,
     *     net_profit: list<float>,
     *     liquidity_trend: list<float>,
     * }
     */
    public function chartSeries(int $tenantUserId, int $months = 6): array
    {
        $today = Carbon::today();
        $labels = [];
        $sales = [];
        $purchases = [];
        $netProfit = [];
        $liquidityTrend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $from = $month->copy()->startOfMonth()->toDateString();
            $to = $month->copy()->endOfMonth()->toDateString();
            $labels[] = $month->translatedFormat('M Y');

            $pl = $this->profitLoss->generate($tenantUserId, $from, $to);
            $netProfit[] = (float) $pl['net_profit'];

            $sales[] = (float) SalesInvoice::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereBetween('date', [$from, $to])
                ->whereNotNull('posted_at')
                ->sum('total');

            $purchases[] = (float) PurchaseInvoice::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereBetween('date', [$from, $to])
                ->whereNotNull('posted_at')
                ->sum('total');

            $liquidityTrend[] = $this->liquidityBalanceUntil($tenantUserId, $to);
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'purchases' => $purchases,
            'net_profit' => $netProfit,
            'liquidity_trend' => $liquidityTrend,
        ];
    }

    private function liquidityBalance(int $tenantUserId): float
    {
        return $this->liquidityBalanceUntil($tenantUserId, Carbon::today()->toDateString());
    }

    private function liquidityBalanceUntil(int $tenantUserId, string $date): float
    {
        $accounts = Account::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereIn('code', [DefaultLedgerAccounts::CODE_CASH, DefaultLedgerAccounts::CODE_BANK])
            ->get(['id', 'opening_balance']);

        $total = 0.0;
        foreach ($accounts as $account) {
            $movement = (float) DB::table('journal_items as ji')
                ->join('journal_entries as je', 'je.id', '=', 'ji.journal_entry_id')
                ->where('je.user_id', $tenantUserId)
                ->where('ji.account_id', $account->id)
                ->whereDate('je.date', '<=', $date)
                ->sum(DB::raw('ji.debit - ji.credit'));
            $total += (float) $account->opening_balance + $movement;
        }

        return round(max(0, $total), 4);
    }
}
