<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('raw_materials_warehouse_id')
                ->nullable()
                ->after('end_date')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->foreignId('finished_goods_warehouse_id')
                ->nullable()
                ->after('raw_materials_warehouse_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('sales_order_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['raw_materials_warehouse_id']);
            $table->dropForeign(['finished_goods_warehouse_id']);
            $table->dropColumn(['raw_materials_warehouse_id', 'finished_goods_warehouse_id']);
        });
    }
};
