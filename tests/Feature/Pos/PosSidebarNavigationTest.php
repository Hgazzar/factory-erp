<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class PosSidebarNavigationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function fleet_agents_pos_sidebar_shows_store_surface_links_only(): void
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'fleet', 'finance', 'pos',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-pos-nav',
            'slug' => 'fleet-pos-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        $response = $this->actingAs($tenant)->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertSee(route('pos.orders.index'), false);
        $response->assertSee(route('settings.store.edit'), false);
        $response->assertDontSee(route('pos.cashier'), false);
        $response->assertDontSee(route('pos.products.index'), false);
        $response->assertDontSee(route('pos.receipts.index'), false);
        $response->assertDontSee(route('pos.sessions.index'), false);
    }

    #[Test]
    public function retail_pos_sidebar_shows_full_pos_surface(): void
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance', 'inventory', 'sales', 'pos', 'crm', 'purchases',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'retail',
            'domain' => 'retail-pos-nav',
            'slug' => 'retail-pos-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        $response = $this->actingAs($tenant)->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertSee(route('pos.dashboard'), false);
        $response->assertSee(route('pos.cashier'), false);
        $response->assertSee(route('pos.products.index'), false);
        $response->assertSee(route('pos.receipts.index'), false);
    }

    #[Test]
    public function fleet_agents_pos_sidebar_uses_niche_store_order_label(): void
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'fleet', 'finance', 'pos',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-pos-label',
            'slug' => 'fleet-pos-label',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        $ordersLabel = app(\App\Services\Store\StoreNicheCapabilities::class)
            ->ordersNavLabel('fleet_agents');

        $response = $this->actingAs($tenant)->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertSee($ordersLabel, false);
    }
}
