<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Models\TenantProfile;
use App\Models\TenantSetting;
use App\Services\Tenant\TenantBrandingService;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class TenantModuleThemeIsolationTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'nurseries',
            'domain' => 'multi-module',
            'slug' => 'multi-module',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function nursery_color_changes_do_not_affect_clinic_or_store_defaults(): void
    {
        $tenantId = (int) $this->tenant->id;
        $branding = app(TenantBrandingService::class);

        $branding->updateBranding($tenantId, [
            'theme_primary_color' => '#16a34a',
            'theme_secondary_color' => '#dcfce7',
        ], TenantBrandingService::MODULE_NURSERY);

        $nursery = $branding->branding($tenantId, null, TenantBrandingService::MODULE_NURSERY);
        $clinic = $branding->branding($tenantId, null, TenantBrandingService::MODULE_CLINIC);
        $store = $branding->branding($tenantId, null, TenantBrandingService::MODULE_STORE);

        $this->assertSame('#16a34a', $nursery['theme_primary']);
        $this->assertSame('#0d9488', $clinic['theme_primary']);
        $this->assertSame('#dc2626', $store['theme_primary']);
        $this->assertSame('#16a34a', $nursery['theme_vars']['--nursery-primary']);
        $this->assertArrayNotHasKey('--nursery-primary', $clinic['theme_vars']);
        $this->assertSame('#0d9488', $clinic['theme_vars']['--clinic-primary']);
        $this->assertSame('#dc2626', $store['theme_vars']['--store-primary']);

        $setting = TenantSetting::forTenant($tenantId);
        $this->assertSame('#16a34a', $setting->nursery_theme_primary_color);
        $this->assertNull($setting->clinic_theme_primary_color);
        $this->assertNull($setting->store_theme_primary_color);
    }
}
