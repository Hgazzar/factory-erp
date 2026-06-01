<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\DoctorSchedule;
use App\Models\Employee;
use App\Services\Clinic\ClinicAppointmentService;
use App\Services\Clinic\ClinicAvailabilityService;
use App\Services\Clinic\ClinicPatientService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicAvailabilityServiceTest extends ClinicTestCase
{
    private ClinicAvailabilityService $availability;

    private ClinicAppointmentService $appointments;

    private ClinicPatientService $patients;

    private Employee $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = app(ClinicAvailabilityService::class);
        $this->appointments = app(ClinicAppointmentService::class);
        $this->patients = app(ClinicPatientService::class);

        $this->doctor = Employee::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'DOC-001',
            'name' => 'د. سامي',
            'status' => 'active',
            'clinic_role' => 'doctor',
        ]);

        $dayOfWeek = Carbon::parse(now()->addDay()->toDateString())->dayOfWeek;

        DoctorSchedule::query()->create([
            'user_id' => $this->tenant->id,
            'doctor_employee_id' => $this->doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function available_slots_respect_doctor_schedule(): void
    {
        $date = now()->addDay()->toDateString();

        $slots = $this->availability->availableSlotsForDate((int) $this->tenant->id, (int) $this->doctor->id, $date);

        $this->assertNotEmpty($slots);
        $this->assertSame('14:00:00', $slots[0]['start']);
    }

    #[Test]
    public function double_booking_same_doctor_slot_is_rejected(): void
    {
        $date = now()->addDay()->toDateString();
        $patient = $this->patients->create((int) $this->tenant->id, ['name' => 'مريض 1']);

        $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'doctor_employee_id' => $this->doctor->id,
            'appointment_date' => $date,
            'start_time' => '14:00',
        ], dispatchEvent: false);

        $patient2 = $this->patients->create((int) $this->tenant->id, ['name' => 'مريض 2']);

        $this->expectException(\RuntimeException::class);

        $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient2->id,
            'doctor_employee_id' => $this->doctor->id,
            'appointment_date' => $date,
            'start_time' => '14:00',
        ], dispatchEvent: false);
    }

    #[Test]
    public function cancelled_appointment_frees_slot(): void
    {
        $date = now()->addDay()->toDateString();
        $patient = $this->patients->create((int) $this->tenant->id, ['name' => 'مريض إلغاء']);

        $appointment = $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'doctor_employee_id' => $this->doctor->id,
            'appointment_date' => $date,
            'start_time' => '15:00',
        ], dispatchEvent: false);

        $this->appointments->cancel($appointment);

        $available = $this->availability->isSlotAvailable(
            (int) $this->tenant->id,
            (int) $this->doctor->id,
            $date,
            '15:00',
        );

        $this->assertTrue($available);
    }
}
