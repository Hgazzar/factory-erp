<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('order_date');
            $table->string('currency', 5)->default('SAR');
            $table->string('reference', 100)->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('delivery_address')->nullable();
            $table->decimal('shipping_cost', 15, 4)->default(0);
            $table->text('internal_notes')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->string('status', 50)->default('معلق');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('total_discount', 15, 4)->default(0);
            $table->decimal('total_tax', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
