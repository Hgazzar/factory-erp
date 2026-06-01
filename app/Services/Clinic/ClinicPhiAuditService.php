<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\AuditLog;
use App\Models\Clinic\ClinicalNote;
use App\Models\Clinic\Patient;
use App\Models\User;

/**
 * سجل تدقيق PHI (HIPAA-like) — استعراض وتعديل البيانات الطبية الحساسة.
 */
final class ClinicPhiAuditService
{
    public function logPatientView(Patient $patient, string $context = 'patient_profile'): void
    {
        $this->log('clinic_phi_view', $patient, [
            'context' => $context,
            'patient_code' => $patient->code,
        ]);
    }

    public function logClinicalWrite(Patient $patient, string $action, ?ClinicalNote $note = null): void
    {
        $this->log($action, $patient, [
            'clinical_note_id' => $note?->id,
        ], $note);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function log(string $action, Patient $patient, array $meta = [], ?ClinicalNote $subject = null): void
    {
        if (! auth()->check()) {
            return;
        }

        AuditLog::logModuleEvent($action, array_merge($meta, [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
        ]), $subject ?? $patient);
    }
}
