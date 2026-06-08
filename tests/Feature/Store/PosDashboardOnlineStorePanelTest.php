<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\PosDevice;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class PosDashboardOnlineStorePanelTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        $tenantId = (int) $this->tenant->id;

        PosDevice::withoutGlobalScopes()->create([
            'user_id' => $tenantId,
            'name' => 'Main POS',
            'mac_address' => fake()->unique()->macAddress(),
            'status' => PosDevice::STATUS_ACTIVE,
            'warehouse_id' => $this->makePosDevice()->warehouse_id,
        ]);
    }

    #[Test]
    public function pos_dashboard_shows_online_store_panel_when_feature_enabled(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => 'dash-store',
            'slug' => 'dash-store',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->tenant)->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertSee('Online Store Channel', false);
        $response->assertSee('مبيعات اليوم', false);
        $response->assertSee('storeMetrics', false);
    }

    #[Test]
    public function pos_dashboard_hides_online_store_panel_without_feature(): void
    {
        TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'retail',
            'domain' => 'no-panel',
            'slug' => 'no-panel',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->tenant)->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Online Store Channel', false);
    }
}
