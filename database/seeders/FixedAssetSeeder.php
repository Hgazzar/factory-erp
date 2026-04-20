<?php

namespace Database\Seeders;

use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixedAssetSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('fixed_assets')->truncate();
        Schema::enableForeignKeyConstraints();

        $facId = FixedAssetCategory::ensureDefaultForUser(1)->id;
        $ledgerId = (int) FixedAssetCategory::withoutGlobalScopes()->whereKey($facId)->value('ledger_asset_account_id');

        $rows = [
            ['asset_code' => 'FA-001', 'name' => 'ماكينة تعبئة أوتوماتيكية', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'معدات إنتاج', 'acquisition_date' => '2023-02-15', 'acquisition_cost' => 250000, 'book_value' => 198000, 'status' => 'in_use'],
            ['asset_code' => 'FA-002', 'name' => 'رافعة شوكية 3 طن', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'معدات مناولة', 'acquisition_date' => '2022-07-01', 'acquisition_cost' => 95000, 'book_value' => 62000, 'status' => 'in_use'],
            ['asset_code' => 'FA-003', 'name' => 'مولد كهرباء احتياطي', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'معدات تشغيل', 'acquisition_date' => '2021-11-20', 'acquisition_cost' => 180000, 'book_value' => 99000, 'status' => 'stopped'],
            ['asset_code' => 'FA-004', 'name' => 'سيارة توزيع', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'مركبات', 'acquisition_date' => '2020-05-12', 'acquisition_cost' => 140000, 'book_value' => 52000, 'status' => 'in_use'],
            ['asset_code' => 'FA-005', 'name' => 'نظام كاميرات المراقبة', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'أمن وسلامة', 'acquisition_date' => '2024-01-08', 'acquisition_cost' => 38000, 'book_value' => 33000, 'status' => 'in_use'],
            ['asset_code' => 'FA-006', 'name' => 'خط تغليف قديم', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'معدات إنتاج', 'acquisition_date' => '2018-03-02', 'acquisition_cost' => 210000, 'book_value' => 0, 'status' => 'decommissioned'],
            ['asset_code' => 'FA-007', 'name' => 'أجهزة حاسب للإدارة', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'أجهزة تقنية', 'acquisition_date' => '2024-09-10', 'acquisition_cost' => 42000, 'book_value' => 37000, 'status' => 'in_use'],
            ['asset_code' => 'FA-008', 'name' => 'وحدة تكييف مركزية', 'fixed_asset_category_id' => $facId, 'ledger_account_id' => $ledgerId, 'category' => 'مباني ومرافق', 'acquisition_date' => '2019-06-17', 'acquisition_cost' => 88000, 'book_value' => 21000, 'status' => 'stopped'],
        ];

        foreach ($rows as $row) {
            FixedAsset::query()->create($row);
        }
    }
}
