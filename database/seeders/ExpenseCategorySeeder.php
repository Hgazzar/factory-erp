<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('expense_categories')->truncate();
        Schema::enableForeignKeyConstraints();

        $ids = [];
        $tenantUserId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        $categories = [
            ['code' => 'EXP-000', 'name_ar' => 'مصروفات تشغيلية', 'name_en' => 'Operating Expenses', 'parent_code' => null, 'is_taxable' => false, 'status' => 'active'],
            ['code' => 'EXP-001', 'name_ar' => 'مستلزمات مكتبية', 'name_en' => 'Office Supplies', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-002', 'name_ar' => 'المرافق', 'name_en' => 'Utilities', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-003', 'name_ar' => 'الإيجار', 'name_en' => 'Rent', 'parent_code' => 'EXP-000', 'is_taxable' => false, 'status' => 'active'],
            ['code' => 'EXP-004', 'name_ar' => 'السفر والمواصلات', 'name_en' => 'Travel & Transportation', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-005', 'name_ar' => 'الرواتب والأجور', 'name_en' => 'Salaries & Wages', 'parent_code' => 'EXP-000', 'is_taxable' => false, 'status' => 'active'],
            ['code' => 'EXP-006', 'name_ar' => 'الصيانة والإصلاحات', 'name_en' => 'Maintenance & Repairs', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-007', 'name_ar' => 'التسويق والإعلان', 'name_en' => 'Marketing & Advertising', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-008', 'name_ar' => 'التأمين', 'name_en' => 'Insurance', 'parent_code' => 'EXP-000', 'is_taxable' => false, 'status' => 'active'],
            ['code' => 'EXP-009', 'name_ar' => 'الخدمات المهنية', 'name_en' => 'Professional Services', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-010', 'name_ar' => 'مصروفات متنوعة', 'name_en' => 'Miscellaneous', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-011', 'name_ar' => 'اشتراكات الأنظمة', 'name_en' => 'Software Subscriptions', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'inactive'],
            ['code' => 'EXP-012', 'name_ar' => 'الاتصالات والإنترنت', 'name_en' => 'Telecom & Internet', 'parent_code' => 'EXP-000', 'is_taxable' => true, 'status' => 'active'],
            ['code' => 'EXP-013', 'name_ar' => 'ضيافة واستقبال', 'name_en' => 'Hospitality', 'parent_code' => 'EXP-000', 'is_taxable' => false, 'status' => 'active'],
        ];

        foreach ($categories as $row) {
            $parentId = null;
            if (!empty($row['parent_code']) && isset($ids[$row['parent_code']])) {
                $parentId = $ids[$row['parent_code']];
            }

            $category = ExpenseCategory::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'code' => $row['code'],
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'],
                'parent_id' => $parentId,
                'is_taxable' => $row['is_taxable'],
                'status' => $row['status'],
            ]);

            $ids[$row['code']] = $category->id;
        }
    }
}
