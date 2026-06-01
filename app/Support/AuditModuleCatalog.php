<?php

declare(strict_types=1);

namespace App\Support;

/**
 * تسميات الموديولات وأنواع العمليات لعرض سجل التدقيق.
 */
final class AuditModuleCatalog
{
    /**
     * @return array<string, string>
     */
    public static function trailModuleLabels(): array
    {
        return [
            'sales_invoices' => 'المبيعات',
            'purchase_invoices' => 'المشتريات',
            'sales_orders' => 'أوامر البيع',
            'service_orders' => 'الخدمات',
            'delivery_orders' => 'التوريد',
            'production_orders' => 'الإنتاج',
            'production_records' => 'سجلات الإنتاج',
            'production_logs' => 'إنتاج لحظي',
            'stock_movements' => 'حركات المخزون',
            'bom' => 'قوائم المواد',
            'accounts' => 'دليل الحسابات',
            'journal_entries' => 'القيود المحاسبية',
            'journal_items' => 'بنود القيود',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function trailActionLabels(): array
    {
        return [
            'create' => 'إنشاء',
            'update' => 'تحديث',
            'delete' => 'حذف',
            'complete' => 'إكمال',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function controlActionLabels(): array
    {
        return [
            'role_changed' => 'تغيير دور',
            'receipt_created' => 'سند قبض',
            'payment_created' => 'سند صرف',
            'pos_sale_completed' => 'بيع نقطة بيع',
            'pos_session_opened' => 'فتح جلسة POS',
            'account_delete' => 'حذف حساب',
            'account_purge_and_delete' => 'مسح وحذف حساب',
            'expense_back_to_draft' => 'إرجاع مصروف لمسودة',
            'expense_delete_draft' => 'حذف مصروف مسودة',
            'expense_hard_delete' => 'حذف مصروف نهائي',
            'expenses_bulk_delete_super' => 'حذف جماعي للمصروفات',
            'super_purge_financial' => 'مسح مالي شامل',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function controlModuleLabels(): array
    {
        return [
            'finance' => 'المالية',
            'pos' => 'نقطة البيع',
            'hr' => 'الموارد البشرية',
            'security' => 'الأمان والصلاحيات',
            'system' => 'النظام',
        ];
    }

    public static function trailModuleLabel(string $tableName): string
    {
        return self::trailModuleLabels()[$tableName] ?? self::trailModuleLabels()[str_replace('_', '-', $tableName)] ?? $tableName;
    }

    public static function trailActionLabel(string $action): string
    {
        return self::trailActionLabels()[$action] ?? $action;
    }

    public static function controlActionLabel(string $action): string
    {
        return self::controlActionLabels()[$action] ?? $action;
    }

    public static function controlModuleForAction(string $action): string
    {
        if (str_starts_with($action, 'pos_')) {
            return 'pos';
        }
        if (str_starts_with($action, 'expense_') || str_starts_with($action, 'account_') || in_array($action, ['payment_created', 'receipt_created', 'super_purge_financial'], true)) {
            return 'finance';
        }
        if ($action === 'role_changed') {
            return 'hr';
        }

        return 'system';
    }

    public static function controlModuleLabel(string $moduleKey): string
    {
        return self::controlModuleLabels()[$moduleKey] ?? $moduleKey;
    }
}
