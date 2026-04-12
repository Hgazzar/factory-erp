<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');

            // الكمية: موجبة للإضافة، سالبة للخصم
            $table->decimal('quantity', 15, 4);

            // purchase, sale, adjustment, return
            $table->string('type', 30);

            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();

            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index(['item_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);

            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
