<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference', 40)->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->date('production_date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity_produced', 15, 4);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->decimal('total_materials_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('manufacturing_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_run_id')->constrained('manufacturing_runs')->cascadeOnDelete();
            $table->foreignId('ingredient_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity_consumed', 15, 4);
            $table->decimal('unit_cost_at_post', 15, 4)->nullable();
            $table->decimal('line_cost', 15, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_run_lines');
        Schema::dropIfExists('manufacturing_runs');
    }
};
