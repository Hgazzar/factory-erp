<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * Ensures each tenant user has the standard chart (same structure as AccountSeeder),
 * using firstOrCreate semantics on (user_id, code) without truncating data.
 */
final class ChartOfAccountsProvisioner
{
    /**
     * @return Collection<int, array{
     *     code: string,
     *     parent_code: ?string,
     *     name_ar: string,
     *     name_en: string,
     *     type: string,
     *     opening_balance: float,
     *     is_bank?: bool,
     * }>
     */
    public static function definitions(): Collection
    {
        $a = Account::TYPE_ASSET;
        $l = Account::TYPE_LIABILITY;
        $e = Account::TYPE_EXPENSE;
        $r = Account::TYPE_REVENUE;

        return collect([
            ['code' => '1000', 'parent_code' => null, 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1010', 'parent_code' => '1000', 'name_ar' => 'صندوق النقدية', 'name_en' => 'Cash on Hand', 'type' => $a, 'opening_balance' => 75000.00],
            ['code' => '1020', 'parent_code' => '1000', 'name_ar' => 'البنك - الحساب الرئيسي', 'name_en' => 'Bank - Main Account', 'type' => $a, 'opening_balance' => 250000.00, 'is_bank' => true],
            ['code' => '1030', 'parent_code' => '1000', 'name_ar' => 'الذمم المدينة', 'name_en' => 'Accounts Receivable', 'type' => $a, 'opening_balance' => 120000.00],
            ['code' => '1031', 'parent_code' => '1030', 'name_ar' => 'عملاء محليون', 'name_en' => 'Local Customers', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1032', 'parent_code' => '1030', 'name_ar' => 'عملاء مشاريع', 'name_en' => 'Project Customers', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1040', 'parent_code' => '1000', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => $a, 'opening_balance' => 180000.00],
            ['code' => '1041', 'parent_code' => '1040', 'name_ar' => 'مخزن الخامات', 'name_en' => 'Raw Materials Inventory', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1042', 'parent_code' => '1040', 'name_ar' => 'مخزن المنتج التام', 'name_en' => 'Finished Goods Inventory', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1200', 'parent_code' => '1000', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1500', 'parent_code' => null, 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => $a, 'opening_balance' => 0],
            ['code' => '1510', 'parent_code' => '1500', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => $a, 'opening_balance' => -35000.00],
            ['code' => '2000', 'parent_code' => null, 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => $l, 'opening_balance' => 0],
            ['code' => '2010', 'parent_code' => '2000', 'name_ar' => 'الذمم الدائنة', 'name_en' => 'Accounts Payable', 'type' => $l, 'opening_balance' => -85000.00],
            ['code' => '2030', 'parent_code' => '2000', 'name_ar' => 'ضريبة القيمة المضافة المستحقة', 'name_en' => 'VAT Payable', 'type' => $l, 'opening_balance' => 0],
            ['code' => '2020', 'parent_code' => '2000', 'name_ar' => 'قروض قصيرة الأجل', 'name_en' => 'Short-term Loans', 'type' => $l, 'opening_balance' => -100000.00],
            ['code' => '3000', 'parent_code' => null, 'name_ar' => 'حقوق الملكية', 'name_en' => 'Owner\'s Equity', 'type' => $l, 'opening_balance' => -420000.00],
            ['code' => '4000', 'parent_code' => null, 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue', 'type' => $r, 'opening_balance' => 0],
            ['code' => '4050', 'parent_code' => '4000', 'name_ar' => 'مرتجعات المبيعات', 'name_en' => 'Sales Returns', 'type' => $r, 'opening_balance' => 0],
            ['code' => '4100', 'parent_code' => '4000', 'name_ar' => 'إيرادات الخدمات', 'name_en' => 'Service Revenue', 'type' => $r, 'opening_balance' => 0],
            ['code' => '4200', 'parent_code' => '4000', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => $r, 'opening_balance' => 0],
            ['code' => '5000', 'parent_code' => null, 'name_ar' => 'تكلفة البضاعة المباعة', 'name_en' => 'Cost of Goods Sold', 'type' => $e, 'opening_balance' => 0],
            ['code' => '5050', 'parent_code' => '5000', 'name_ar' => 'مردودات المشتريات', 'name_en' => 'Purchase Returns', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6000', 'parent_code' => null, 'name_ar' => 'مصروفات التشغيل', 'name_en' => 'Operating Expenses', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6060', 'parent_code' => '6000', 'name_ar' => 'هالك وإتلاف', 'name_en' => 'Scrap & Waste', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6100', 'parent_code' => '6000', 'name_ar' => 'الرواتب والأجور', 'name_en' => 'Salaries & Wages', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6200', 'parent_code' => '6000', 'name_ar' => 'مصروف الإيجار', 'name_en' => 'Rent Expense', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6300', 'parent_code' => '6000', 'name_ar' => 'مصروف المرافق', 'name_en' => 'Utilities Expense', 'type' => $e, 'opening_balance' => 0],
            ['code' => '6400', 'parent_code' => '6000', 'name_ar' => 'مستلزمات المكتب', 'name_en' => 'Office Supplies', 'type' => $e, 'opening_balance' => 0],
        ]);
    }

    public static function ensureForUser(int $userId): void
    {
        $byCode = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('code');

        foreach (self::definitions() as $def) {
            $parentId = null;
            if ($def['parent_code'] !== null) {
                $parentId = $byCode->get($def['parent_code'])?->id;
            }

            $existing = $byCode->get($def['code']);

            if ($existing === null) {
                $account = Account::withoutGlobalScopes()->create([
                    'user_id' => $userId,
                    'code' => $def['code'],
                    'name_ar' => $def['name_ar'],
                    'name_en' => $def['name_en'],
                    'type' => $def['type'],
                    'parent_id' => $parentId,
                    'opening_balance' => $def['opening_balance'],
                    'is_bank' => $def['is_bank'] ?? false,
                    'is_active' => true,
                    'allow_direct_posting' => true,
                ]);
                $byCode->put($def['code'], $account);

                continue;
            }

            $existing->fill([
                'name_ar' => $def['name_ar'],
                'name_en' => $def['name_en'],
                'type' => $def['type'],
                'parent_id' => $parentId,
                'is_bank' => $def['is_bank'] ?? false,
                'is_active' => true,
                'allow_direct_posting' => true,
            ]);

            if ($existing->isDirty()) {
                $existing->saveQuietly();
            }
        }
    }
}
