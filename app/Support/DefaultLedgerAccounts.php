<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Account;

/**
 * يضمن وجود حسابات دليل الحسابات القياسية (أكواد رباعية متوافقة مع AccountSeeder) لكل مستخدم.
 */
final class DefaultLedgerAccounts
{
    public const CODE_CURRENT_ASSETS = '1000';

    public const CODE_CASH = '1010';

    public const CODE_BANK = '1020';

    public const CODE_AR = '1030';

    public const CODE_INVENTORY_PARENT = '1040';

    public const CODE_RAW_MATERIALS_INV = '1041';

    public const CODE_FINISHED_GOODS_INV = '1042';

    public const CODE_INVENTORY_RECEIPTS = '1200';

    public const CODE_CURRENT_LIABILITIES = '2000';

    public const CODE_AP = '2010';

    public const CODE_VAT_PAYABLE = '2030';

    public const CODE_SALES_REVENUE = '4000';

    /** مرتجعات المبيعات (إيراد مقاصّ) */
    public const CODE_SALES_RETURNS = '4050';

    public const CODE_COGS = '5000';

    /** مردودات المشتريات (مصروف مقاصّ) */
    public const CODE_PURCHASE_RETURNS = '5050';

    public const CODE_OPERATING_EXPENSES = '6000';

    /** هالك وإتلاف (إنتاج) */
    public const CODE_SCRAP_EXPENSE = '6060';

    /** مجموعة الأصول الثابتة في الدليل (أصول غير متداولة). */
    public const CODE_FIXED_ASSETS_GROUP = '1500';

    /** مجمع الإهلاك الافتراضي (رصيده سالب عادةً). */
    public const CODE_ACCUMULATED_DEPRECIATION = '1510';

    /** حساب أصل تفصيلي للترحيل الافتراضي تحت 1500. */
    public const CODE_PPE_POSTING = '1590';

    /** مصروف إهلاك افتراضي تحت مصروفات التشغيل. */
    public const CODE_DEPRECIATION_EXPENSE = '6510';

