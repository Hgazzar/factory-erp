<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حسابات فرعية للمخزون (خامات / منتج تام) وربط القيود التلقائية بأوامر الإنتاج والتوريد.
     */
    public function up(): void
    {
        $parent = Account::query()->where('code', '1040')->first();
        if ($parent) {
            Account::firstOrCreate(
                ['code' => '1041'],
                [
                    'name_ar' => 'مخزن الخامات',
                    'name_en' => 'Raw Materials Inventory',
                    'type' => Account::TYPE_ASSET,
                    'parent_id' => $parent->id,
                    'opening_balance' => 0,
                    'is_bank' => false,
                    'is_active' => true,
                ]
            );
            Account::firstOrCreate(
                ['code' => '1042'],
                [
                    'name_ar' => 'مخزن المنتج التام',
                    'name_en' => 'Finished Goods Inventory',
                    'type' => Account::TYPE_ASSET,
                    'parent_id' => $parent->id,
                    'opening_balance' => 0,
                    'is_bank' => false,
                    'is_active' => true,
                ]
            );
        }

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });
    }
};
