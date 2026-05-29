<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'current_stock')) {
                $table->decimal('current_stock', 15, 4)->default(0)->after('cost');
            }
            if (! Schema::hasColumn('items', 'min_stock')) {
                $table->decimal('min_stock', 15, 4)->default(0)->after('current_stock');
            }
        });

        Schema::create('item_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'item_id', 'warehouse_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('movement_type', 30);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'warehouse_id']);
            $table->index(['movement_type']);
        });

        Schema::create('manufacturing_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40)->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('production_date');
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity_produced', 15, 4)->default(0);
            $table->decimal('total_materials_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_order_id')->nullable();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_parts');
        Schema::dropIfExists('manufacturing_runs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('item_warehouse');

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            if (Schema::hasColumn('items', 'current_stock')) {
                $table->dropColumn('current_stock');
            }
        });
    }
};
