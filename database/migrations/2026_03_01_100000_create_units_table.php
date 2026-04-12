<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول وحدات القياس (قطعة، متر، كيلو، لتر، ...)
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('كود الوحدة');
            $table->string('name_ar')->comment('الاسم بالعربي');
            $table->string('name_en')->nullable()->comment('الاسم بالإنجليزي');
            $table->string('symbol', 10)->nullable()->comment('الرمز مثل: قطعة، م، كجم');
            $table->unsignedBigInteger('base_unit_id')->nullable()->comment('الوحدة الأساسية للتحويل');
            $table->decimal('conversion_factor', 15, 6)->default(1)->comment('معامل التحويل للوحدة الأساسية');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreign('base_unit_id')->references('id')->on('units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
        });
        Schema::dropIfExists('units');
    }
};
