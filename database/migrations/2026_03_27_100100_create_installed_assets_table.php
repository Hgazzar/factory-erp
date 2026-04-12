<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('delivery_order_item_id')->constrained('delivery_order_items')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('installation_location', 255)->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('delivery_order_item_id');
            $table->index(['warranty_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_assets');
    }
};
