<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\TenantProfile;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicSidebarNavigationTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $this->tenant->id, [
            'core', 'clinic', 'finance', 'hr',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'medical_clinics',
            'domain' => 'clinic-sidebar-nav',
            'slug' => 'clinic-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);
    }

    #[Test]
    public function medical_clinics_sidebar_shows_full_clinic_ops_links_for_owner(): void
    {
        $response = $this->get(route('clinic.dashboard'));

        $response->assertOk();
        $response->assertSee(route('clinic.dashboard'), false);
        $response->assertSee(route('clinic.appointments.index'), false);
        $response->assertSee(route('clinic.patients.index'), false);
        $response->assertSee(route('clinic.prescriptions.index'), false);
        $response->assertSee(route('clinic.services.index'), false);
        $response->assertSee(route('clinic.doctor-schedules.index'), false);
        $response->assertSee(route('clinic.settings.index'), false);
        $response->assertDontSee(route('fleet.dashboard'), false);
        $response->assertDontSee(route('nursery.dashboard'), false);
    }
}