    private static function tenantUserId(): int
    {
        return (int) (auth()->id() ?? 1);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function firstOrCreateAccount(string $code, array $attributes, ?int $userId = null): Account
    {
        $uid = $userId ?? self::tenantUserId();

        return Account::withoutGlobalScopes()->firstOrCreate(
            ['code' => $code, 'user_id' => $uid],
            array_merge($attributes, ['user_id' => $uid])
        );
    }

    /**
     * حساب أصل (تفصيلي) لترحيل تكلفة الأصول الثابتة الجديدة.
     */
    public static function fixedAssetPostingAccount(?int $userId = null): Account
    {
        $parent = self::firstOrCreateAccount(self::CODE_FIXED_ASSETS_GROUP, [
            'name_ar' => 'الأصول الثابتة',
            'name_en' => 'Fixed Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);

        return self::firstOrCreateAccount(self::CODE_PPE_POSTING, [
            'name_ar' => 'أصول ثابتة — ترحيل',
            'name_en' => 'Fixed Assets — Posting',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
            'allow_direct_posting' => true,
        ], $userId);
    }

    /**
     * حساب مجمع الإهلاك الافتراضي (أصل بالرصيد الدائن/السالب).
     */
    public static function accumulatedDepreciationAccount(?int $userId = null): Account
    {
        $parent = self::firstOrCreateAccount(self::CODE_FIXED_ASSETS_GROUP, [
            'name_ar' => 'الأصول الثابتة',
            'name_en' => 'Fixed Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);

        return self::firstOrCreateAccount(self::CODE_ACCUMULATED_DEPRECIATION, [
            'name_ar' => 'مجمع الإهلاك',
            'name_en' => 'Accumulated Depreciation',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
            'allow_direct_posting' => true,
        ], $userId);
    }

    /**
     * مصروف إهلاك افتراضي لربط فئات الأصول.
     */
    public static function depreciationExpenseAccount(?int $userId = null): Account
    {
        $parent = self::ensureOperatingExpensesRoot($userId);

        return self::firstOrCreateAccount(self::CODE_DEPRECIATION_EXPENSE, [
            'name_ar' => 'إهلاك الأصول الثابتة',
            'name_en' => 'Depreciation Expense',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
            'allow_direct_posting' => true,
        ], $userId);
    }

    public static function ensureCurrentAssetsGroup(?int $userId = null): Account
    {
        return self::firstOrCreateAccount(self::CODE_CURRENT_ASSETS, [
            'name_ar' => 'الأصول المتداولة',
            'name_en' => 'Current Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    public static function ensureCurrentLiabilitiesGroup(): Account
    {
        return self::firstOrCreateAccount(self::CODE_CURRENT_LIABILITIES, [
            'name_ar' => 'الخصوم المتداولة',
            'name_en' => 'Current Liabilities',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function ensureInventoryParent(?int $userId = null): Account
    {
        $pid = self::ensureCurrentAssetsGroup($userId)->id;

        return self::firstOrCreateAccount(self::CODE_INVENTORY_PARENT, [
            'name_ar' => 'المخزون',
            'name_en' => 'Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    public static function ensureCogsRoot(): Account
    {
        return self::firstOrCreateAccount(self::CODE_COGS, [
            'name_ar' => 'تكلفة البضاعة المباعة',
            'name_en' => 'Cost of Goods Sold',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function ensureCogsRootForTenant(int $tenantUserId): Account
    {
        return self::firstOrCreateAccount(self::CODE_COGS, [
            'name_ar' => 'تكلفة البضاعة المباعة',
            'name_en' => 'Cost of Goods Sold',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    public static function ensureOperatingExpensesRoot(?int $userId = null): Account
    {
        return self::firstOrCreateAccount(self::CODE_OPERATING_EXPENSES, [
            'name_ar' => 'مصروفات التشغيل',
            'name_en' => 'Operating Expenses',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    public static function cashOnHand(): Account
    {
        $pid = self::ensureCurrentAssetsGroup()->id;

        return self::firstOrCreateAccount(self::CODE_CASH, [
            'name_ar' => 'صندوق النقدية',
            'name_en' => 'Cash on Hand',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function bankMain(): Account
    {
        $pid = self::ensureCurrentAssetsGroup()->id;

        return self::firstOrCreateAccount(self::CODE_BANK, [
            'name_ar' => 'البنك - الحساب الرئيسي',
            'name_en' => 'Bank - Main Account',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function accountsReceivable(): Account
    {
        $pid = self::ensureCurrentAssetsGroup()->id;

        return self::firstOrCreateAccount(self::CODE_AR, [
            'name_ar' => 'الذمم المدينة',
            'name_en' => 'Accounts Receivable',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function accountsReceivableForTenant(int $tenantUserId): Account
    {
        $pid = self::ensureCurrentAssetsGroupForTenant($tenantUserId)->id;

        return self::firstOrCreateAccount(self::CODE_AR, [
            'name_ar' => 'الذمم المدينة',
            'name_en' => 'Accounts Receivable',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    public static function inventoryRawMaterials(?int $userId = null): Account
    {
        $pid = self::ensureInventoryParent($userId)->id;

        return self::firstOrCreateAccount(self::CODE_RAW_MATERIALS_INV, [
            'name_ar' => 'مخزن الخامات',
            'name_en' => 'Raw Materials Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    public static function inventoryFinishedGoods(?int $userId = null): Account
    {
        $pid = self::ensureInventoryParent($userId)->id;

        return self::firstOrCreateAccount(self::CODE_FINISHED_GOODS_INV, [
            'name_ar' => 'مخزن المنتج التام',
            'name_en' => 'Finished Goods Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    public static function inventoryReceipts(): Account
    {
        $pid = self::ensureCurrentAssetsGroup()->id;

        return self::firstOrCreateAccount(self::CODE_INVENTORY_RECEIPTS, [
            'name_ar' => 'المخزون',
            'name_en' => 'Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function accountsPayable(): Account
    {
        $pid = self::ensureCurrentLiabilitiesGroup()->id;

        return self::firstOrCreateAccount(self::CODE_AP, [
            'name_ar' => 'الذمم الدائنة',
            'name_en' => 'Accounts Payable',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function vatPayable(): Account
    {
        $pid = self::ensureCurrentLiabilitiesGroup()->id;

        return self::firstOrCreateAccount(self::CODE_VAT_PAYABLE, [
            'name_ar' => 'ضريبة القيمة المضافة المستحقة',
            'name_en' => 'VAT Payable',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function salesRevenue(): Account
    {
        return self::firstOrCreateAccount(self::CODE_SALES_REVENUE, [
            'name_ar' => 'إيرادات المبيعات',
            'name_en' => 'Sales Revenue',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function salesRevenueForTenant(int $tenantUserId): Account
    {
        return self::firstOrCreateAccount(self::CODE_SALES_REVENUE, [
            'name_ar' => 'إيرادات المبيعات',
            'name_en' => 'Sales Revenue',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    public static function vatPayableForTenant(int $tenantUserId): Account
    {
        $pid = self::ensureCurrentLiabilitiesGroupForTenant($tenantUserId)->id;

        return self::firstOrCreateAccount(self::CODE_VAT_PAYABLE, [
            'name_ar' => 'ضريبة القيمة المضافة المستحقة',
            'name_en' => 'VAT Payable',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    private static function ensureCurrentLiabilitiesGroupForTenant(int $tenantUserId): Account
    {
        return self::firstOrCreateAccount(self::CODE_CURRENT_LIABILITIES, [
            'name_ar' => 'الخصوم المتداولة',
            'name_en' => 'Current Liabilities',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    public static function salesReturns(): Account
    {
        $parent = self::salesRevenue();

        return self::firstOrCreateAccount(self::CODE_SALES_RETURNS, [
            'name_ar' => 'مرتجعات المبيعات',
            'name_en' => 'Sales Returns',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function purchaseReturns(): Account
    {
        $parent = self::ensureCogsRoot();

        return self::firstOrCreateAccount(self::CODE_PURCHASE_RETURNS, [
            'name_ar' => 'مردودات المشتريات',
            'name_en' => 'Purchase Returns',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function scrapExpense(?int $userId = null): Account
    {
        $parent = self::ensureOperatingExpensesRoot($userId);

        return self::firstOrCreateAccount(self::CODE_SCRAP_EXPENSE, [
            'name_ar' => 'هالك وإتلاف',
            'name_en' => 'Scrap & Waste',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $parent->id,
            'opening_balance' => 0,
            'is_active' => true,
        ], $userId);
    }

    /**
     * يضمن حسابات قيد الإنتاج اللحظي لدليل مستأجر محدّد.
     */
    public static function provisionProductionEntryLedger(int $tenantUserId): void
    {
        self::inventoryRawMaterials($tenantUserId);
        self::inventoryFinishedGoods($tenantUserId);
        self::scrapExpense($tenantUserId);
    }

    /**
     * حساب نقدية/بنك للصرف أو الدفع حسب طريقة السداد.
     */
    public static function paymentSourceAsset(string $paymentMethod): Account
    {
        return $paymentMethod === 'cash'
            ? self::cashOnHand()
            : self::bankMain();
    }

    /**
     * مصدر الدفع الافتراضي لمالك دليل محدد (مثلاً عندما يعرض المستخدم 1 مصروف مستخدم آخر).
     */
    public static function paymentSourceAssetForTenant(string $paymentMethod, int $tenantUserId): Account
    {
        return $paymentMethod === 'cash'
            ? self::cashOnHandForTenant($tenantUserId)
            : self::bankMainForTenant($tenantUserId);
    }

    private static function ensureCurrentAssetsGroupForTenant(int $tenantUserId): Account
    {
        return self::firstOrCreateAccount(self::CODE_CURRENT_ASSETS, [
            'name_ar' => 'الأصول المتداولة',
            'name_en' => 'Current Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    private static function cashOnHandForTenant(int $tenantUserId): Account
    {
        $pid = self::ensureCurrentAssetsGroupForTenant($tenantUserId)->id;

        return self::firstOrCreateAccount(self::CODE_CASH, [
            'name_ar' => 'صندوق النقدية',
            'name_en' => 'Cash on Hand',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }

    private static function bankMainForTenant(int $tenantUserId): Account
    {
        $pid = self::ensureCurrentAssetsGroupForTenant($tenantUserId)->id;

        return self::firstOrCreateAccount(self::CODE_BANK, [
            'name_ar' => 'البنك - الحساب الرئيسي',
            'name_en' => 'Bank - Main Account',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $pid,
            'opening_balance' => 0,
            'is_active' => true,
        ], $tenantUserId);
    }
}
