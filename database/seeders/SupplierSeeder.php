<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * مورد تجريبي مرتبط بأول مستخدم (للمشتريات والاختبار).
     */
    public function run(): void
    {
        $demoUserId = (int) (User::query()->orderBy('id')->value('id') ?? 1);

        Supplier::withoutGlobalScopes()->updateOrCreate(
            ['code' => 'SUP-DEMO', 'user_id' => $demoUserId],
            [
                'name' => 'مورد تجريبي',
                'name_ar' => 'مورد تجريبي',
                'contact_name' => 'مندوب المشتريات',
                'phone' => '0500000002',
                'email' => 'demo.supplier@example.com',
                'address' => 'جدة',
                'supplier_type' => 'raw_materials',
                'is_active' => true,
            ]
        );
    }
}
