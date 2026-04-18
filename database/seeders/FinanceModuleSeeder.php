<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * حسابات إضافية لمصنع حلويات/سكاكر (تكميلية على دليل الحسابات الأساسي).
 * آمن للتشغيل المتكرر: firstOrCreate حسب user_id + code.
 */
class FinanceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        $parent = static fn (string $code) => Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->value('id');

        $ensure = static function (int $userId, string $code, array $attrs): void {
            Account::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $userId, 'code' => $code],
                array_merge([
                    'name_ar' => $code,
                    'name_en' => null,
                    'type' => Account::TYPE_EXPENSE,
                    'parent_id' => null,
                    'opening_balance' => 0,
                    'is_bank' => false,
                    'is_active' => true,
                    'allow_direct_posting' => true,
                ], $attrs)
            );
        };

        $p4000 = $parent('4000');
        if ($p4000) {
            $ensure($userId, '4011', ['name_ar' => 'إيرادات حلويات جملة', 'name_en' => 'Wholesale confectionery revenue', 'type' => Account::TYPE_REVENUE, 'parent_id' => $p4000]);
            $ensure($userId, '4012', ['name_ar' => 'إيرادات بقالة وتجزئة', 'name_en' => 'Grocery & retail candy revenue', 'type' => Account::TYPE_REVENUE, 'parent_id' => $p4000]);
            $ensure($userId, '4013', ['name_ar' => 'إيرادات تصدير', 'name_en' => 'Export sales revenue', 'type' => Account::TYPE_REVENUE, 'parent_id' => $p4000]);
        }

        $p5000 = $parent('5000');
        if ($p5000) {
            $ensure($userId, '5110', ['name_ar' => 'شراء سكر ومحليات', 'name_en' => 'Sugar & sweeteners purchases', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
            $ensure($userId, '5120', ['name_ar' => 'شراء شوكولاتة وكاكاو', 'name_en' => 'Chocolate & cocoa purchases', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
            $ensure($userId, '5130', ['name_ar' => 'تعبئة وتغليف (ورق/أكياس/علب)', 'name_en' => 'Packaging materials', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
            $ensure($userId, '5140', ['name_ar' => 'كهرباء وماء المصنع', 'name_en' => 'Factory utilities', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
            $ensure($userId, '5150', ['name_ar' => 'تسويق ومعارض حلويات', 'name_en' => 'Marketing & trade fairs', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
            $ensure($userId, '5160', ['name_ar' => 'صيانة خطوط الإنتاج', 'name_en' => 'Production line maintenance', 'type' => Account::TYPE_EXPENSE, 'parent_id' => $p5000]);
        }

        $p1040 = $parent('1040');
        if ($p1040) {
            $ensure($userId, '1043', ['name_ar' => 'مخزون مواد تغليف', 'name_en' => 'Packaging inventory', 'type' => Account::TYPE_ASSET, 'parent_id' => $p1040]);
        }

        $p3000 = $parent('3000');
        if ($p3000) {
            $ensure($userId, '3010', ['name_ar' => 'جاري الشركاء', 'name_en' => 'Partners current account', 'type' => Account::TYPE_EQUITY, 'parent_id' => $p3000]);
            $ensure($userId, '3020', ['name_ar' => 'احتياطي قانوني', 'name_en' => 'Legal reserve', 'type' => Account::TYPE_EQUITY, 'parent_id' => $p3000]);
        }
    }
}
