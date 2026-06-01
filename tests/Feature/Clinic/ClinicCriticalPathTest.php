<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\Prescription;
use App\Models\JournalEntry;
use App\Services\Clinic\ClinicServiceCatalogService;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

/**
 * المسار الحرج: مريض → حجز → تحصيل متعدد الخدمات → روشتة.
 */
final class ClinicCriticalPathTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $tenantId = (int) $this->tenant->id;
        DefaultLedgerAccounts::salesRevenueForTenant($tenantId);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', $tenantId);
        DefaultLedgerAccounts::vatPayableForTenant($tenantId);

        app(ClinicServiceCatalogService::class)->seedDefaults($tenantId);
    }

    #[Test]
    public function critical_path_patient_booking_collection_prescription(): void
    {
        $tenantId = (int) $this->tenant->id;

        $createPatient = $this->post(route('clinic.patients.store'), [
            'name' => 'أحمد محمود',
            'phone' => '01012345678',
            'national_id' => '29001011234567',
        ]);
        $createPatient->assertRedirect();
        $createPatient->assertSessionHas('success');

        $patient = Patient::query()->where('user_id', $tenantId)->where('name', 'أحمد محمود')->first();
        $this->assertNotNull($patient);

        $schedule = $this->post(route('clinic.appointments.store'), [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
        ]);
        $schedule->assertRedirect();
        $schedule->assertSessionHas('success');

        $appointment = Appointment::query()
            ->where('user_id', $tenantId)
            ->where('patient_id', $patient->id)
            ->where('status', Appointment::STATUS_PENDING)
            ->first();
        $this->assertNotNull($appointment);

        $services = app(ClinicServiceCatalogService::class)->activeForTenant($tenantId);
        $serviceIds = $services->take(2)->pluck('id')->all();
        $this->assertCount(2, $serviceIds);

        $collect = $this->patch(route('clinic.appointments.status', $appointment), [
            'action' => 'complete_paid',
            'payment_method' => 'cash',
            'service_ids' => $serviceIds,
        ]);
        $collect->assertRedirect();
        $collect->assertSessionHas('success');
        $collect->assertSessionHas('receipt_appointment_id', $appointment->id);

        $appointment->refresh();
        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->status);
        $this->assertNotNull($appointment->paid_at);
        $this->assertNotNull($appointment->journal_entry_id);
        $this->assertGreaterThan(0, (float) $appointment->fee_amount);
        $this->assertCount(2, $appointment->serviceLines()->get());

        $entry = JournalEntry::query()->find($appointment->journal_entry_id);
        $this->assertNotNull($entry);
        $this->assertEqualsWithDelta((float) $appointment->fee_amount, (float) $entry->total, 0.02);

        $prescription = $this->post(route('clinic.prescriptions.store'), [
            'patient_id' => $patient->id,
            'clinic_appointment_id' => $appointment->id,
            'diagnosis' => 'التهاب حلق حاد',
            'medications' => [
                ['name' => 'Augmentin', 'dosage' => '625mg', 'frequency' => 'مرتين يومياً', 'duration' => '5 أيام'],
            ],
        ]);
        $prescription->assertRedirect();
        $prescription->assertSessionHas('success');

        $this->assertDatabaseHas('clinic_prescriptions', [
            'user_id' => $tenantId,
            'patient_id' => $patient->id,
            'clinic_appointment_id' => $appointment->id,
        ]);

        $rx = Prescription::query()->where('patient_id', $patient->id)->first();
        $this->assertNotNull($rx);
    }

    #[Test]
    public function patient_edit_reschedule_cancel_and_pdf_routes(): void
    {
        $tenantId = (int) $this->tenant->id;

        $patient = Patient::query()->create([
            'user_id' => $tenantId,
            'code' => 'PAT-00001',
            'name' => 'سارة علي',
            'phone' => '01099998877',
        ]);

        $update = $this->put(route('clinic.patients.update', $patient), [
            'name' => 'سارة علي محمود',
            'phone' => '01088887766',
        ]);
        $update->assertRedirect(route('clinic.patients.show', $patient));
        $this->assertSame('سارة علي محمود', $patient->fresh()->name);

        $appointment = Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-TEST-0001',
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'status' => Appointment::STATUS_PENDING,
            'created_by' => $tenantId,
        ]);

        $reschedule = $this->patch(route('clinic.appointments.status', $appointment), [
            'action' => 'reschedule',
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '11:30',
        ]);
        $reschedule->assertRedirect();
        $appointment->refresh();
        $this->assertSame(now()->addDay()->toDateString(), $appointment->appointment_date->toDateString());
        $this->assertStringStartsWith('11:30', (string) $appointment->start_time);

        $cancel = $this->patch(route('clinic.appointments.status', $appointment), [
            'action' => 'cancel',
        ]);
        $cancel->assertRedirect();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);

        $paidAppointment = Appointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => 'CLN-TEST-0002',
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '14:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'fee_amount' => 300,
            'subtotal_amount' => 260.87,
            'vat_amount' => 39.13,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'created_by' => $tenantId,
        ]);

        $receiptPdf = $this->get(route('clinic.appointments.receipt.pdf', $paidAppointment));
        $receiptPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $receiptPdf->headers->get('content-type'));

        $rx = Prescription::query()->create([
            'user_id' => $tenantId,
            'patient_id' => $patient->id,
            'diagnosis' => 'سكر',
            'medications' => [['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'يومياً', 'duration' => '30 يوم']],
            'prescribed_at' => now(),
            'created_by' => $tenantId,
        ]);

        $rxPdf = $this->get(route('clinic.prescriptions.pdf', $rx));
        $rxPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $rxPdf->headers->get('content-type'));
    }
}
