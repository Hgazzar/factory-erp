<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code', 30);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedSmallInteger('grace_minutes')->default(0);
                $table->boolean('is_night')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('code', 30);
                $table->string('name');
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('attendance_device_id', 64)->nullable();
                $table->string('status', 20)->default('active');
                $table->decimal('base_salary', 12, 2)->nullable();
                $table->string('salary_type', 20)->default('monthly');
                $table->string('attendance_policy', 30)->default('none');
                $table->timestamps();
                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->date('work_date');
                $table->dateTime('check_in_at')->nullable();
                $table->dateTime('check_out_at')->nullable();
                $table->string('status', 20)->default('absent');
                $table->unsignedSmallInteger('minutes_late')->default(0);
                $table->unsignedSmallInteger('minutes_early_departure')->default(0);
                $table->decimal('work_hours', 6, 2)->nullable();
                $table->decimal('deduction_amount', 12, 2)->nullable();
                $table->timestamps();
                $table->unique(['employee_id', 'work_date']);
            });
        }

        if (! Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('employee_device_id', 64)->nullable();
                $table->dateTime('logged_at');
                $table->string('direction', 10);
                $table->string('source', 40)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('overtime_requests')) {
            Schema::create('overtime_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('work_date');
                $table->decimal('hours', 8, 2)->default(0);
                $table->string('status', 20)->default('new');
                $table->decimal('rate_multiplier', 5, 2)->default(1.5);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('shifts');
    }
};
