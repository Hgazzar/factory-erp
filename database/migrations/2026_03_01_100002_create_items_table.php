<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول الأصناف (المنتجات - مواد خام - نصف مصنعة)
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('كود الصنف');
            $table->string('name_ar')->comment('الاسم بالعربي');
            $table->string('name_en')->nullable()->comment('الاسم بالإنجليزي');
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete()->comment('وحدة القياس');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('finished')->comment('قبل الترقية: raw_material|semi_finished|finished — بعد migration 2026_03_17: raw_material|finished_good|service');
            $table->decimal('min_stock', 15, 4)->default(0)->comment('الحد الأدنى للمخزون');
            $table->decimal('cost', 15, 4)->nullable()->comment('التكلفة التقديرية');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
