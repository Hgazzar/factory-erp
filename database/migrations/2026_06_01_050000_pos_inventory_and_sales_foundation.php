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

                $table->unique(['user_id', 'name']);
                $table->index(['user_id', 'is_active']);
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

                $table->unique(['user_id', 'name']);
                $table->index(['user_id', 'is_active']);
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

                $table->unique(['user_id', 'sku']);
                $table->unique(['user_id', 'barcode']);
                $table->index(['user_id', 'is_active']);
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
                    $table->string('invoice_number', 48)->nullable()->after('receipt_number');
                }
                if (! Schema::hasColumn('pos_sales', 'subtotal_amount')) {
                    $table->decimal('subtotal_amount', 15, 4)->nullable()->after('total_price');
                }
                if (! Schema::hasColumn('pos_sales', 'vat_amount')) {
                    $table->decimal('vat_amount', 15, 4)->nullable()->after('subtotal_amount');
                }
                if (! Schema::hasColumn('pos_sales', 'total_amount')) {
                    $table->decimal('total_amount', 15, 4)->nullable()->after('vat_amount');
                }
                if (! Schema::hasColumn('pos_sales', 'cogs_amount')) {
                    $table->decimal('cogs_amount', 15, 4)->nullable()->after('total_amount');
                }
            });

            if (! $this->indexExists('pos_sales', 'pos_sales_user_invoice_unique')) {
                Schema::table('pos_sales', function (Blueprint $table) {
                    $table->unique(['user_id', 'invoice_number'], 'pos_sales_user_invoice_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            if ($this->indexExists('pos_sales', 'pos_sales_user_invoice_unique')) {
                Schema::table('pos_sales', function (Blueprint $table) {
                    $table->dropUnique('pos_sales_user_invoice_unique');
                });
            }

            Schema::table('pos_sales', function (Blueprint $table) {
                $drops = [];
                foreach (['invoice_number', 'subtotal_amount', 'vat_amount', 'total_amount', 'cogs_amount'] as $column) {
                    if (Schema::hasColumn('pos_sales', $column)) {
                        $drops[] = $column;
                    }
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }

        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_products');
        Schema::dropIfExists('pos_product_brands');
        Schema::dropIfExists('pos_product_categories');
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getConfig('schema') ?? 'public';
        $driver = $connection->getDriverName();

        return match ($driver) {
            'pgsql' => (bool) $connection->selectOne(
                'select 1 from pg_indexes where schemaname = ? and tablename = ? and indexname = ?',
                [$schema, $table, $index]
            ),
            'mysql' => (bool) $connection->selectOne(
                'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
                [$table, $index]
            ),
            'sqlite' => false,
            default => false,
        };
    }
};
