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
                if (! Schema::hasColumn('pos_sales', 'fulfillment_mode')) {
                    $table->string('fulfillment_mode', 32)->nullable()->after('sale_channel');
                }
                if (! Schema::hasColumn('pos_sales', 'fulfillment_status')) {
                    $table->string('fulfillment_status', 32)->nullable()->after('fulfillment_mode');
                }
                if (! Schema::hasColumn('pos_sales', 'assigned_agent_id')) {
                    $table->unsignedBigInteger('assigned_agent_id')->nullable()->after('fulfillment_status');
                }
                if (! Schema::hasColumn('pos_sales', 'fleet_customer_id')) {
                    $table->unsignedBigInteger('fleet_customer_id')->nullable()->after('assigned_agent_id');
                }
            });

            if (Schema::hasTable('fleet_agents')) {
                Schema::table('pos_sales', function (Blueprint $table): void {
                    if (Schema::hasColumn('pos_sales', 'assigned_agent_id')) {
                        $table->foreign('assigned_agent_id')
                            ->references('id')
                            ->on('fleet_agents')
                            ->nullOnDelete();
                    }
                });
            }

            if (Schema::hasTable('fleet_customers')) {
                Schema::table('pos_sales', function (Blueprint $table): void {
                    if (Schema::hasColumn('pos_sales', 'fleet_customer_id')) {
                        $table->foreign('fleet_customer_id')
                            ->references('id')
                            ->on('fleet_customers')
                            ->nullOnDelete();
                    }
                });
            }
        }

        if (Schema::hasTable('fleet_route_stops') && ! Schema::hasColumn('fleet_route_stops', 'pos_sale_id')) {
            Schema::table('fleet_route_stops', function (Blueprint $table): void {
                $table->unsignedBigInteger('pos_sale_id')->nullable()->after('customer_id');
                $table->foreign('pos_sale_id')->references('id')->on('pos_sales')->nullOnDelete();
                $table->index(['user_id', 'pos_sale_id']);
            });
        }

        if (Schema::hasTable('fleet_products') && ! Schema::hasColumn('fleet_products', 'pos_product_id')) {
            Schema::table('fleet_products', function (Blueprint $table): void {
                $table->unsignedBigInteger('pos_product_id')->nullable()->after('user_id');
                $table->foreign('pos_product_id')->references('id')->on('pos_products')->nullOnDelete();
            });
        }

        if (Schema::hasTable('tenant_store_settings') && ! Schema::hasColumn('tenant_store_settings', 'field_delivery_enabled')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                $table->boolean('field_delivery_enabled')->default(false)->after('cod_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_store_settings') && Schema::hasColumn('tenant_store_settings', 'field_delivery_enabled')) {
            Schema::table('tenant_store_settings', function (Blueprint $table): void {
                $table->dropColumn('field_delivery_enabled');
            });
        }

        if (Schema::hasTable('fleet_products') && Schema::hasColumn('fleet_products', 'pos_product_id')) {
            Schema::table('fleet_products', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('pos_product_id');
            });
        }

        if (Schema::hasTable('fleet_route_stops') && Schema::hasColumn('fleet_route_stops', 'pos_sale_id')) {
            Schema::table('fleet_route_stops', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('pos_sale_id');
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                foreach (['fleet_customer_id', 'assigned_agent_id'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropForeign([$col]);
                    }
                }
                foreach (['fleet_customer_id', 'assigned_agent_id', 'fulfillment_status', 'fulfillment_mode'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
