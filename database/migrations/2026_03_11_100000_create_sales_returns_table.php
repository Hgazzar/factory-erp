<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->comment('رقم المرتجع');
            $table->date('date');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('reason_type', 100)->nullable()->comment('نوع السبب');
            $table->text('reason')->nullable()->comment('سبب الإرجاع');
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('vat_amount', 15, 4)->default(0);
            $table->string('status', 50)->default('معلق')->comment('معلق | معتمد | مسترد');
            $table->decimal('refunded_amount', 15, 4)->default(0)->comment('المبلغ المسترد');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('إشعار دائن');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
