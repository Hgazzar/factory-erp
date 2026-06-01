<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clinic_appointments', 'reminder_sent_at')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->timestamp('reminder_sent_at')->nullable()->after('paid_at');
                $table->index(['user_id', 'appointment_date', 'status', 'reminder_sent_at'], 'clinic_appt_reminder_lookup');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinic_appointments', 'reminder_sent_at')) {
            Schema::table('clinic_appointments', function (Blueprint $table) {
                $table->dropIndex('clinic_appt_reminder_lookup');
                $table->dropColumn('reminder_sent_at');
            });
        }
    }
};
