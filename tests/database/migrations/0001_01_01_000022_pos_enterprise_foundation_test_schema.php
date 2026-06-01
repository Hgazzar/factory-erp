<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_product_categories')) {
            Schema::create('pos_product_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 32)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_product_brands')) {
            Schema::create('pos_product_brands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 32)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_products')) {
            Schema::create('pos_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_product_category_id')->nullable()->constrained('pos_product_categories')->nullOnDelete();
                $table->foreignId('pos_product_brand_id')->nullable()->constrained('pos_product_brands')->nullOnDelete();
                $table->string('name');
                $table->string('sku', 64)->nullable();
                $table->string('barcode', 64)->nullable();
                $table->text('description')->nullable();
                $table->decimal('cost_price', 15, 4)->default(0);
                $table->decimal('sale_price', 15, 4)->default(0);
                $table->decimal('vat_percent', 8, 4)->default(0);
                $table->decimal('opening_quantity', 15, 4)->default(0);
                $table->decimal('current_quantity', 15, 4)->default(0);
                $table->decimal('low_stock_alert_quantity', 15, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_sale_items')) {
            Schema::create('pos_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
                $table->foreignId('pos_product_id')->constrained('pos_products')->cascadeOnDelete();
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_cost', 15, 4)->default(0);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('vat_percent', 8, 4)->default(0);
                $table->decimal('vat_amount', 15, 4)->default(0);
                $table->decimal('line_subtotal', 15, 4);
                $table->decimal('line_total', 15, 4);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'invoice_number')) {
                    $table->string('invoice_number', 48)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'subtotal_amount')) {
                    $table->decimal('subtotal_amount', 15, 4)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'vat_amount')) {
                    $table->decimal('vat_amount', 15, 4)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'total_amount')) {
                    $table->decimal('total_amount', 15, 4)->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'cogs_amount')) {
                    $table->decimal('cogs_amount', 15, 4)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_products');
        Schema::dropIfExists('pos_product_brands');
        Schema::dropIfExists('pos_product_categories');
    }
};
