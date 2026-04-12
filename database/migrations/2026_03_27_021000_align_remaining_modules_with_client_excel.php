<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'allow_direct_posting')) {
                $table->boolean('allow_direct_posting')->default(true)->after('is_active');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'order_number')) {
                $table->string('order_number', 50)->nullable()->after('id');
                $table->unique('order_number', 'sales_orders_order_number_unique');
            }
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_order_items', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 4)->default(0)->after('tax_percent');
            }

            if (! Schema::hasColumn('sales_order_items', 'total_amount')) {
                $table->decimal('total_amount', 15, 4)->default(0)->after('line_total');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'order_number')) {
                $table->string('order_number', 50)->nullable()->after('id');
                $table->unique('order_number', 'purchase_orders_order_number_unique');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 4)->default(0)->after('tax_percent');
            }

            if (! Schema::hasColumn('purchase_order_items', 'total_amount')) {
                $table->decimal('total_amount', 15, 4)->default(0)->after('line_total');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'expense_number')) {
                $table->string('expense_number', 50)->nullable()->after('id');
                $table->unique('expense_number', 'payments_expense_number_unique');
            }

            if (! Schema::hasColumn('payments', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('expense_account_id');
                $table->index('category_id', 'payments_category_id_idx');
            }

            if (! Schema::hasColumn('payments', 'total_amount')) {
                $table->decimal('total_amount', 15, 4)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('payments', 'status')) {
                $table->string('status', 30)->default('draft')->after('type');
            }
        });

        if (
            Schema::hasTable('payments')
            && Schema::hasTable('accounts')
            && Schema::hasColumn('payments', 'category_id')
        ) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreign('category_id', 'payments_category_id_fk')
                        ->references('id')
                        ->on('accounts')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Ignore if FK already exists in a specific environment.
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'category_id')) {
                try {
                    $table->dropForeign('payments_category_id_fk');
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropIndex('payments_category_id_idx');
                } catch (\Throwable $e) {
                }

                $table->dropColumn('category_id');
            }

            if (Schema::hasColumn('payments', 'expense_number')) {
                try {
                    $table->dropUnique('payments_expense_number_unique');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('expense_number');
            }

            if (Schema::hasColumn('payments', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
            if (Schema::hasColumn('purchase_order_items', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'order_number')) {
                try {
                    $table->dropUnique('purchase_orders_order_number_unique');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('order_number');
            }
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_order_items', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
            if (Schema::hasColumn('sales_order_items', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'order_number')) {
                try {
                    $table->dropUnique('sales_orders_order_number_unique');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('order_number');
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'allow_direct_posting')) {
                $table->dropColumn('allow_direct_posting');
            }
        });
    }
};

