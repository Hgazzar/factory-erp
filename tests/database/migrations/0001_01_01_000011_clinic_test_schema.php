<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32)->nullable();
                $table->string('name');
                $table->string('phone', 32)->nullable();
                $table->string('national_id', 64)->nullable();
                $table->string('blood_type', 8)->nullable();
                $table->text('medical_history_summary')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('clinic_appointments')) {
            Schema::create('clinic_appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('appointment_number', 32);
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('doctor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->date('appointment_date');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->string('status', 24)->default('pending');
                $table->decimal('fee_amount', 15, 4)->nullable();
                $table->string('payment_method', 32)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'appointment_number']);
            });
        }

        if (! Schema::hasTable('clinic_prescriptions')) {
            Schema::create('clinic_prescriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('doctor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('clinic_appointment_id')->nullable()->constrained('clinic_appointments')->nullOnDelete();
                $table->text('diagnosis')->nullable();
                $table->json('medications');
                $table->timestamp('prescribed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_prescriptions');
        Schema::dropIfExists('clinic_appointments');
        Schema::dropIfExists('patients');
    }
};
