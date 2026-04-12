<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
