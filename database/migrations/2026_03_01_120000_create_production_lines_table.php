<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول خطوط الإنتاج (موديول التصنيع)
     */
    public function up(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('كود الخط');
            $table->string('name_ar')->comment('اسم الخط بالعربي');
            $table->string('name_en')->nullable()->comment('اسم الخط بالإنجليزي');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_lines');
    }
};
