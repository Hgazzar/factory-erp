<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('patients', 'allergies')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('allergies')->nullable();
                $table->text('chronic_conditions')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'clinic_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('clinic_role', 32)->nullable();
            });
        }

        if (! Schema::hasTable('clinical_notes')) {
            Schema::create('clinical_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('clinic_appointment_id')->nullable()->constrained('clinic_appointments')->nullOnDelete();
                $table->foreignId('doctor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->text('chief_complaint')->nullable();
                $table->text('examination')->nullable();
                $table->text('diagnosis')->nullable();
                $table->timestamp('noted_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('clinic_services')) {
            Schema::create('clinic_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name');
                $table->decimal('price', 15, 4);
                $table->boolean('vat_inclusive')->default(true);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'code']);
            });
        }

        if (! Schema::hasTable('clinic_appointment_service_lines')) {
            Schema::create('clinic_appointment_service_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('clinic_appointment_id')->constrained('clinic_appointments')->cascadeOnDelete();
                $table->foreignId('clinic_service_id')->constrained('clinic_services')->cascadeOnDelete();
                $table->unsignedSmallInteger('quantity')->default(1);
                $table->decimal('unit_price', 15, 4);
                $table->decimal('vat_amount', 15, 4)->default(0);
                $table->decimal('line_total', 15, 4);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('clinic_appointments', 'subtotal_amount')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->decimal('subtotal_amount', 15, 4)->nullable();
                $table->decimal('vat_amount', 15, 4)->nullable();
            });
        }

        if (! Schema::hasTable('clinic_medical_attachments')) {
            Schema::create('clinic_medical_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->string('category', 32)->default('other');
                $table->string('original_name');
                $table->string('storage_path');
                $table->string('mime_type', 128)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_medical_attachments');
        Schema::dropIfExists('clinic_appointment_service_lines');
        Schema::dropIfExists('clinic_services');
        Schema::dropIfExists('clinical_notes');
    }
};
