<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('cost_centers')->truncate();
        Schema::enableForeignKeyConstraints();

        $tenantUserId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        $rows = [
            ['code' => 'CC-001', 'name' => 'قسم التسويق', 'branch' => 'الرياض', 'annual_budget' => 250000, 'status' => 'active'],
            ['code' => 'CC-002', 'name' => 'الإدارة العامة', 'branch' => 'الرياض', 'annual_budget' => 420000, 'status' => 'active'],
            ['code' => 'CC-003', 'name' => 'عمليات المبيعات', 'branch' => 'جدة', 'annual_budget' => 310000, 'status' => 'active'],
            ['code' => 'CC-004', 'name' => 'الدعم الفني', 'branch' => 'الدمام', 'annual_budget' => 180000, 'status' => 'active'],
            ['code' => 'CC-005', 'name' => 'اللوجستيات', 'branch' => 'جدة', 'annual_budget' => 225000, 'status' => 'inactive'],
            ['code' => 'CC-006', 'name' => 'الموارد البشرية', 'branch' => 'الرياض', 'annual_budget' => 165000, 'status' => 'active'],
        ];

        foreach ($rows as $row) {
            CostCenter::withoutGlobalScopes()->create(array_merge($row, ['user_id' => $tenantUserId]));
        }
    }
}
