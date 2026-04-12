<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول الورديات (موديول العمليات)
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('كود الوردية');
            $table->string('name_ar')->comment('اسم الوردية بالعربي');
            $table->string('name_en')->nullable()->comment('اسم الوردية بالإنجليزي');
            $table->time('start_time')->comment('وقت بداية الوردية');
            $table->time('end_time')->comment('وقت نهاية الوردية');
            $table->boolean('is_night')->default(false)->comment('هل الوردية ليلية');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};

