<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->comment('رقم المرتجع');
            $table->date('date');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('reason_type', 100)->nullable()->comment('نوع السبب');
            $table->text('reason')->nullable()->comment('سبب الإرجاع');
            $table->text('notes')->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('vat_amount', 15, 4)->default(0);
            $table->string('status', 30)->default('pending')->comment('pending | shipped | completed');
            $table->foreignId('debit_note_id')->nullable()->constrained('debit_notes')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
