<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * كميات الأصناف في كل مخزن (حركة المخزون تُدار لاحقاً بأوامر الإنتاج والتحويل)
     */
    public function up(): void
    {
        Schema::create('item_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0)->comment('الكمية المتاحة');
            $table->decimal('reserved_quantity', 15, 4)->default(0)->comment('الكمية المحجوزة');
            $table->timestamps();

            $table->unique(['item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_warehouse');
    }
};
