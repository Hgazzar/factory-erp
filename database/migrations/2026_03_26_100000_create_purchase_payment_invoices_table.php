<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط سندات صرف الموردين بفواتير المشتريات مع مبلغ التخصيص.
     */
    public function up(): void
    {
        Schema::create('purchase_payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->timestamps();

            $table->unique(['payment_id', 'purchase_invoice_id']);
            $table->index('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_invoices');
    }
};
