<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * سجلات تسجيل الإنتاج بواسطة الموظفين
     */
    public function up(): void
    {
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('production_shift_id')->nullable()->constrained('production_shifts')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('scrap_quantity', 15, 4)->default(0);
            $table->dateTime('recorded_at');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id']);
            $table->index(['item_id']);
            $table->index(['recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_records');
    }
};

