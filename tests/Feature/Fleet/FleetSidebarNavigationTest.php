<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\PremiumFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class FleetSidebarNavigationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function fleet_agents_sidebar_shows_all_fleet_ops_links(): void
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'fleet', 'finance',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-sidebar-nav',
            'slug' => 'fleet-sidebar-nav',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => PremiumFeatureKeys::FLEET_FIELD_OPS,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        $response = $this->actingAs($tenant)->get(route('fleet.dashboard'));

        $response->assertOk();
        $response->assertSee(route('fleet.dashboard'), false);
        $response->assertSee(route('fleet.collections.index'), false);
        $response->assertSee(route('fleet.custody.index'), false);
        $response->assertSee(route('fleet.store-orders.index'), false);
        $response->assertSee(route('fleet.routes.index'), false);
        $response->assertSee(route('fleet.agents.index'), false);
        $response->assertSee(route('fleet.customers.index'), false);
        $response->assertSee(route('fleet.products.index'), false);
        $response->assertDontSee(route('pos.cashier'), false);
        $response->assertDontSee(route('inventory.dashboard'), false);
    }

    #[Test]
    public function fleet_sidebar_uses_niche_labels_for_agents_and_customers(): void
    {
        $tenant = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'fleet', 'finance',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-sidebar-labels',
            'slug' => 'fleet-sidebar-labels',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => PremiumFeatureKeys::FLEET_FIELD_OPS,
        ]);

        app(TenantFeatureRegistry::class)->forgetCache((int) $tenant->id);

        $lexicon = app(\App\Services\Tenant\NicheLexiconService::class);
        $agentLabel = $lexicon->label('entities.agent', (int) $tenant->id, 'المناديب');
        $customerLabel = $lexicon->label('entities.customer', (int) $tenant->id, 'العملاء');

        $response = $this->actingAs($tenant)->get(route('fleet.dashboard'));

        $response->assertOk();
        $response->assertSee($agentLabel, false);
        $response->assertSee($customerLabel, false);
    }

    #[Test]
    public function fleet_sidebar_hidden_when_module_disabled(): void
    {
        $tenant = $this->tenant;

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenant->id, [
            'core', 'finance',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'fleet-sidebar-off',
            'slug' => 'fleet-sidebar-off',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $this->actingAs($tenant)->get(route('fleet.dashboard'))
            ->assertRedirect(route('dashboard'));
    }
}
