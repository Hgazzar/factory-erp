<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Jobs\Clinic\SendClinicAppointmentConfirmationWhatsAppJob;
use App\Jobs\Clinic\SendClinicAppointmentReminderWhatsAppJob;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Employee;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicAppointmentWhatsAppJobTest extends ClinicTestCase
{
    #[Test]
    public function booking_listener_dispatches_confirmation_whatsapp_job(): void
    {
        Queue::fake();

        $tenantId = (int) $this->tenant->id;
        $patient = Patient::query()->create([
            'user_id' => $tenantId,
            'code' => 'PAT-JOB-01',
            'name' => 'مريض Job',
            'phone' => '01012345678',
        ]);

        $doctor = Employee::query()->create([
            'user_id' => $tenantId,
            'code' => 'DOC-JOB-01',
            'name' => 'د. سامي',
            'status' => 'active',
            'clinic_role' => 'doctor',
        ]);

        $appointment = Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-JOB-001',
            'patient_id' => $patient->id,
            'doctor_employee_id' => $doctor->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
            'booking_source' => Appointment::SOURCE_PORTAL,
        ]);

        event(new ClinicAppointmentBooked($appointment, $tenantId));

        Queue::assertPushed(SendClinicAppointmentConfirmationWhatsAppJob::class, function ($job) use ($tenantId, $appointment): bool {
            return $job->tenantUserId === $tenantId && $job->appointmentId === (int) $appointment->id;
        });
    }

    #[Test]
    public function reminder_command_dispatches_reminder_whatsapp_job(): void
    {
        Queue::fake();

        $tenantId = (int) $this->tenant->id;
        $patient = Patient::query()->create([
            'user_id' => $tenantId,
            'code' => 'PAT-JOB-02',
            'name' => 'مريض تذكير',
            'phone' => '01012345679',
        ]);

        $doctor = Employee::query()->create([
            'user_id' => $tenantId,
            'code' => 'DOC-JOB-02',
            'name' => 'د. ليلى',
            'status' => 'active',
            'clinic_role' => 'doctor',
        ]);

        $scheduledAt = now()->addHours(6);

        Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-JOB-002',
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

        $this->artisan('clinic:send-whatsapp-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain('sent=1');

        Queue::assertPushed(SendClinicAppointmentReminderWhatsAppJob::class);
    }
}
