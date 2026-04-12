<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique()->nullable();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('type', 30)->default('service')->comment('service|product');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('billing_cycle', 30)->default('monthly')->comment('monthly|quarterly|yearly');
            $table->string('currency', 10)->default('SAR');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('reminder_days')->default(3);
            $table->boolean('auto_renew')->default(false);
            $table->date('next_invoice_date')->nullable();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('status', 20)->default('active')->comment('active|expired|cancelled');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'customer_id']);
            $table->index('next_invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
