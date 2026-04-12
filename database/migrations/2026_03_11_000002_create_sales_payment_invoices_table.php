<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_payment_id')->constrained('sales_payments')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('amount_allocated', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['sales_payment_id', 'sales_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payment_invoices');
    }
};
