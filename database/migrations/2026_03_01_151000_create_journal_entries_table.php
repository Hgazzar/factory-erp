<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول قيود اليومية (رأس القيد)
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->comment('الرقم المرجعي للقيد');
            $table->date('date')->comment('تاريخ القيد');
            $table->text('description')->nullable()->comment('وصف / بيان القيد');
            $table->decimal('total', 15, 4)->default(0)->comment('إجمالي القيد (مدين = دائن)');
            $table->timestamps();

            $table->index('date');
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};

