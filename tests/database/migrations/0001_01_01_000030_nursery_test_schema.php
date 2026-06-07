<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_guardians')) {
            Schema::create('nursery_guardians', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('phone', 32);
                $table->string('phone_alt', 32)->nullable();
                $table->string('email')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_children')) {
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
            });
        }

        if (! Schema::hasTable('nursery_classrooms')) {
            Schema::create('nursery_classrooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('capacity')->nullable();
                $table->json('age_groups')->nullable();
                $table->foreignId('teacher_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('accent_color', 16)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'name']);
            });
        }

        if (! Schema::hasTable('nursery_enrollments')) {
            Schema::create('nursery_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
                $table->foreignId('classroom_id')->constrained('nursery_classrooms')->cascadeOnDelete();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_attendance_logs')) {
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
            });
        }

        if (! Schema::hasColumn('employees', 'nursery_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nursery_role', 32)->nullable();
            });
        }
        if (! Schema::hasColumn('employees', 'nursery_job_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nursery_job_role', 64)->nullable();
                $table->json('nursery_permissions')->nullable();
                $table->string('nursery_education', 120)->nullable();
                $table->string('nursery_specialization', 120)->nullable();
            });
        }

        Schema::table('employees', function (Blueprint $table): void {
            foreach ([
                'email' => fn (Blueprint $t) => $t->string('email')->nullable(),
                'mobile' => fn (Blueprint $t) => $t->string('mobile', 32)->nullable(),
                'phone' => fn (Blueprint $t) => $t->string('phone', 32)->nullable(),
                'id_number' => fn (Blueprint $t) => $t->string('id_number', 64)->nullable(),
                'gender' => fn (Blueprint $t) => $t->string('gender', 16)->nullable(),
                'birth_date' => fn (Blueprint $t) => $t->date('birth_date')->nullable(),
                'address' => fn (Blueprint $t) => $t->string('address')->nullable(),
                'region' => fn (Blueprint $t) => $t->string('region', 64)->nullable(),
                'city' => fn (Blueprint $t) => $t->string('city', 120)->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('employees', $column)) {
                    $callback($table);
                }
            }
        });

        if (! Schema::hasColumn('nursery_guardians', 'national_id')) {
            Schema::table('nursery_guardians', function (Blueprint $table) {
                $table->string('national_id', 64)->nullable();
                $table->string('address', 500)->nullable();
                $table->string('region', 120)->nullable();
                $table->string('city', 120)->nullable();
            });
        }

        if (! Schema::hasColumn('nursery_guardians', 'portal_access_token')) {
            Schema::table('nursery_guardians', function (Blueprint $table) {
                $table->string('portal_access_token', 64)->nullable();
                $table->timestamp('portal_invited_at')->nullable();
                $table->timestamp('portal_last_login_at')->nullable();
                $table->unique(['user_id', 'portal_access_token'], 'nursery_guardians_user_portal_token_unique');
            });
        }

        if (! Schema::hasColumn('nursery_children', 'guardian_relationship')) {
            Schema::table('nursery_children', function (Blueprint $table) {
                $table->string('guardian_relationship', 32)->nullable();
                $table->text('diseases')->nullable();
                $table->text('health_notes')->nullable();
            });
        }

        if (! Schema::hasTable('nursery_units')) {
            Schema::create('nursery_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->json('age_groups')->nullable();
                $table->json('goals')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'name']);
            });
        }

        if (! Schema::hasTable('nursery_unit_lessons')) {
            Schema::create('nursery_unit_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('unit_id')->constrained('nursery_units')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['unit_id', 'name']);
            });
        }

        if (! Schema::hasTable('nursery_calendar_entries')) {
            Schema::create('nursery_calendar_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('entry_type', 24);
                $table->string('title');
                $table->foreignId('unit_id')->nullable()->constrained('nursery_units')->nullOnDelete();
                $table->foreignId('unit_lesson_id')->nullable()->constrained('nursery_unit_lessons')->nullOnDelete();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->text('notes')->nullable();
                $table->json('classroom_ids')->nullable();
                $table->json('child_ids')->nullable();
                $table->json('media_links')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_attendance_weekday_settings')) {
            Schema::create('nursery_attendance_weekday_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('scope', 16);
                $table->json('weekdays');
                $table->timestamps();
                $table->unique(['user_id', 'scope']);
            });
        }

        if (! Schema::hasTable('nursery_leave_records')) {
            Schema::create('nursery_leave_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('scope', 16);
                $table->foreignId('child_id')->nullable()->constrained('nursery_children')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
                $table->string('name');
                $table->date('starts_on');
                $table->date('ends_on');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_staff_attendance_logs')) {
            Schema::create('nursery_staff_attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('attendance_date');
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('checked_out_at')->nullable();
                $table->string('status', 24)->default('present');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'employee_id', 'attendance_date']);
            });
        }

        if (! Schema::hasTable('nursery_subscription_plans')) {
            Schema::create('nursery_subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('plan_type', 32)->default('custom');
                $table->decimal('amount', 12, 2);
                $table->decimal('tax_rate', 5, 2)->default(15);
                $table->string('currency_code', 8)->default('SAR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'name']);
            });
        }

        if (! Schema::hasTable('nursery_subscriptions')) {
            Schema::create('nursery_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('nursery_subscription_plans')->cascadeOnDelete();
                $table->date('starts_on');
                $table->date('ends_on');
                $table->decimal('amount_after_tax', 12, 2);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->boolean('is_paid')->default(false);
                $table->string('status', 24)->default('active');
                $table->timestamp('payment_reminder_sent_at')->nullable();
                $table->timestamp('renewal_reminder_sent_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method', 32)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_settings')) {
            Schema::create('nursery_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('nursery_name');
                $table->string('display_name', 120)->nullable();
                $table->string('logo_path', 500)->nullable();
                $table->string('theme_primary_color', 7)->nullable();
                $table->string('theme_secondary_color', 7)->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('region')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_mobile')->nullable();
                $table->string('manager_email')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursery_shifts')) {
            Schema::create('nursery_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('nursery_shifts') && ! Schema::hasColumn('employees', 'nursery_shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('nursery_shift_id')->nullable()->constrained('nursery_shifts')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('nursery_child_medications')) {
            Schema::create('nursery_child_medications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
                $table->string('name');
                $table->string('dosage')->nullable();
                $table->string('frequency', 32)->nullable();
                $table->string('schedule_notes')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_child_medications');
        Schema::dropIfExists('nursery_shifts');
        Schema::dropIfExists('nursery_settings');
        Schema::dropIfExists('nursery_subscriptions');
        Schema::dropIfExists('nursery_subscription_plans');
        Schema::dropIfExists('nursery_staff_attendance_logs');
        Schema::dropIfExists('nursery_leave_records');
        Schema::dropIfExists('nursery_attendance_weekday_settings');
        Schema::dropIfExists('nursery_calendar_entries');
        Schema::dropIfExists('nursery_unit_lessons');
        Schema::dropIfExists('nursery_units');
        Schema::dropIfExists('nursery_attendance_logs');
        Schema::dropIfExists('nursery_enrollments');
        Schema::dropIfExists('nursery_classrooms');
        Schema::dropIfExists('nursery_children');
        Schema::dropIfExists('nursery_guardians');
    }
};
