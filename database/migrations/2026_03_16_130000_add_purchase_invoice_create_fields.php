<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number', 100)->nullable()->after('reference');
            }
            if (!Schema::hasColumn('purchase_invoices', 'currency')) {
                $table->string('currency', 5)->default('SAR')->after('total');
            }
            if (!Schema::hasColumn('purchase_invoices', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_invoices', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('notes');
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->string('description', 500)->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'discount')) {
                $table->decimal('discount', 15, 4)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'vat_percent')) {
                $table->decimal('vat_percent', 5, 2)->default(15)->after('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $cols = ['supplier_invoice_number', 'currency', 'notes', 'internal_notes'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('purchase_invoices', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $cols = ['description', 'discount', 'vat_percent'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('purchase_invoice_items', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
