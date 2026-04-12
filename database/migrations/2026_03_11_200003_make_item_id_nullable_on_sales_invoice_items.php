<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE sales_invoice_items ALTER COLUMN item_id DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE sales_invoice_items MODIFY item_id BIGINT UNSIGNED NULL');
        }
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE sales_invoice_items ALTER COLUMN item_id SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE sales_invoice_items MODIFY item_id BIGINT UNSIGNED NOT NULL');
        }
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }
};
