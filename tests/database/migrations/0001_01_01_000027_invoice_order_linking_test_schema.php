<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                    $table->unsignedBigInteger('sales_order_id')->nullable();
                }
                if (! Schema::hasColumn('sales_invoices', 'posting_source')) {
                    $table->string('posting_source', 32)->default('direct');
                }
            });
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoices', 'purchase_order_id')) {
                    $table->unsignedBigInteger('purchase_order_id')->nullable();
                }
                if (! Schema::hasColumn('purchase_invoices', 'posting_source')) {
                    $table->string('posting_source', 32)->default('direct');
                }
            });
        }
    }

    public function down(): void
    {
        // test schema — no rollback required
    }
};
