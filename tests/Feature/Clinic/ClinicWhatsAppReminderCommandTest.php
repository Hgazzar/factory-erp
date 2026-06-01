<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Employee;
use App\Services\Tenant\TenantFeatureRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicWhatsAppReminderCommandTest extends ClinicTestCase
{
    #[Test]
    public function command_sends_reminder_for_pending_appointments_within_next_24_hours(): void
    {
        $tenantId = (int) $this->tenant->id;
        $patient = Patient::query()->create([
            'user_id' => $tenantId,
            'code' => 'PAT-10001',
            'name' => 'مريض التذكير',
            'phone' => '01012345678',
        ]);

        $doctor = Employee::query()->create([
            'user_id' => $tenantId,
            'code' => 'DOC-R1',
            'name' => 'د. كريم',
            'status' => 'active',
            'clinic_role' => 'doctor',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone')));
        $scheduledAt = now()->copy()->addHours(6);

        $appointment = Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-REM-001',
            'patient_id' => $patient->id,
            'doctor_employee_id' => $doctor->id,
            'appointment_date' => $scheduledAt->toDateString(),
            'start_time' => $scheduledAt->format('H:i:s'),
            'status' => Appointment::STATUS_PENDING,
            'booking_source' => Appointment::SOURCE_PORTAL,
        ]);

        config([
            'clinic.whatsapp.enabled' => false,
            'clinic.whatsapp.access_token' => null,
            'clinic.whatsapp.phone_number_id' => null,
        ]);

        Cache::flush();
        app(TenantFeatureRegistry::class)->forgetCache($tenantId);

        $this->assertTrue(Schema::hasColumn('clinic_appointments', 'reminder_sent_at'));

        $this->artisan('clinic:send-whatsapp-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain('sent=1');

        Carbon::setTestNow();

        $appointment->refresh();
        $this->assertNotNull($appointment->reminder_sent_at);
    }

    #[Test]
    public function command_skips_already_sent_or_outside_window_appointments(): void
    {
        $tenantId = (int) $this->tenant->id;
        $patient = Patient::query()->create([
            'user_id' => $tenantId,
            'code' => 'PAT-10002',
            'name' => 'مريض تخطي',
            'phone' => '01010000000',
        ]);

        Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-REM-002',
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'status' => Appointment::STATUS_PENDING,
            'reminder_sent_at' => null,
        ]);

        Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-REM-003',
            'patient_id' => $patient->id,
            'appointment_date' => now()->addHours(6)->toDateString(),
            'start_time' => now()->addHours(6)->format('H:i:s'),
            'status' => Appointment::STATUS_PENDING,
            'reminder_sent_at' => now(),
        ]);

        $this->artisan('clinic:send-whatsapp-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain('sent=0');
    }
}
