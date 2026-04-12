<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول الماكينات (موديول التصنيع)
     */
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('كود الماكينة');
            $table->string('name_ar')->comment('اسم الماكينة بالعربي');
            $table->string('name_en')->nullable()->comment('اسم الماكينة بالإنجليزي');
            $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->nullOnDelete()->comment('خط الإنتاج');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active')->comment('active | maintenance | inactive');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
