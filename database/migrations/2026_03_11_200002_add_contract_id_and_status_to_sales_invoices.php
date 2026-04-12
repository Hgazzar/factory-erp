<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('quotation_id')->constrained('contracts')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_invoices', 'invoice_status')) {
                $table->string('invoice_status', 20)->default('issued')->after('reference')->comment('draft|issued');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'contract_id')) {
                $table->dropForeign(['contract_id']);
            }
            if (Schema::hasColumn('sales_invoices', 'invoice_status')) {
                $table->dropColumn('invoice_status');
            }
        });
    }
};
