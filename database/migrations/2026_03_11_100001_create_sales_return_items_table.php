<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0)->comment('الكمية المرتجعة');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->string('line_reason', 500)->nullable()->comment('سبب البند');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
