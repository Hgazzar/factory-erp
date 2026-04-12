<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول الورديات الفعلية على خطوط الإنتاج/الماكينات
     */
    public function up(): void
    {
        Schema::create('production_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete()->comment('قالب الوردية');
            $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->nullOnDelete()->comment('خط الإنتاج');
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete()->comment('الماكينة (اختياري)');
            $table->date('date')->comment('تاريخ الوردية');
            $table->dateTime('planned_start_at')->nullable()->comment('بداية مخططة');
            $table->dateTime('planned_end_at')->nullable()->comment('نهاية مخططة');
            $table->dateTime('actual_start_at')->nullable()->comment('بداية فعلية');
            $table->dateTime('actual_end_at')->nullable()->comment('نهاية فعلية');
            $table->string('status', 30)->default('planned')->comment('planned | in_progress | completed | cancelled');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['date', 'shift_id']);
            $table->index(['production_line_id', 'machine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_shifts');
    }
};

