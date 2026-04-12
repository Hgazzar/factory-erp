<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        Schema::disableForeignKeyConstraints();
        \DB::table('journal_items')->truncate();
        \DB::table('journal_entries')->truncate();
        \DB::table('accounts')->truncate();
        Schema::enableForeignKeyConstraints();

        $ids = [];

        // ─── أصول (1xxx) ───
        $ids['1000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1000',
            'name_ar' => 'الأصول المتداولة',
            'name_en' => 'Current Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['1010'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1010',
            'name_ar' => 'صندوق النقدية',
            'name_en' => 'Cash on Hand',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1000'],
            'opening_balance' => 75000.00,
        ])->id;

        $ids['1020'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1020',
            'name_ar' => 'البنك - الحساب الرئيسي',
            'name_en' => 'Bank - Main Account',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1000'],
            'opening_balance' => 250000.00,
        ])->id;

        $ids['1030'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1030',
            'name_ar' => 'الذمم المدينة',
            'name_en' => 'Accounts Receivable',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1000'],
            'opening_balance' => 120000.00,
        ])->id;

        $ids['1040'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1040',
            'name_ar' => 'المخزون',
            'name_en' => 'Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1000'],
            'opening_balance' => 180000.00,
        ])->id;

        $ids['1041'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1041',
            'name_ar' => 'مخزن الخامات',
            'name_en' => 'Raw Materials Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1040'],
            'opening_balance' => 0,
        ])->id;

        $ids['1042'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1042',
            'name_ar' => 'مخزن المنتج التام',
            'name_en' => 'Finished Goods Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1040'],
            'opening_balance' => 0,
        ])->id;

        $ids['1200'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1200',
            'name_ar' => 'المخزون',
            'name_en' => 'Inventory',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1000'],
            'opening_balance' => 0,
        ])->id;

        $ids['1500'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1500',
            'name_ar' => 'الأصول الثابتة',
            'name_en' => 'Fixed Assets',
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['1510'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '1510',
            'name_ar' => 'مجمع الإهلاك',
            'name_en' => 'Accumulated Depreciation',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $ids['1500'],
            'opening_balance' => -35000.00,
        ])->id;

        // ─── خصوم (2xxx) ───
        $ids['2000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '2000',
            'name_ar' => 'الخصوم المتداولة',
            'name_en' => 'Current Liabilities',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['2010'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '2010',
            'name_ar' => 'الذمم الدائنة',
            'name_en' => 'Accounts Payable',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $ids['2000'],
            'opening_balance' => -85000.00,
        ])->id;

        $ids['2030'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '2030',
            'name_ar' => 'ضريبة القيمة المضافة المستحقة',
            'name_en' => 'VAT Payable',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $ids['2000'],
            'opening_balance' => 0,
        ])->id;

        $ids['2020'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '2020',
            'name_ar' => 'قروض قصيرة الأجل',
            'name_en' => 'Short-term Loans',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $ids['2000'],
            'opening_balance' => -100000.00,
        ])->id;

        $ids['3000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '3000',
            'name_ar' => 'حقوق الملكية',
            'name_en' => 'Owner\'s Equity',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => null,
            'opening_balance' => -420000.00,
        ])->id;

        // ─── إيرادات (4xxx) ───
        $ids['4000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '4000',
            'name_ar' => 'إيرادات المبيعات',
            'name_en' => 'Sales Revenue',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['4050'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '4050',
            'name_ar' => 'مرتجعات المبيعات',
            'name_en' => 'Sales Returns',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => $ids['4000'],
            'opening_balance' => 0,
        ])->id;

        $ids['4100'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '4100',
            'name_ar' => 'إيرادات الخدمات',
            'name_en' => 'Service Revenue',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => $ids['4000'],
            'opening_balance' => 0,
        ])->id;

        $ids['4200'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '4200',
            'name_ar' => 'إيرادات أخرى',
            'name_en' => 'Other Revenue',
            'type' => Account::TYPE_REVENUE,
            'parent_id' => $ids['4000'],
            'opening_balance' => 0,
        ])->id;

        // ─── مصروفات (5xxx) ───
        $ids['5000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '5000',
            'name_ar' => 'تكلفة البضاعة المباعة',
            'name_en' => 'Cost of Goods Sold',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['5050'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '5050',
            'name_ar' => 'مردودات المشتريات',
            'name_en' => 'Purchase Returns',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['5000'],
            'opening_balance' => 0,
        ])->id;

        $ids['6000'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6000',
            'name_ar' => 'مصروفات التشغيل',
            'name_en' => 'Operating Expenses',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => null,
            'opening_balance' => 0,
        ])->id;

        $ids['6060'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6060',
            'name_ar' => 'هالك وإتلاف',
            'name_en' => 'Scrap & Waste',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['6000'],
            'opening_balance' => 0,
        ])->id;

        $ids['6100'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6100',
            'name_ar' => 'الرواتب والأجور',
            'name_en' => 'Salaries & Wages',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['6000'],
            'opening_balance' => 0,
        ])->id;

        $ids['6200'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6200',
            'name_ar' => 'مصروف الإيجار',
            'name_en' => 'Rent Expense',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['6000'],
            'opening_balance' => 0,
        ])->id;

        $ids['6300'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6300',
            'name_ar' => 'مصروف المرافق',
            'name_en' => 'Utilities Expense',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['6000'],
            'opening_balance' => 0,
        ])->id;

        $ids['6400'] = Account::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => '6400',
            'name_ar' => 'مستلزمات المكتب',
            'name_en' => 'Office Supplies',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $ids['6000'],
            'opening_balance' => 0,
        ])->id;
    }
}
