<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_categories')) {
            Schema::create('item_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('name_ar', 150)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'mobile')) {
                $table->string('mobile', 30)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->unsignedInteger('payment_terms_days')->nullable()->after('credit_limit');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'reorder_level')) {
                $table->decimal('reorder_level', 15, 4)->default(0)->after('min_stock_level');
            }

            if (! Schema::hasColumn('items', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('sku');
            }
        });

        // Add FK only when the table/columns exist and no foreign key yet.
        if (
            Schema::hasTable('item_categories')
            && Schema::hasTable('items')
            && Schema::hasColumn('items', 'category_id')
        ) {
            try {
                Schema::table('items', function (Blueprint $table) {
                    $table->index('category_id', 'items_category_id_idx');
                    $table->foreign('category_id', 'items_category_id_fk')
                        ->references('id')
                        ->on('item_categories')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Ignore if index/foreign key already exists in certain environments.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (Schema::hasColumn('items', 'category_id')) {
                    try {
                        $table->dropForeign('items_category_id_fk');
                    } catch (\Throwable $e) {
                    }

                    try {
                        $table->dropIndex('items_category_id_idx');
                    } catch (\Throwable $e) {
                    }
                }

                if (Schema::hasColumn('items', 'reorder_level')) {
                    $table->dropColumn('reorder_level');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'mobile')) {
                    $table->dropColumn('mobile');
                }
                if (Schema::hasColumn('customers', 'payment_terms_days')) {
                    $table->dropColumn('payment_terms_days');
                }
            });
        }

        Schema::dropIfExists('item_categories');
    }
};

