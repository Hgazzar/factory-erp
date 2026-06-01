<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                $table->foreignId('sales_order_id')
                    ->nullable()
                    ->after('quotation_id')
                    ->constrained('sales_orders')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_invoices', 'posting_source')) {
                $table->string('posting_source', 32)->default('direct')->after('sales_order_id');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('purchase_orders')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_invoices', 'posting_source')) {
                $table->string('posting_source', 32)->default('direct')->after('purchase_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                $table->dropConstrainedForeignId('sales_order_id');
            }
            if (Schema::hasColumn('sales_invoices', 'posting_source')) {
                $table->dropColumn('posting_source');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'purchase_order_id')) {
                $table->dropConstrainedForeignId('purchase_order_id');
            }
            if (Schema::hasColumn('purchase_invoices', 'posting_source')) {
                $table->dropColumn('posting_source');
            }
        });
    }
};
