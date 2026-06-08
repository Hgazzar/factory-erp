<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetProduct;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetFoundationTest extends FleetTestCase
{
    #[Test]
    public function admin_can_view_fleet_dashboard_and_crud_entities(): void
    {
        $this->get(route('fleet.dashboard'))
            ->assertOk()
            ->assertSee('لوحة التحكم')
            ->assertSee('مناديب نشطون')
            ->assertSee('خطوط السير اليوم');

        $this->get(route('fleet.agents.index'))->assertOk()->assertSee('إضافة مندوب');

        $this->post(route('fleet.agents.store'), [
            'name' => 'مندوب تجريبي',
            'phone' => '0501234567',
        ])->assertRedirect(route('fleet.agents.index'));

        $agent = FleetAgent::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($agent);
        $this->assertSame('مندوب تجريبي', $agent->name);

        $this->post(route('fleet.customers.store'), [
            'name' => 'عميل ميدان',
            'phone' => '0509876543',
            'assigned_agent_id' => $agent->id,
        ])->assertRedirect(route('fleet.customers.index'));

        $this->assertDatabaseHas('fleet_customers', [
            'user_id' => $this->tenant->id,
            'name' => 'عميل ميدان',
            'assigned_agent_id' => $agent->id,
        ]);

        $this->post(route('fleet.products.store'), [
            'name' => 'صنف عهدة',
            'sku' => 'FL-001',
            'sale_price' => 99.5,
            'is_active' => '1',
        ])->assertRedirect(route('fleet.products.index'));

        $product = FleetProduct::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($product);
        $this->assertSame('صنف عهدة', $product->name);

        $customer = FleetCustomer::query()->where('user_id', $this->tenant->id)->first();
        $this->get(route('fleet.customers.edit', $customer))->assertOk()->assertSee('عميل ميدان');
        $this->get(route('fleet.products.edit', $product))->assertOk()->assertSee('صنف عهدة');
    }

    #[Test]
    public function fleet_module_disabled_returns_forbidden(): void
    {
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncEnabledModuleKeys(
            (int) $this->tenant->id,
            ['core', 'finance']
        );

        $this->get(route('fleet.dashboard'))->assertRedirect(route('dashboard'));
    }
}
