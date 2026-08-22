<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use App\Services\Tenant\TenantNavigationService;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class ErpSidebarNavigationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function manufacturing_tenant_finance_sidebar_shows_lite_links_only(): void
    {
        $tenant = $this->provisionManufacturingTenant();

        $response = $this->actingAs($tenant)->get(route('finance.dashboard'));

        $response->assertOk();
        $sidebar = $this->extractModuleSidebarHtml((string) $response->getContent());

        $this->assertStringContainsString(route('finance.reports.profit-loss'), $sidebar);
        $this->assertStringContainsString(route('finance.expenses.index'), $sidebar);
        $this->assertStringNotContainsString(route('finance.accounts.index'), $sidebar);
        $this->assertStringNotContainsString(route('finance.journals.index'), $sidebar);
    }

    #[Test]
    public function full_erp_tenant_sales_sidebar_shows_full_surface(): void
    {
        $tenant = $this->provisionFullErpTenant();

        $response = $this->actingAs($tenant)->get(route('sales.dashboard'));

        $response->assertOk();
        $sidebar = $this->extractModuleSidebarHtml((string) $response->getContent());

        $this->assertStringContainsString('عروض الأسعار', $sidebar);
        $this->assertStringContainsString('أوامر البيع', $sidebar);
        $this->assertStringContainsString('كشف حساب العميل', $sidebar);
    }

    #[Test]
    public function retail_tenant_sales_sidebar_visible_without_manufacturing_links(): void
    {
        $tenant = $this->provisionRetailTenant();

        $response = $this->actingAs($tenant)->get(route('sales.dashboard'));

        $response->assertOk();
        $sidebar = $this->extractModuleSidebarHtml((string) $response->getContent());

        $this->assertStringContainsString('العملاء', $sidebar);
        $this->assertStringContainsString('الفواتير', $sidebar);
        $this->assertStringNotContainsString('قوائم المواد', $sidebar);
        $this->assertStringNotContainsString('انحرافات التصنيع', $sidebar);
    }

    #[Test]
    public function fleet_agents_finance_sidebar_shows_lite_links_only(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $response = $this->actingAs($tenant)->get(route('finance.dashboard'));

        $response->assertOk();
        $sidebar = $this->extractModuleSidebarHtml((string) $response->getContent());

        $this->assertStringContainsString(route('finance.reports.profit-loss'), $sidebar);
        $this->assertStringNotContainsString(route('finance.accounts.index'), $sidebar);
    }

    #[Test]
    public function manufacturing_navigation_service_exposes_full_manufacturing_shell(): void
    {
        $tenant = $this->provisionManufacturingTenant();
        $this->actingAs($tenant);

        $keys = array_map(
            static fn ($link) => $link->key,
            app(TenantNavigationService::class)->visibleLinks((int) $tenant->id, 'manufacturing')
        );

        $this->assertContains('manufacturing.bom_lists', $keys);
        $this->assertContains('manufacturing.runs', $keys);
        $this->assertFalse(app(TenantNavigationService::class)->isLinkVisible('sales.quotations', (int) $tenant->id));
    }

    private function extractModuleSidebarHtml(string $html): string
    {
        $offset = strpos($html, 'class="module-sidebar');
        $this->assertNotFalse($offset, 'Expected ERP module sidebar in response HTML.');

        $closeOffset = strpos($html, '</aside>', $offset);
        $this->assertNotFalse($closeOffset, 'Expected ERP module sidebar to close with </aside>.');

        return substr($html, $offset, $closeOffset - $offset + strlen('</aside>'));
    }

    private function provisionManufacturingTenant(): User
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance', 'inventory', 'manufacturing', 'purchases', 'sales', 'hr', 'pos',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'manufacturing',
            'domain' => 'mfg-sidebar-nav',
            'slug' => 'mfg-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        return $tenant;
    }

    private function provisionFullErpTenant(): User
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance', 'inventory', 'sales', 'purchases', 'manufacturing', 'hr', 'pos',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'full_erp',
            'domain' => 'full-erp-sidebar-nav',
            'slug' => 'full-erp-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        return $tenant;
    }

    private function provisionRetailTenant(): User
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance', 'inventory', 'sales', 'pos', 'crm', 'purchases',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'retail',
            'domain' => 'retail-sidebar-nav',
            'slug' => 'retail-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        return $tenant;
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
            'domain' => 'fleet-sidebar-nav',
            'slug' => 'fleet-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        return $tenant;
    }
}
