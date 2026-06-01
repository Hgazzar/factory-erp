<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Patient;
use App\Services\Tenant\TenantFeatureRegistry;

final class ClinicPatientService
{
    public function __construct(
        private readonly TenantFeatureRegistry $features,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data, ?int $createdByUserId = null): Patient
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('اسم المريض مطلوب.');
        }

        $insuranceEnabled = $this->features->isEnabled('clinic_medical_insurance', $tenantUserId);

        return Patient::query()->create([
            'user_id' => $tenantUserId,
            'code' => $this->nextCode($tenantUserId),
            'name' => $name,
            'phone' => $this->nullableString($data['phone'] ?? null),
            'national_id' => $this->nullableString($data['national_id'] ?? null),
            'blood_type' => $this->normalizeBloodType($data['blood_type'] ?? null),
            'medical_history_summary' => $this->nullableString($data['medical_history_summary'] ?? null),
            'clinic_insurance_company_id' => $insuranceEnabled ? $this->nullableInt($data['clinic_insurance_company_id'] ?? null) : null,
            'clinic_insurance_plan_id' => $insuranceEnabled ? $this->nullableInt($data['clinic_insurance_plan_id'] ?? null) : null,
            'insurance_card_number' => $insuranceEnabled ? $this->nullableString($data['insurance_card_number'] ?? null) : null,
            'insurance_expires_at' => $insuranceEnabled ? $this->nullableString($data['insurance_expires_at'] ?? null) : null,
            'allergies' => $this->nullableString($data['allergies'] ?? null),
            'chronic_conditions' => $this->nullableString($data['chronic_conditions'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        $insuranceEnabled = $this->features->isEnabled('clinic_medical_insurance', (int) $patient->user_id);

        $patient->fill([
            'name' => trim((string) ($data['name'] ?? $patient->name)),
            'phone' => array_key_exists('phone', $data) ? $this->nullableString($data['phone']) : $patient->phone,
            'national_id' => array_key_exists('national_id', $data) ? $this->nullableString($data['national_id']) : $patient->national_id,
            'blood_type' => array_key_exists('blood_type', $data) ? $this->normalizeBloodType($data['blood_type']) : $patient->blood_type,
            'medical_history_summary' => array_key_exists('medical_history_summary', $data)
                ? $this->nullableString($data['medical_history_summary'])
                : $patient->medical_history_summary,
            'clinic_insurance_company_id' => $insuranceEnabled && array_key_exists('clinic_insurance_company_id', $data)
                ? $this->nullableInt($data['clinic_insurance_company_id'])
                : $patient->clinic_insurance_company_id,
            'clinic_insurance_plan_id' => $insuranceEnabled && array_key_exists('clinic_insurance_plan_id', $data)
                ? $this->nullableInt($data['clinic_insurance_plan_id'])
                : $patient->clinic_insurance_plan_id,
            'insurance_card_number' => $insuranceEnabled && array_key_exists('insurance_card_number', $data)
                ? $this->nullableString($data['insurance_card_number'])
                : $patient->insurance_card_number,
            'insurance_expires_at' => $insuranceEnabled && array_key_exists('insurance_expires_at', $data)
                ? $this->nullableString($data['insurance_expires_at'])
                : $patient->insurance_expires_at?->format('Y-m-d'),
            'allergies' => array_key_exists('allergies', $data) ? $this->nullableString($data['allergies']) : $patient->allergies,
            'chronic_conditions' => array_key_exists('chronic_conditions', $data)
                ? $this->nullableString($data['chronic_conditions'])
                : $patient->chronic_conditions,
        ]);
        $patient->save();

        return $patient->fresh();
    }

    private function nextCode(int $tenantUserId): string
    {
        $count = Patient::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->count();

        return 'PAT-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeBloodType(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        if (! in_array($value, Patient::BLOOD_TYPES, true)) {
            return 'unknown';
        }

        return $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
