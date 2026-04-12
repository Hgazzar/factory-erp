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

        $id4000 = DB::table('accounts')->where('code', '4000')->value('id');
        if ($id4000 && ! DB::table('accounts')->where('code', '4050')->exists()) {
            DB::table('accounts')->insert([
                'code' => '4050',
                'name_ar' => 'مرتجعات المبيعات',
                'name_en' => 'Sales Returns',
                'type' => 'revenue',
                'parent_id' => $id4000,
                'opening_balance' => 0,
                'is_bank' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $id5000 = DB::table('accounts')->where('code', '5000')->value('id');
        if ($id5000 && ! DB::table('accounts')->where('code', '5050')->exists()) {
            DB::table('accounts')->insert([
                'code' => '5050',
                'name_ar' => 'مردودات المشتريات',
                'name_en' => 'Purchase Returns',
                'type' => 'expense',
                'parent_id' => $id5000,
                'opening_balance' => 0,
                'is_bank' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $id6000 = DB::table('accounts')->where('code', '6000')->value('id');
        if ($id6000 && ! DB::table('accounts')->where('code', '6060')->exists()) {
            DB::table('accounts')->insert([
                'code' => '6060',
                'name_ar' => 'هالك وإتلاف',
                'name_en' => 'Scrap & Waste',
                'type' => 'expense',
                'parent_id' => $id6000,
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
        DB::table('accounts')->whereIn('code', ['4050', '5050', '6060'])->delete();
    }
};
