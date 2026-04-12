<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
