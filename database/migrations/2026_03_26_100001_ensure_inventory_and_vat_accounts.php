<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $parent1000 = DB::table('accounts')->where('code', '1000')->value('id');
        if ($parent1000 && ! DB::table('accounts')->where('code', '1200')->exists()) {
            DB::table('accounts')->insert([
                'code' => '1200',
                'name_ar' => 'المخزون',
                'name_en' => 'Inventory',
                'type' => 'asset',
                'parent_id' => $parent1000,
                'opening_balance' => 0,
                'is_bank' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $parent2000 = DB::table('accounts')->where('code', '2000')->value('id');
        if ($parent2000 && ! DB::table('accounts')->where('code', '2030')->exists()) {
            DB::table('accounts')->insert([
                'code' => '2030',
                'name_ar' => 'ضريبة القيمة المضافة المستحقة',
                'name_en' => 'VAT Payable',
                'type' => 'liability',
                'parent_id' => $parent2000,
                'opening_balance' => 0,
                'is_bank' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['1200', '2030'])->delete();
    }
};
