<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
