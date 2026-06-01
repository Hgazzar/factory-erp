<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\MedicalAttachment;
use App\Models\Clinic\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * مرفقات طبية مشفّرة — tenant + patient معزولة.
 */
final class ClinicMedicalAttachmentService
{
    private const DISK = 'local';

    private const MAX_BYTES = 15_728_640;

    public function __construct(
        private readonly ClinicPhiAuditService $audit,
    ) {}

    public function store(
        int $tenantUserId,
        Patient $patient,
        UploadedFile $file,
        string $category,
        ?int $uploadedBy = null,
        ?int $appointmentId = null,
    ): MedicalAttachment {
        $category = in_array($category, [
            MedicalAttachment::CATEGORY_XRAY,
            MedicalAttachment::CATEGORY_LAB,
            MedicalAttachment::CATEGORY_IMAGE,
            MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION,
            MedicalAttachment::CATEGORY_OTHER,
        ], true) ? $category : MedicalAttachment::CATEGORY_OTHER;

        if ($category === MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        } else {
            $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('حجم الملف يتجاوز الحد المسموح (15MB).');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';

        if (! in_array($mime, $allowed, true)) {
            throw new InvalidArgumentException(
                $category === MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION
                    ? 'الروشتة اليدوية يجب أن تكون صورة (JPEG/PNG/WebP).'
                    : 'نوع الملف غير مدعوم. PDF أو صور فقط.'
            );
        }

        $resolvedAppointmentId = $this->resolveAppointmentId($tenantUserId, $patient, $appointmentId);

        $path = sprintf(
            'clinic-secure/%d/patients/%d/%s.enc',
            $tenantUserId,
            $patient->id,
            uniqid('att_', true)
        );

        $encrypted = Crypt::encryptString($file->get());

        if (! Storage::disk(self::DISK)->put($path, $encrypted)) {
            throw new RuntimeException('تعذّر حفظ المرفق.');
        }

        $record = MedicalAttachment::query()->create([
            'user_id' => $tenantUserId,
            'patient_id' => $patient->id,
            'clinic_appointment_id' => $resolvedAppointmentId,
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $mime,
            'size_bytes' => (int) $file->getSize(),
            'uploaded_by' => $uploadedBy,
        ]);

        $auditAction = $category === MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION
            ? 'manual_prescription_uploaded'
            : 'medical_attachment_uploaded';

        $this->audit->logPatientView($patient, $auditAction);

        return $record;
    }

    public function storeManualPrescriptionImage(
        int $tenantUserId,
        Patient $patient,
        UploadedFile $file,
        ?int $appointmentId = null,
        ?int $uploadedBy = null,
    ): MedicalAttachment {
        return $this->store(
            $tenantUserId,
            $patient,
            $file,
            MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION,
            $uploadedBy,
            $appointmentId,
        );
    }

    public function decryptContents(MedicalAttachment $attachment): string
    {
        if (! Storage::disk(self::DISK)->exists($attachment->storage_path)) {
            throw new RuntimeException('الملف غير موجود.');
        }

        $encrypted = Storage::disk(self::DISK)->get($attachment->storage_path);

        return Crypt::decryptString($encrypted);
    }

    public function delete(MedicalAttachment $attachment): void
    {
        if (Storage::disk(self::DISK)->exists($attachment->storage_path)) {
            Storage::disk(self::DISK)->delete($attachment->storage_path);
        }

        $attachment->delete();
    }

    private function resolveAppointmentId(int $tenantUserId, Patient $patient, ?int $appointmentId): ?int
    {
        if ($appointmentId === null || $appointmentId < 1) {
            return null;
        }

        $valid = Appointment::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('patient_id', $patient->id)
            ->whereKey($appointmentId)
            ->exists();

        if (! $valid) {
            throw new InvalidArgumentException('الحجز غير مرتبط بهذا المريض.');
        }

        return $appointmentId;
    }
}
