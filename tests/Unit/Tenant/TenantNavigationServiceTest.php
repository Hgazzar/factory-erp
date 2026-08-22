<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Services\Tenant\TenantNavigationService;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantNavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantNavigationService $navigation;

    protected function migrateFreshUsing(): array
    {
        return [
            '--drop-views' => false,
            '--drop-types' => false,
            '--path' => base_path('tests/database/migrations'),
            '--realpath' => true,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
        $this->navigation = app(TenantNavigationService::class);
    }

    #[Test]
    public function fleet_agents_with_pos_sees_only_store_surface_links_in_pos_shell(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $posLinks = $this->navigation->visibleLinks((int) $tenant->id, 'pos');
        $posKeys = array_map(static fn ($link) => $link->key, $posLinks);

        $this->assertSame(['pos.orders', 'pos.store_settings'], $posKeys);
    }

    #[Test]
    public function fleet_agents_does_not_see_pos_cashier(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $this->assertFalse($this->navigation->isLinkVisible('pos.cashier', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('pos.dashboard', (int) $tenant->id));
    }

    #[Test]
    public function retail_with_pos_sees_full_pos_surface_including_cashier(): void
    {
        $tenant = $this->makeTenant('retail', ['core', 'finance', 'inventory', 'sales', 'pos', 'crm', 'purchases'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $this->assertTrue($this->navigation->isLinkVisible('pos.cashier', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('pos.dashboard', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('pos.receipts', (int) $tenant->id));
    }

    #[Test]
    public function full_erp_sees_all_registered_pos_links_when_module_enabled(): void
    {
        $tenant = $this->makeTenant('full_erp', ['core', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        foreach (config('navigation.surfaces')['pos.full'] ?? [] as $linkKey) {
            $this->assertTrue(
                $this->navigation->isLinkVisible((string) $linkKey, (int) $tenant->id),
                "Expected full_erp to see link [{$linkKey}]"
            );
        }
    }

    #[Test]
    public function online_store_feature_gate_hides_pos_orders_when_disabled(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos']);

        $this->actingAs($tenant);

        $this->assertFalse($this->navigation->isLinkVisible('pos.orders', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('pos.store_settings', (int) $tenant->id));
    }

    #[Test]
    public function primary_shell_is_fleet_for_fleet_agents(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance']);

        $this->assertSame('fleet', $this->navigation->primaryShell((int) $tenant->id));
    }

    #[Test]
    public function nurseries_default_home_route_is_nursery_dashboard(): void
    {
        $tenant = $this->makeTenant('nurseries', ['core', 'nursery', 'finance', 'hr']);

        $this->actingAs($tenant);

        $this->assertSame('nursery', $this->navigation->primaryShell((int) $tenant->id));
        $this->assertSame(
            route('nursery.dashboard', absolute: false),
            $this->navigation->defaultHomeRoute($tenant)
        );
    }

    #[Test]
    public function retail_default_home_route_remains_erp_dashboard(): void
    {
        $tenant = $this->makeTenant('retail', ['core', 'finance', 'pos', 'inventory', 'sales']);

        $this->actingAs($tenant);

        $this->assertSame(
            route('dashboard', absolute: false),
            $this->navigation->defaultHomeRoute($tenant)
        );
    }

    #[Test]
    public function navigation_target_routes_are_registered_in_the_application(): void
    {
        $this->assertTrue(Route::has('fleet.dashboard'));
        $this->assertTrue(Route::has('pos.orders.index'));
        $this->assertTrue(Route::has('pos.cashier'));
        $this->assertTrue(Route::has('settings.store.edit'));
    }

    #[Test]
    public function fleet_agents_sees_fleet_ops_links_when_module_enabled(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance']);

        $this->actingAs($tenant);

        $fleetLinks = $this->navigation->visibleLinks((int) $tenant->id, 'fleet');
        $fleetKeys = array_map(static fn ($link) => $link->key, $fleetLinks);

        $this->assertContains('fleet.dashboard', $fleetKeys);
        $this->assertContains('fleet.routes', $fleetKeys);
        $this->assertNotContains('pos.cashier', $fleetKeys);
    }

    #[Test]
    public function module_gate_hides_finance_links_when_finance_disabled(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet']);

        $this->actingAs($tenant);

        $this->assertFalse($this->navigation->isLinkVisible('finance.dashboard', (int) $tenant->id));
    }

    #[Test]
    public function quick_links_for_fleet_agents_include_fleet_dashboard_not_pos_cashier(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $quickLinks = $this->navigation->quickLinks((int) $tenant->id);
        $routes = array_column($quickLinks, 'route');

        $this->assertContains('fleet.dashboard', $routes);
        $this->assertContains('pos.orders.index', $routes);
        $this->assertNotContains('pos.cashier', $routes);
    }

    #[Test]
    public function launcher_cards_for_fleet_agents_include_icon_metadata(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $cards = $this->navigation->visibleModuleLauncherCards((int) $tenant->id);

        $this->assertNotEmpty($cards);
        $this->assertNotSame('', $cards[0]['icon'] ?? '');
        $this->assertNotSame('', $cards[0]['iconBg'] ?? '');
    }

    #[Test]
    public function manufacturing_profile_exposes_full_manufacturing_and_lite_finance_surfaces(): void
    {
        $tenant = $this->makeTenant('manufacturing', [
            'core', 'finance', 'inventory', 'manufacturing', 'purchases', 'sales', 'hr',
        ]);

        $this->actingAs($tenant);

        $this->assertTrue($this->navigation->isLinkVisible('manufacturing.bom_lists', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('sales.invoices', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('sales.quotations', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('finance.expenses', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('finance.accounts', (int) $tenant->id));
    }

    #[Test]
    public function fleet_agents_dashboard_module_visibility_matches_launchers(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $this->assertTrue($this->navigation->isDashboardModuleVisible('fleet', (int) $tenant->id));
        $this->assertTrue($this->navigation->isDashboardModuleVisible('finance', (int) $tenant->id));
        $this->assertTrue($this->navigation->isDashboardModuleVisible('pos', (int) $tenant->id));
        $this->assertFalse($this->navigation->isDashboardModuleVisible('inventory', (int) $tenant->id));
        $this->assertFalse($this->navigation->usesUnfilteredNavigation((int) $tenant->id));
        $this->assertTrue($this->navigation->isDashboardReportsWidgetVisible((int) $tenant->id));
    }

    #[Test]
    public function retail_profile_exposes_full_sales_surface(): void
    {
        $tenant = $this->makeTenant('retail', [
            'core', 'finance', 'inventory', 'sales', 'pos', 'crm', 'purchases',
        ]);

        $this->actingAs($tenant);

        $this->assertTrue($this->navigation->isLinkVisible('sales.quotations', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('purchases.reports', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('manufacturing.runs', (int) $tenant->id));
    }

    #[Test]
    public function restaurants_profile_uses_pos_shell_with_full_pos_and_lite_inventory(): void
    {
        $tenant = $this->makeTenant('restaurants', [
            'core', 'finance', 'inventory', 'purchases', 'pos', 'hr',
        ], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $this->assertSame('pos', $this->navigation->primaryShell((int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('pos.cashier', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('inventory.items', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('inventory.price_lists', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('sales.invoices', (int) $tenant->id));
        $this->assertTrue($this->navigation->isLinkVisible('finance.expenses', (int) $tenant->id));
        $this->assertFalse($this->navigation->isLinkVisible('finance.accounts', (int) $tenant->id));

        $quickRoutes = array_column($this->navigation->quickLinks((int) $tenant->id), 'route');
        $this->assertContains('pos.dashboard', $quickRoutes);
        $this->assertContains('finance.reports.profit-loss', $quickRoutes);
    }

    #[Test]
    public function visible_module_launchers_for_fleet_agents_include_fleet_not_inventory(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $launchers = $this->navigation->visibleModuleLaunchers((int) $tenant->id);
        $modules = array_column($launchers, 'module');

        $this->assertContains('fleet', $modules);
        $this->assertContains('pos', $modules);
        $this->assertNotContains('inventory', $modules);
        $this->assertNotContains('manufacturing', $modules);
    }

    #[Test]
    public function fleet_agents_finance_dashboard_actions_include_dashboard_and_expenses_only(): void
    {
        $tenant = $this->makeTenant('fleet_agents', ['core', 'fleet', 'finance', 'pos'], [
            StoreFeatureKeys::ONLINE_STORE,
        ]);

        $this->actingAs($tenant);

        $actions = $this->navigation->visibleDashboardActions('finance', (int) $tenant->id);
        $keys = array_map(static fn ($action) => $action->key, $actions);

        $this->assertSame(['finance.dashboard', 'finance.expenses'], $keys);
        $this->assertSame('لوحة', $actions[0]->label);
        $this->assertSame('مصروفات', $actions[1]->label);
    }

    #[Test]
    public function full_erp_finance_dashboard_actions_include_accounts_and_journals(): void
    {
        $tenant = $this->makeTenant('full_erp', [
            'core', 'finance', 'inventory', 'sales', 'purchases', 'manufacturing', 'hr', 'crm', 'pos',
        ]);

        $this->actingAs($tenant);

        $keys = array_map(
            static fn ($action) => $action->key,
            $this->navigation->visibleDashboardActions('finance', (int) $tenant->id),
        );

        $this->assertContains('finance.dashboard', $keys);
        $this->assertContains('finance.accounts', $keys);
        $this->assertContains('finance.journals', $keys);
        $this->assertContains('finance.expenses', $keys);
    }

    /**
     * @param  list<string>  $modules
     * @param  list<string>  $features
     */
    private function makeTenant(string $nicheKey, array $modules, array $features = []): User
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, $modules);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => $nicheKey,
            'domain' => "test-{$nicheKey}",
            'slug' => "test-{$nicheKey}",
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        foreach ($features as $featureKey) {
            DB::table('tenant_features')->insert([
                'tenant_id' => $tenant->id,
                'feature_key' => $featureKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        return $tenant;
    }
}
