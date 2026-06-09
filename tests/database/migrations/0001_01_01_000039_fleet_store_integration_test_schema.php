<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                foreach (['fulfillment_mode', 'fulfillment_status'] as $col) {
                    if (! Schema::hasColumn('pos_sales', $col)) {
                        $table->string($col, 32)->nullable();
                    }
                }
                foreach (['assigned_agent_id', 'fleet_customer_id'] as $col) {
                    if (! Schema::hasColumn('pos_sales', $col)) {
                        $table->unsignedBigInteger($col)->nullable();
                    }
                }
            });
        }

        if (Schema::hasTable('fleet_route_stops') && ! Schema::hasColumn('fleet_route_stops', 'pos_sale_id')) {
            Schema::table('fleet_route_stops', function (Blueprint $table): void {
                $table->unsignedBigInteger('pos_sale_id')->nullable()->after('customer_id');
            });
        }

        if (Schema::hasTable('fleet_products') && ! Schema::hasColumn('fleet_products', 'pos_product_id')) {
            Schema::table('fleet_products', function (Blueprint $table): void {
                $table->unsignedBigInteger('pos_product_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('tenant_store_settings') && ! Schema::hasColumn('tenant_store_settings', 'field_delivery_enabled')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                $table->boolean('field_delivery_enabled')->default(false);
            });
        }
    }

    public function down(): void
    {
        // test schema — no down
    }
};
