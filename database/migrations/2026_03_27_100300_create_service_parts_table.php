<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['service_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_parts');
    }
};
