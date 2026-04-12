<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // اسم المنتج (مثلاً: حديد، بلاستيك، خشب)
            $table->string('sku')->unique();  // كود الصنف (عشان ميتكررش)
            $table->integer('quantity')->default(0); // الكمية الموجودة
            $table->decimal('price', 10, 2)->nullable(); // السعر لو حابب تضيفه
            $table->string('unit')->default('piece'); // الوحدة (قطعة، متر، كيلو)
            $table->timestamps(); // ده بيسجل تلقائياً وقت إضافة المنتج
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};