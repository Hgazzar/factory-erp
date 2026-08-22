<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantDashboardPackageService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Services\Tenant\TenantNavigationService;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class TenantDashboardNavigationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function fleet_agents_dashboard_quick_links_use_navigation_profile(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $this->actingAs($tenant);

        $quickLinks = app(TenantDashboardPackageService::class)->buildForViewer($tenant)['quick_links'];
        $routes = array_column($quickLinks, 'route');

        $this->assertContains('fleet.dashboard', $routes);
        $this->assertContains('pos.orders.index', $routes);
        $this->assertNotContains('pos.dashboard', $routes);
        $this->assertNotContains('sales.dashboard', $routes);
    }

    #[Test]
    public function fleet_agents_module_launcher_modal_excludes_inventory_and_sales(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $response = $this->actingAs($tenant)->get(route('dashboard'));

        $response->assertOk();

        $html = (string) $response->getContent();
        $modalOffset = strpos($html, 'id="erpModuleLauncherModal"');
        $this->assertNotFalse($modalOffset);

        $modalHtml = substr($html, $modalOffset, 12000);

        $this->assertStringContainsString(route('fleet.dashboard'), $modalHtml);
        $this->assertStringContainsString(route('finance.dashboard'), $modalHtml);
        $this->assertStringNotContainsString(route('inventory.dashboard'), $modalHtml);
        $this->assertStringNotContainsString(route('sales.dashboard'), $modalHtml);
        $this->assertStringNotContainsString(route('purchases.dashboard'), $modalHtml);
    }

    #[Test]
    public function retail_dashboard_quick_links_include_pos_and_crm(): void
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance', 'inventory', 'sales', 'pos', 'crm', 'purchases',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'retail',
            'domain' => 'retail-dash-nav',
            'slug' => 'retail-dash-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $this->actingAs($tenant);

        $routes = array_column(
            app(TenantDashboardPackageService::class)->buildForViewer($tenant)['quick_links'],
            'route',
        );

        $this->assertContains('pos.dashboard', $routes);
        $this->assertContains('crm.dashboard', $routes);
    }

    #[Test]
    public function navigation_service_launcher_cards_include_icons(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $this->actingAs($tenant);

        $cards = app(TenantNavigationService::class)->visibleModuleLauncherCards((int) $tenant->id);

        $this->assertNotEmpty($cards);
        $this->assertArrayHasKey('icon', $cards[0]);
        $this->assertArrayHasKey('iconBg', $cards[0]);
        $this->assertNotSame('', $cards[0]['icon']);
    }

    private function provisionFleetAgentsTenant(): User
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'fleet', 'finance', 'pos',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-dash-nav',
            'slug' => 'fleet-dash-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        return $tenant;
    }
}
