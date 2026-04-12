<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'sku')) {
                $table->string('sku', 100)->nullable()->after('barcode')->comment('SKU/Barcode');
            }

            if (! Schema::hasColumn('items', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('sku')->comment('تصنيف الصنف');
            }

            if (! Schema::hasColumn('items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('category_id')->comment('الوحدة (kg, piece, ...)');
            }

            if (! Schema::hasColumn('items', 'purchase_price')) {
                $table->decimal('purchase_price', 15, 4)->default(0)->after('cost')->comment('سعر الشراء');
            }

            if (! Schema::hasColumn('items', 'sale_price')) {
                $table->decimal('sale_price', 15, 4)->default(0)->after('selling_price')->comment('سعر البيع');
            }

            if (! Schema::hasColumn('items', 'min_stock_level')) {
                $table->decimal('min_stock_level', 15, 4)->default(0)->after('min_stock')->comment('الحد الأدنى للمخزون (تنبيه)');
            }

            if (! Schema::hasColumn('items', 'current_stock')) {
                $table->decimal('current_stock', 15, 4)->default(0)->after('type')->comment('الرصيد الحالي للصنف');
            }
        });

        // تهيئة القيم الجديدة من الأعمدة القديمة إن وجدت
        if (Schema::hasColumn('items', 'sku') && Schema::hasColumn('items', 'barcode')) {
            DB::table('items')
                ->whereNull('sku')
                ->update(['sku' => DB::raw('barcode')]);
        }

        if (Schema::hasColumn('items', 'purchase_price') && Schema::hasColumn('items', 'cost')) {
            DB::table('items')
                ->where(function ($q) {
                    $q->whereNull('purchase_price')->orWhere('purchase_price', 0);
                })
                ->update(['purchase_price' => DB::raw('COALESCE(cost, 0)')]);
        }

        if (Schema::hasColumn('items', 'sale_price') && Schema::hasColumn('items', 'selling_price')) {
            DB::table('items')
                ->where(function ($q) {
                    $q->whereNull('sale_price')->orWhere('sale_price', 0);
                })
                ->update(['sale_price' => DB::raw('COALESCE(selling_price, 0)')]);
        }

        if (Schema::hasColumn('items', 'min_stock_level') && Schema::hasColumn('items', 'min_stock')) {
            DB::table('items')
                ->where(function ($q) {
                    $q->whereNull('min_stock_level')->orWhere('min_stock_level', 0);
                })
                ->update(['min_stock_level' => DB::raw('COALESCE(min_stock, 0)')]);
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'sku')) {
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('items', 'category_id')) {
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('items', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('items', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
            if (Schema::hasColumn('items', 'sale_price')) {
                $table->dropColumn('sale_price');
            }
            if (Schema::hasColumn('items', 'min_stock_level')) {
                $table->dropColumn('min_stock_level');
            }
        });
    }
};
