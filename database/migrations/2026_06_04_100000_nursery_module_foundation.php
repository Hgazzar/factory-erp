<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('phone_alt', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'phone']);
            $table->index(['user_id', 'name']);
        });

        Schema::create('nursery_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 32)->nullable();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();
            $table->foreignId('guardian_id')->constrained('nursery_guardians')->cascadeOnDelete();
            $table->text('allergies')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'code']);
            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('nursery_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->foreignId('teacher_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('accent_color', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('nursery_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('nursery_classrooms')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'classroom_id', 'is_active']);
            $table->index(['child_id', 'is_active']);
        });

        Schema::create('nursery_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status', 24)->default('present');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'child_id', 'attendance_date']);
            $table->index(['user_id', 'attendance_date', 'status']);
        });

        if (! Schema::hasColumn('employees', 'nursery_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nursery_role', 32)->nullable()->after('clinic_role');
                $table->index(['user_id', 'nursery_role']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_attendance_logs');
        Schema::dropIfExists('nursery_enrollments');
        Schema::dropIfExists('nursery_classrooms');
        Schema::dropIfExists('nursery_children');
        Schema::dropIfExists('nursery_guardians');

        if (Schema::hasColumn('employees', 'nursery_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'nursery_role']);
                $table->dropColumn('nursery_role');
            });
        }
    }
};
