<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول تتبع الإنتاج اللحظي لكل وردية
     */
    public function up(): void
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_shift_id')->constrained('production_shifts')->cascadeOnDelete()->comment('الوردية الفعلية');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete()->comment('الصنف المنتج');
            $table->decimal('quantity', 15, 4)->default(0)->comment('الكمية المنتَجة');
            $table->decimal('rejected_quantity', 15, 4)->default(0)->comment('الكمية المرفوضة/الهالك');
            $table->dateTime('logged_at')->comment('وقت تسجيل الإنتاج');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['production_shift_id', 'item_id']);
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};

