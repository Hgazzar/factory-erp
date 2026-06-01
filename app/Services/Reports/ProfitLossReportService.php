<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\CompanySetting;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;

/**
 * قائمة أرباح وخسائر (استحقاق) من القيود المحاسبية للمستأجر.
 */
final class ProfitLossReportService
{
    /**
     * @return array{
     *     from_date: string,
     *     to_date: string,
     *     gross_sales: float,
     *     sales_returns: float,
     *     net_sales: float,
     *     cogs: float,
     *     purchase_returns: float,
     *     net_cogs: float,
     *     gross_profit: float,
     *     payroll_expense: float,
     *     operating_expenses: float,
     *     net_profit: float,
     *     payroll_account_code: ?string,
     *     cogs_account_code: string,
     * }
     */
    public function generate(int $tenantUserId, string $fromDate, string $toDate): array
    {
        $revenueIds = $this->accountIdsByType($tenantUserId, Account::TYPE_REVENUE);
        $salesReturnId = $this->accountIdByCode($tenantUserId, DefaultLedgerAccounts::CODE_SALES_RETURNS);
        $revenueIdsExReturns = array_values(array_diff($revenueIds, array_filter([$salesReturnId])));

        $grossSales = $this->sumMovement($tenantUserId, $revenueIdsExReturns, $fromDate, $toDate, 'credit');
        $salesReturns = $salesReturnId
            ? $this->sumMovement($tenantUserId, [$salesReturnId], $fromDate, $toDate, 'debit')
            : 0.0;
        $netSales = round(max(0, $grossSales - $salesReturns), 4);

        $cogsAccountId = $this->accountIdByCode($tenantUserId, DefaultLedgerAccounts::CODE_COGS);
        $purchaseReturnId = $this->accountIdByCode($tenantUserId, DefaultLedgerAccounts::CODE_PURCHASE_RETURNS);

        $cogs = $cogsAccountId
            ? $this->sumMovement($tenantUserId, [$cogsAccountId], $fromDate, $toDate, 'debit')
            : 0.0;
        $purchaseReturns = $purchaseReturnId
            ? $this->sumMovement($tenantUserId, [$purchaseReturnId], $fromDate, $toDate, 'credit')
            : 0.0;
        $netCogs = round(max(0, $cogs - $purchaseReturns), 4);

        $payrollAccountId = $this->resolvePayrollExpenseAccountId($tenantUserId);
        $payrollExpense = $payrollAccountId
            ? $this->sumMovement($tenantUserId, [$payrollAccountId], $fromDate, $toDate, 'debit')
            : 0.0;

        $excludeExpenseIds = array_values(array_filter(array_unique(array_merge(
            $cogsAccountId ? [$cogsAccountId] : [],
            $purchaseReturnId ? [$purchaseReturnId] : [],
            $payrollAccountId ? [$payrollAccountId] : [],
        ))));

        $allExpenseIds = $this->accountIdsByType($tenantUserId, Account::TYPE_EXPENSE);
        $operatingExpenseIds = array_values(array_diff($allExpenseIds, $excludeExpenseIds));
        $operatingExpenses = $this->sumMovement($tenantUserId, $operatingExpenseIds, $fromDate, $toDate, 'debit');

        $grossProfit = round($netSales - $netCogs, 4);
        $netProfit = round($netSales - $netCogs - $payrollExpense - $operatingExpenses, 4);

        $payrollCode = null;
        if ($payrollAccountId) {
            $payrollCode = Account::withoutGlobalScopes()->whereKey($payrollAccountId)->value('code');
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'gross_sales' => round($grossSales, 4),
            'sales_returns' => round($salesReturns, 4),
            'net_sales' => $netSales,
            'cogs' => round($cogs, 4),
            'purchase_returns' => round($purchaseReturns, 4),
            'net_cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'payroll_expense' => round($payrollExpense, 4),
            'operating_expenses' => round($operatingExpenses, 4),
            'net_profit' => $netProfit,
            'payroll_account_code' => $payrollCode ? (string) $payrollCode : null,
            'cogs_account_code' => DefaultLedgerAccounts::CODE_COGS,
        ];
    }

    private function resolvePayrollExpenseAccountId(int $tenantUserId): ?int
    {
        $setting = CompanySetting::forTenant($tenantUserId);
        if ($setting?->payroll_wage_expense_account_id) {
            return (int) $setting->payroll_wage_expense_account_id;
        }

        return $this->accountIdByCode($tenantUserId, '6100');
    }

    /**
     * @param  list<int>  $accountIds
     */
    private function sumMovement(
        int $tenantUserId,
        array $accountIds,
        string $fromDate,
        string $toDate,
        string $normalSide,
    ): float {
        if ($accountIds === []) {
            return 0.0;
        }

        $expression = $normalSide === 'credit'
            ? 'ji.credit - ji.debit'
            : 'ji.debit - ji.credit';

        $sum = (float) DB::table('journal_items as ji')
            ->join('journal_entries as je', 'je.id', '=', 'ji.journal_entry_id')
            ->where('je.user_id', $tenantUserId)
            ->whereIn('ji.account_id', $accountIds)
            ->whereBetween('je.date', [$fromDate, $toDate])
            ->sum(DB::raw($expression));

        return round(max(0, $sum), 4);
    }

    /**
     * @return list<int>
     */
    private function accountIdsByType(int $tenantUserId, string $type): array
    {
        return Account::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('type', $type)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function accountIdByCode(int $tenantUserId, string $code): ?int
    {
        $id = Account::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('code', $code)
            ->value('id');

        return $id ? (int) $id : null;
    }
}
