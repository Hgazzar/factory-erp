<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\MedicalAttachment;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicPatientTimelineService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicManualPrescriptionApiTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');
    }

    #[Test]
    public function api_uploads_manual_prescription_and_returns_timeline_event(): void
    {
        $patient = Patient::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'PAT-00100',
            'name' => 'مريض رفع',
        ]);

        $appointment = Appointment::query()->create([
            'user_id' => $this->tenant->id,
            'appointment_number' => 'CLN-RX-001',
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
            'created_by' => $this->tenant->id,
        ]);

        $response = $this->post(route('clinic.api.upload-manual-prescription'), [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'image' => UploadedFile::fake()->image('prescription.jpg'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('attachment.category', MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION);
        $response->assertJsonPath('timeline_event.type', 'manual_prescription');

        $this->assertDatabaseHas('clinic_medical_attachments', [
            'patient_id' => $patient->id,
            'clinic_appointment_id' => $appointment->id,
            'category' => MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION,
        ]);

        $timeline = app(ClinicPatientTimelineService::class)->build($patient);
        $this->assertTrue($timeline->contains(fn (array $e) => $e['type'] === 'manual_prescription'));
    }
}
