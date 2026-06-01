<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\ClinicalNote;
use App\Models\Clinic\Patient;
use InvalidArgumentException;

final class ClinicClinicalNoteService
{
    public function __construct(
        private readonly ClinicPhiAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data, ?int $createdByUserId = null): ClinicalNote
    {
        $patientId = (int) ($data['patient_id'] ?? 0);

        if ($patientId < 1) {
            throw new InvalidArgumentException('المريض مطلوب.');
        }

        $patient = Patient::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->findOrFail($patientId);

        $note = ClinicalNote::query()->create([
            'user_id' => $tenantUserId,
            'patient_id' => $patientId,
            'clinic_appointment_id' => isset($data['clinic_appointment_id']) ? (int) $data['clinic_appointment_id'] : null,
            'doctor_employee_id' => isset($data['doctor_employee_id']) ? (int) $data['doctor_employee_id'] : null,
            'chief_complaint' => $this->text($data['chief_complaint'] ?? null),
            'examination' => $this->text($data['examination'] ?? null),
            'diagnosis' => $this->text($data['diagnosis'] ?? null),
            'noted_at' => now(),
            'created_by' => $createdByUserId ?? $tenantUserId,
        ]);

        $this->audit->logClinicalWrite($patient, 'clinic_clinical_note_created', $note);

        return $note->fresh(['doctor', 'appointment']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ClinicalNote $note, array $data): ClinicalNote
    {
        $note->fill([
            'chief_complaint' => array_key_exists('chief_complaint', $data) ? $this->text($data['chief_complaint']) : $note->chief_complaint,
            'examination' => array_key_exists('examination', $data) ? $this->text($data['examination']) : $note->examination,
            'diagnosis' => array_key_exists('diagnosis', $data) ? $this->text($data['diagnosis']) : $note->diagnosis,
        ]);
        $note->save();

        $patient = Patient::withoutGlobalScopes()->findOrFail($note->patient_id);
        $this->audit->logClinicalWrite($patient, 'clinic_clinical_note_updated', $note);

        return $note->fresh(['doctor', 'appointment']);
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
