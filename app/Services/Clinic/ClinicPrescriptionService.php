<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\Prescription;
use InvalidArgumentException;

final class ClinicPrescriptionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data, ?int $createdByUserId = null): Prescription
    {
        $patientId = (int) ($data['patient_id'] ?? 0);
        $medications = $this->normalizeMedications($data['medications'] ?? []);

        if ($patientId < 1) {
            throw new InvalidArgumentException('المريض مطلوب.');
        }

        if ($medications === []) {
            throw new InvalidArgumentException('أضف دواءً واحداً على الأقل.');
        }

        $patientExists = Patient::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($patientId)
            ->exists();

        if (! $patientExists) {
            throw new InvalidArgumentException('المريض غير موجود.');
        }

        $appointmentId = isset($data['clinic_appointment_id']) ? (int) $data['clinic_appointment_id'] : null;

        if ($appointmentId !== null && $appointmentId > 0) {
            $validAppointment = Appointment::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($appointmentId)
                ->exists();

            if (! $validAppointment) {
                throw new InvalidArgumentException('الحجز غير موجود.');
            }
        } else {
            $appointmentId = null;
        }

        $doctorId = isset($data['doctor_employee_id']) ? (int) $data['doctor_employee_id'] : null;

        return Prescription::query()->create([
            'user_id' => $tenantUserId,
            'patient_id' => $patientId,
            'doctor_employee_id' => $doctorId > 0 ? $doctorId : null,
            'clinic_appointment_id' => $appointmentId,
            'diagnosis' => isset($data['diagnosis']) ? trim((string) $data['diagnosis']) : null,
            'medications' => $medications,
            'prescribed_at' => now(),
            'created_by' => $createdByUserId ?? $tenantUserId,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>|mixed  $raw
     * @return list<array{name: string, dosage: string, frequency: string, duration: string, notes: string|null}>
     */
    private function normalizeMedications(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $normalized = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'dosage' => trim((string) ($row['dosage'] ?? '')),
                'frequency' => trim((string) ($row['frequency'] ?? '')),
                'duration' => trim((string) ($row['duration'] ?? '')),
                'notes' => ($n = trim((string) ($row['notes'] ?? ''))) !== '' ? $n : null,
            ];
        }

        return $normalized;
    }
}
