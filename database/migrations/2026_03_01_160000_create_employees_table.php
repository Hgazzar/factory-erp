<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول الموظفين (HR - Employee Module)
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code', 30)->unique()->comment('كود الموظف');
            $table->string('name')->comment('اسم الموظف');
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->date('hired_at')->nullable()->comment('تاريخ التعيين');
            $table->timestamps();

            $table->index('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

