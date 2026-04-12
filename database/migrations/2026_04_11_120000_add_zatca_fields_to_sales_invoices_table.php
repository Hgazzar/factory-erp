<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->uuid('zatca_invoice_uuid')->nullable()->after('journal_entry_id');
            $table->unsignedBigInteger('zatca_icv')->nullable()->after('zatca_invoice_uuid')->comment('ZATCA invoice counter value (ICV)');
            $table->text('zatca_hash')->nullable()->after('zatca_icv')->comment('Invoice cryptographic hash (e.g. base64)');
            $table->text('zatca_pih')->nullable()->after('zatca_hash')->comment('Previous invoice hash (PIH), base64');
            $table->longText('zatca_signed_xml')->nullable()->after('zatca_pih');
            $table->string('zatca_status', 32)->nullable()->after('zatca_signed_xml')->comment('e.g. pending, signed, reported, cleared, failed');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'zatca_invoice_uuid',
                'zatca_icv',
                'zatca_hash',
                'zatca_pih',
                'zatca_signed_xml',
                'zatca_status',
            ]);
        });
    }
};
