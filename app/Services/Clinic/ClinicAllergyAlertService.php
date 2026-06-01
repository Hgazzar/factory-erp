<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Patient;

/**
 * تحذيرات الحساسية أثناء كتابة الروشتة.
 */
final class ClinicAllergyAlertService
{
    /**
     * @return list<array{term: string, allergen: string, severity: string, message: string}>
     */
    public function checkMedication(Patient $patient, string $medicationName): array
    {
        $medicationName = mb_strtolower(trim($medicationName));

        if ($medicationName === '') {
            return [];
        }

        $allergens = $this->parseAllergens($patient);

        $alerts = [];

        foreach ($allergens as $allergen) {
            if ($this->matches($medicationName, $allergen)) {
                $alerts[] = [
                    'term' => $medicationName,
                    'allergen' => $allergen,
                    'severity' => 'high',
                    'message' => "تحذير: الدواء «{$medicationName}» قد يتعارض مع حساسية مسجّلة: «{$allergen}»",
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param  list<string>  $medicationNames
     * @return list<array{term: string, allergen: string, severity: string, message: string}>
     */
    public function checkMedications(Patient $patient, array $medicationNames): array
    {
        $all = [];

        foreach ($medicationNames as $name) {
            $all = array_merge($all, $this->checkMedication($patient, $name));
        }

        return $all;
    }

    /**
     * @return list<string>
     */
    private function parseAllergens(Patient $patient): array
    {
        $raw = trim((string) ($patient->allergies ?? ''));

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[,،;\n]+/u', $raw) ?: [];

        return array_values(array_filter(array_map(
            fn (string $p) => mb_strtolower(trim($p)),
            $parts
        )));
    }

    private function matches(string $medication, string $allergen): bool
    {
        if ($allergen === '') {
            return false;
        }

        return str_contains($medication, $allergen) || str_contains($allergen, $medication);
    }
}
