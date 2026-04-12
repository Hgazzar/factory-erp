<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('reason');
            $table->string('currency', 5)->default('SAR')->after('vat_amount');
            $table->text('internal_notes')->nullable()->after('notes');
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_invoice_id']);
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_invoice_id')->nullable()->change();
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->nullOnDelete();
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->decimal('vat_percent', 5, 2)->default(0)->after('unit_price');
            $table->string('line_status', 50)->nullable()->after('vat_percent')->comment('معيب، سليم، غير مطابق، إلخ');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn(['vat_percent', 'line_status']);
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_invoice_id']);
            $table->unsignedBigInteger('purchase_invoice_id')->nullable(false)->change();
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->cascadeOnDelete();
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn(['reference', 'currency', 'internal_notes']);
        });
    }
};
