<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('production_number', 50)->unique();
            $table->string('status', 20)->default('pending'); // pending | in_progress | completed | cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('produced_quantity', 15, 4)->default(0);
        });

        Schema::create('production_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity_to_consume', 15, 4);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_ingredients');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('production_orders');
    }
};
