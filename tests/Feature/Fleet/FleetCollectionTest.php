<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetCollectionTest extends FleetTestCase
{
    #[Test]
    public function admin_can_collect_from_custody_and_mark_route_stop_visited(): void
    {
        $agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب تحصيل',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);

        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل زيارة',
            'phone' => '0500000000',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);

        $product = FleetProduct::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'منتج ميداني',
            'sku' => 'COL-1',
            'sale_price' => 100,
            'is_active' => true,
        ]);

        $this->post(route('fleet.custody.store'), [
            'agent_id' => $agent->id,
            'issued_on' => now()->toDateString(),
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertRedirect();

        $issue = FleetCustodyIssue::query()->where('user_id', $this->tenant->id)->first();
        $this->post(route('fleet.custody.confirm', $issue))->assertRedirect();

        $this->post(route('fleet.routes.store'), [
            'agent_id' => $agent->id,
            'route_date' => now()->toDateString(),
            'customer_ids' => [$customer->id],
        ])->assertRedirect();

        $route = FleetRoute::query()->where('user_id', $this->tenant->id)->first();
        $stop = FleetRouteStop::query()->where('route_id', $route->id)->first();
        $this->assertNotNull($stop);

        $this->get(route('fleet.collections.index'))->assertOk()->assertSee('التحصيل الميداني');

        $this->post(route('fleet.collections.store'), [
            'agent_id' => $agent->id,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'route_stop_id' => $stop->id,
            'collected_on' => now()->toDateString(),
            'payment_method' => 'cod',
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $collection = FleetCollection::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($collection);
        $this->assertSame(200.0, (float) $collection->subtotal);

        $this->post(route('fleet.collections.confirm', $collection))->assertRedirect();
        $collection->refresh();
        $this->assertSame(FleetCollection::STATUS_CONFIRMED, $collection->status);

        $stop->refresh();
        $this->assertSame(FleetRouteStop::STATUS_VISITED, $stop->status);

        $this->get(route('fleet.custody.balances.agent', $agent))
            ->assertOk()
            ->assertSee('3');

        $this->get(route('fleet.dashboard'))
            ->assertOk()
            ->assertSee('تحصيلات اليوم');
    }
}
