<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number')->comment('رقم القسط');
            $table->date('due_date');
            $table->decimal('amount', 15, 4)->default(0);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['sales_invoice_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
