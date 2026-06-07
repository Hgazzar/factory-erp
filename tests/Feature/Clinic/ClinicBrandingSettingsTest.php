<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\TenantSetting;
use App\Services\Tenant\TenantFeatureRegistry;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicBrandingSettingsTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemModuleSeeder::class);
        app(TenantFeatureRegistry::class)->syncCatalogKeys(
            (int) $this->tenant->id,
            ['clinic_patient_portal'],
            ['clinic_patient_portal'],
        );
    }

    #[Test]
    public function clinic_owner_can_save_branding_to_tenant_settings(): void
    {
        $this->get(route('clinic.settings.index', ['tab' => 'branding']))
            ->assertOk()
            ->assertSee('تخصيص الألوان');

        $this->put(route('clinic.settings.branding.update'), [
            'display_name' => 'عيادة النور',
            'theme_primary_color' => '#1d4ed8',
            'theme_secondary_color' => '#bfdbfe',
        ])->assertRedirect(route('clinic.settings.index', ['tab' => 'branding']));

        $this->assertDatabaseHas('tenant_settings', [
            'tenant_user_id' => $this->tenant->id,
            'display_name' => 'عيادة النور',
            'theme_primary_color' => '#1d4ed8',
            'theme_secondary_color' => '#bfdbfe',
        ]);

        TenantSetting::forTenant((int) $this->tenant->id);
        $this->assertTrue(
            TenantSetting::query()->where('tenant_user_id', $this->tenant->id)->exists()
        );
    }
}
