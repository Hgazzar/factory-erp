<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Clinic;

use App\Models\Clinic\MedicalAttachment;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicMedicalAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicManualPrescriptionUploadTest extends ClinicTestCase
{
    #[Test]
    public function stores_encrypted_manual_prescription_linked_to_appointment(): void
    {
        Storage::fake('local');

        $patient = Patient::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'PAT-00099',
            'name' => 'Test Patient',
        ]);

        $appointment = app(\App\Services\Clinic\ClinicAppointmentService::class)->schedule(
            (int) $this->tenant->id,
            [
                'patient_id' => $patient->id,
                'appointment_date' => now()->toDateString(),
                'start_time' => '10:00',
            ],
        );

        $file = UploadedFile::fake()->image('rx-handwritten.jpg', 800, 1200);

        $attachment = app(ClinicMedicalAttachmentService::class)->storeManualPrescriptionImage(
            (int) $this->tenant->id,
            $patient,
            $file,
            $appointment->id,
            (int) $this->tenant->id,
        );

        $this->assertSame(MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION, $attachment->category);
        $this->assertSame($appointment->id, $attachment->clinic_appointment_id);
        $this->assertSame((int) $this->tenant->id, $attachment->user_id);
        Storage::disk('local')->assertExists($attachment->storage_path);
    }
}
