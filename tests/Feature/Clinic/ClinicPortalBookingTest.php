<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\ClinicSpecialty;
use App\Models\Clinic\DoctorSchedule;
use App\Models\Employee;
use App\Models\TenantProfile;
use App\Services\Clinic\ClinicPortalBookingService;
use Carbon\Carbon;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicPortalBookingTest extends ClinicTestCase
{
    private string $slug = 'demo-clinic';

    private Employee $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);

        $tenantId = (int) $this->tenant->id;

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'medical_clinics',
            'domain' => $this->slug,
            'slug' => $this->slug,
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant($tenantId, [
            'core', 'clinic', 'finance', 'hr',
        ]);

        $specialty = ClinicSpecialty::query()->create([
            'user_id' => $tenantId,
            'name' => 'طب عام',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->doctor = Employee::query()->create([
            'user_id' => $tenantId,
            'code' => 'DOC-PORTAL',
            'name' => 'د. نور',
            'status' => 'active',
            'clinic_role' => 'doctor',
            'clinic_specialty_id' => $specialty->id,
        ]);

        $tomorrow = now()->addDay();
        DoctorSchedule::query()->create([
            'user_id' => $tenantId,
            'doctor_employee_id' => $this->doctor->id,
            'day_of_week' => $tomorrow->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function portal_book_page_is_public(): void
    {
        $this->get(route('clinic.portal.book', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('احجز موعدك');
    }

    #[Test]
    public function portal_api_book_creates_appointment_and_dispatches_event(): void
    {
        Event::fake([ClinicAppointmentBooked::class]);

        $date = now()->addDay()->toDateString();

        $response = $this->postJson(route('clinic.portal.api.book', ['tenant_slug' => $this->slug]), [
            'patient_name' => 'خالد محمد',
            'patient_phone' => '01098765432',
            'doctor_employee_id' => $this->doctor->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('appointment.date', $date);

        $this->assertDatabaseHas('clinic_appointments', [
            'user_id' => $this->tenant->id,
            'doctor_employee_id' => $this->doctor->id,
            'booking_source' => Appointment::SOURCE_PORTAL,
            'status' => Appointment::STATUS_PENDING,
        ]);

        Event::assertDispatched(ClinicAppointmentBooked::class);
    }

    #[Test]
    public function patient_can_open_manage_link_and_cancel_or_reschedule_before_cutoff(): void
    {
        // Outside portal manage cutoff (default 24h before appointment).
        $date = now()->addDays(3)->toDateString();
        $newDate = now()->addDays(10)->toDateString();

        foreach ([$date, $newDate] as $scheduleDate) {
            $day = Carbon::parse($scheduleDate);
            DoctorSchedule::query()->firstOrCreate(
                [
                    'user_id' => (int) $this->tenant->id,
                    'doctor_employee_id' => $this->doctor->id,
                    'day_of_week' => $day->dayOfWeek,
                ],
                [
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'slot_duration_minutes' => 30,
                    'is_active' => true,
                ]
            );
        }

        $book = $this->postJson(route('clinic.portal.api.book', ['tenant_slug' => $this->slug]), [
            'patient_name' => 'مريض إدارة',
            'patient_phone' => '01000000000',
            'doctor_employee_id' => $this->doctor->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
        ])->assertCreated();

        $appointment = Appointment::query()->findOrFail((int) $book->json('appointment.id'));
        $appointment->refresh();

        $token = (string) $appointment->portal_manage_token;
        $this->assertNotSame('', $token);
        $this->assertNotNull(app(ClinicPortalBookingService::class)->findByManageToken((int) $this->tenant->id, $token));

        $this->get(route('clinic.portal.book', ['tenant_slug' => $this->slug, 'manage_token' => $token]))
            ->assertOk()
            ->assertSee('إدارة موعدك');

        $this->postJson('/c/'.$this->slug.'/api/manage/'.$token.'/reschedule', [
            'appointment_id' => $appointment->id,
            'appointment_date' => $newDate,
            'start_time' => '11:00',
        ])->assertOk();

        $appointment->refresh();
        $this->assertSame($newDate, $appointment->appointment_date->toDateString());

        $this->postJson('/c/'.$this->slug.'/api/manage/'.$token.'/cancel', [
            'appointment_id' => $appointment->id,
        ])
            ->assertOk();

        $appointment->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->status);
    }

    #[Test]
    public function portal_slots_api_returns_available_times(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->getJson(route('clinic.portal.api.slots', [
            'tenant_slug' => $this->slug,
            'doctor_id' => $this->doctor->id,
            'date' => $date,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['slots' => [['start', 'end', 'label']]]);
        $this->assertNotEmpty($response->json('slots'));
    }
}
