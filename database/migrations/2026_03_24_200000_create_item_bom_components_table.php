<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_bom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('component_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity_per_unit', 15, 4);
            $table->timestamps();

            $table->unique(['finished_item_id', 'component_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_bom_components');
    }
};
