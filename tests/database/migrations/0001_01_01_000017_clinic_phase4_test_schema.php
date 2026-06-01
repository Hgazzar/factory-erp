<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenant_profiles', 'slug')) {
            Schema::table('tenant_profiles', function (Blueprint $table) {
                $table->string('slug', 128)->nullable()->unique();
            });
        }

        if (! Schema::hasTable('clinic_specialties')) {
            Schema::create('clinic_specialties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('employees', 'clinic_specialty_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('clinic_specialty_id')->nullable();
            });
        }

        if (! Schema::hasTable('clinic_doctor_schedules')) {
            Schema::create('clinic_doctor_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_employee_id')->constrained('employees')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('clinic_blocked_slots')) {
            Schema::create('clinic_blocked_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
                $table->date('blocked_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_full_day')->default(false);
                $table->string('reason')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('clinic_appointments', 'booking_source')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->string('booking_source', 16)->default('staff');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinic_appointments', 'booking_source')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->dropColumn('booking_source');
            });
        }

        Schema::dropIfExists('clinic_blocked_slots');
        Schema::dropIfExists('clinic_doctor_schedules');

        if (Schema::hasColumn('employees', 'clinic_specialty_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('clinic_specialty_id');
            });
        }

        Schema::dropIfExists('clinic_specialties');

        if (Schema::hasColumn('tenant_profiles', 'slug')) {
            Schema::table('tenant_profiles', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
