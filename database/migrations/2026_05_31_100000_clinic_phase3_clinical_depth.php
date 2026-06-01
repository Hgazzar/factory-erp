<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'allergies')) {
                $table->text('allergies')->nullable()->after('medical_history_summary');
            }
            if (! Schema::hasColumn('patients', 'chronic_conditions')) {
                $table->text('chronic_conditions')->nullable()->after('allergies');
            }
        });

        if (! Schema::hasColumn('employees', 'clinic_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('clinic_role', 32)->nullable()->after('status');
                $table->index(['user_id', 'clinic_role']);
            });
        }

        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('clinic_appointment_id')->nullable()->constrained('clinic_appointments')->nullOnDelete();
            $table->foreignId('doctor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('examination')->nullable();
            $table->text('diagnosis')->nullable();
            $table->timestamp('noted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'patient_id', 'noted_at']);
        });

        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->decimal('price', 15, 4);
            $table->boolean('vat_inclusive')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'code']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('clinic_appointment_service_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinic_appointment_id')->constrained('clinic_appointments')->cascadeOnDelete();
            $table->foreignId('clinic_service_id')->constrained('clinic_services')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('vat_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->index(['clinic_appointment_id']);
        });

        Schema::table('clinic_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('clinic_appointments', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 15, 4)->nullable()->after('fee_amount');
            }
            if (! Schema::hasColumn('clinic_appointments', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 4)->nullable()->after('subtotal_amount');
            }
        });

        Schema::create('clinic_medical_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('category', 32)->default('other');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_medical_attachments');
        Schema::dropIfExists('clinic_appointment_service_lines');
        Schema::dropIfExists('clinic_services');
        Schema::dropIfExists('clinical_notes');

        Schema::table('clinic_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_appointments', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
            if (Schema::hasColumn('clinic_appointments', 'subtotal_amount')) {
                $table->dropColumn('subtotal_amount');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'chronic_conditions')) {
                $table->dropColumn('chronic_conditions');
            }
            if (Schema::hasColumn('patients', 'allergies')) {
                $table->dropColumn('allergies');
            }
        });

        if (Schema::hasColumn('employees', 'clinic_role')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'clinic_role']);
                $table->dropColumn('clinic_role');
            });
        }
    }
};
