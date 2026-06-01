<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Clinic;

use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicAllergyAlertService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicAllergyAlertServiceTest extends ClinicTestCase
{
    #[Test]
    public function detects_medication_matching_allergy(): void
    {
        $patient = Patient::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'PAT-00001',
            'name' => 'Test',
            'allergies' => 'بنسلين, أسبرين',
        ]);

        $alerts = app(ClinicAllergyAlertService::class)->checkMedication($patient, 'Amoxicillin بنسلين');

        $this->assertNotEmpty($alerts);
    }

    #[Test]
    public function no_alert_when_no_match(): void
    {
        $patient = Patient::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'PAT-00002',
            'name' => 'Test2',
            'allergies' => 'أسبرين',
        ]);

        $alerts = app(ClinicAllergyAlertService::class)->checkMedication($patient, 'Paracetamol');

        $this->assertSame([], $alerts);
    }
}
