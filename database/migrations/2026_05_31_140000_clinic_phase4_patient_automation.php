<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenant_profiles', 'slug')) {
            Schema::table('tenant_profiles', function (Blueprint $table) {
                $table->string('slug', 128)->nullable()->after('domain');
            });

            DB::table('tenant_profiles')->whereNull('slug')->update([
                'slug' => DB::raw('domain'),
            ]);

            Schema::table('tenant_profiles', function (Blueprint $table) {
                $table->unique('slug');
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

                $table->index(['user_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('employees', 'clinic_specialty_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('clinic_specialty_id')
                    ->nullable()
                    ->after('clinic_role')
                    ->constrained('clinic_specialties')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('clinic_doctor_schedules')) {
            Schema::create('clinic_doctor_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_employee_id')->constrained('employees')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week')->comment('0=Sunday … 6=Saturday (Carbon dayOfWeek)');
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'doctor_employee_id', 'day_of_week', 'is_active'], 'clinic_doc_sched_lookup');
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

                $table->index(['user_id', 'doctor_employee_id', 'blocked_date'], 'clinic_blocked_lookup');
            });
        }

        if (! Schema::hasColumn('clinic_appointments', 'booking_source')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->string('booking_source', 16)->default('staff')->after('status');
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
                $table->dropConstrainedForeignId('clinic_specialty_id');
            });
        }

        Schema::dropIfExists('clinic_specialties');

        if (Schema::hasColumn('tenant_profiles', 'slug')) {
            Schema::table('tenant_profiles', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
