<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete()->comment('المنتج التام');
            $table->string('name', 255);
            $table->string('version', 40)->default('1.0');
            $table->string('status', 20)->default('draft')->index()->comment('draft, active, obsolete');
            $table->decimal('labor_cost', 15, 4)->default(0);
            $table->decimal('overhead_cost', 15, 4)->default(0);
            $table->text('header_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bom_list_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_list_id')->constrained('bom_lists')->cascadeOnDelete();
            $table->foreignId('component_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 50)->nullable();
            $table->decimal('scrap_percent', 8, 4)->default(0);
            $table->string('notes', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_list_lines');
        Schema::dropIfExists('bom_lists');
    }
};
