<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 4)->default(0)->after('unit_price');
            }
        });

        if (Schema::hasColumn('quotation_items', 'subtotal')) {
            DB::statement('UPDATE quotation_items SET subtotal = ROUND((quantity::numeric * unit_price::numeric) * (1 - discount_percent::numeric / 100), 4)');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'subtotal')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->dropColumn('subtotal');
            });
        }
    }
};
