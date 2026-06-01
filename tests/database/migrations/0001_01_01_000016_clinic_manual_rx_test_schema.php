<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_medical_attachments') && ! Schema::hasColumn('clinic_medical_attachments', 'clinic_appointment_id')) {
            Schema::table('clinic_medical_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('clinic_appointment_id')->nullable()->after('patient_id');
                $table->index(['clinic_appointment_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinic_medical_attachments', 'clinic_appointment_id')) {
            Schema::table('clinic_medical_attachments', function (Blueprint $table) {
                $table->dropColumn('clinic_appointment_id');
            });
        }
    }
};
