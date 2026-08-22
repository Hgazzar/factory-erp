<?php

declare(strict_types=1);

namespace App\Support;

/**
 * يحدّد صلاحية Nursery المطلوبة لكل مسار Finance معتمد في Stage D1.
 * المسارات غير المدرجة (HIDE / NOT NEEDED) تُرفض للموظفين؛ المالك يمرّ عبر isTenantOwner.
 */
final class NurseryFinanceRouteGate
{
    /**
     * @return NurseryAccess::CAP_*|null null = غير معتمد للموظفين في Niche الحضانة
     */
    public static function capabilityForRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        // ترتيب الأدق أولاً (categories قبل expenses)
        if (str_starts_with($routeName, 'finance.expenses.categories')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_EXPENSES;
        }

        if (str_starts_with($routeName, 'finance.expenses')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_EXPENSES;
        }

        if (str_starts_with($routeName, 'finance.accounts')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_LEDGER;
        }

        if (str_starts_with($routeName, 'finance.journals')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_LEDGER;
        }

        if ($routeName === 'finance.ledger.index') {
            return NurseryAccess::CAP_MANAGE_FINANCE_LEDGER;
        }

        if (str_starts_with($routeName, 'finance.receipts')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_TREASURY;
        }

        if (str_starts_with($routeName, 'finance.payments')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_TREASURY;
        }

        if (str_starts_with($routeName, 'finance.bank-accounts')) {
            return NurseryAccess::CAP_MANAGE_FINANCE_TREASURY;
        }

        if (str_starts_with($routeName, 'finance.bank-reconciliations')) {
            return NurseryAccess::CAP_FINANCE_ADMIN;
        }

        if (str_starts_with($routeName, 'finance.tax-rates')) {
            return NurseryAccess::CAP_FINANCE_ADMIN;
        }

        if (str_starts_with($routeName, 'finance.payment-method-accounts')) {
            return NurseryAccess::CAP_FINANCE_ADMIN;
        }

        if (in_array($routeName, [
            'finance.dashboard',
            'finance.index',
            'finance.reports.profit-loss',
            'finance.reports.trial-balance',
            'reports.tax.index',
        ], true)) {
            return NurseryAccess::CAP_VIEW_FINANCE_REPORTS;
        }

        // ملخص الحضانة له middleware خاص (nursery.capability)
        if ($routeName === 'nursery.finance.index') {
            return NurseryAccess::CAP_VIEW_FINANCE;
        }

        return null;
    }
}
