<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 40)->nullable()->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('reference', 100)->nullable();
            $table->date('date');
            $table->string('notes', 2000)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_in_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained('stock_ins')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['stock_in_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_lines');
        Schema::dropIfExists('stock_ins');
    }
};
