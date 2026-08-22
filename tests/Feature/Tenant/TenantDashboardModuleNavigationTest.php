<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class TenantDashboardModuleNavigationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function fleet_agents_dashboard_hides_inventory_and_manufacturing_module_cards(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $response = $this->actingAs($tenant)->get(route('dashboard'));

        $response->assertOk();

        $modulesGrid = $this->extractModulesGridHtml((string) $response->getContent());

        $this->assertStringNotContainsString(route('inventory.dashboard'), $modulesGrid);
        $this->assertStringNotContainsString(route('manufacturing.dashboard'), $modulesGrid);
        $this->assertStringNotContainsString(route('sales.dashboard'), $modulesGrid);
        $this->assertStringContainsString(route('finance.dashboard'), $modulesGrid);
        $this->assertStringContainsString(route('pos.dashboard'), $modulesGrid);
    }

    #[Test]
    public function retail_dashboard_shows_sales_and_pos_but_not_manufacturing(): void
    {
        $tenant = $this->provisionRetailTenant();

        $response = $this->actingAs($tenant)->get(route('dashboard'));

        $response->assertOk();

        $modulesGrid = $this->extractModulesGridHtml((string) $response->getContent());

        $this->assertStringContainsString(route('sales.dashboard'), $modulesGrid);
        $this->assertStringContainsString(route('pos.dashboard'), $modulesGrid);
        $this->assertStringContainsString(route('crm.dashboard'), $modulesGrid);
        $this->assertStringNotContainsString(route('manufacturing.dashboard'), $modulesGrid);
    }

    #[Test]
    public function fleet_agents_finance_card_hides_accounts_and_journals_quick_actions(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $response = $this->actingAs($tenant)->get(route('dashboard'));

        $response->assertOk();

        $modulesGrid = $this->extractModulesGridHtml((string) $response->getContent());

        $this->assertStringContainsString(route('finance.dashboard'), $modulesGrid);
        $this->assertStringContainsString(route('finance.expenses.index'), $modulesGrid);
        $this->assertStringNotContainsString(route('finance.accounts.index'), $modulesGrid);
        $this->assertStringNotContainsString(route('finance.journals.index'), $modulesGrid);
    }

    #[Test]
    public function fleet_agents_dashboard_hides_placeholder_document_library_cards(): void
    {
        $tenant = $this->provisionFleetAgentsTenant();

        $response = $this->actingAs($tenant)->get(route('dashboard'));

        $response->assertOk();
        $modulesGrid = $this->extractModulesGridHtml((string) $response->getContent());

        $this->assertStringNotContainsString('مكتبة المستندات', $modulesGrid);
        $this->assertStringNotContainsString('>التخطيط<', $modulesGrid);
    }

    private function extractModulesGridHtml(string $html): string
    {
        $titleOffset = strpos($html, 'جميع الوحدات');
        $this->assertNotFalse($titleOffset, 'Expected dashboard modules grid heading.');

        return substr($html, $titleOffset, 28000);
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
            'domain' => 'fleet-dash-modules',
            'slug' => 'fleet-dash-modules',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

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
            'domain' => 'retail-dash-modules',
            'slug' => 'retail-dash-modules',
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
