<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('price', 15, 4);
            $table->timestamps();

            $table->unique(['price_list_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
