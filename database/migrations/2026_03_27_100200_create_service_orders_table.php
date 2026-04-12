<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('service_type', 20)->comment('install|maintenance|repair');
            $table->string('priority', 15)->default('normal')->comment('urgent|normal');
            $table->string('status', 20)->default('open')->comment('open|assigned|in_progress|completed|cancelled');
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('executed_at')->nullable();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->foreignId('installed_asset_id')->nullable()->constrained('installed_assets')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->boolean('is_paid_service')->default(false);
            $table->boolean('outside_warranty')->default(false);
            $table->decimal('labor_amount', 15, 4)->nullable()->comment('أجرة يدوية تُضاف للفاتورة المسودة');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('assigned_technician_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
