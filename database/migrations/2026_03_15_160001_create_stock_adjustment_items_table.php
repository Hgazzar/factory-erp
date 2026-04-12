<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->comment('الكمية الموجبة للإضافة أو الخصم');
            $table->decimal('unit_cost', 15, 4)->default(0)->comment('التكلفة/السعر عند التسوية');
            $table->string('reason', 50)->nullable()->comment('تلف، هدايا، عينات، خطأ جرد، إلخ');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
