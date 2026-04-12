<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('delivery_number', 50)->unique();
            $table->string('status', 20)->default('pending'); // pending | delivered | cancelled
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->unique(['delivery_order_id', 'sales_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};
