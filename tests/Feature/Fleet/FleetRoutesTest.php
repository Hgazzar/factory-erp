<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetRoutesTest extends FleetTestCase
{
    #[Test]
    public function admin_can_create_route_and_mark_stop_visited(): void
    {
        $agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب 1',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);

        $customerA = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل أ',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);

        $customerB = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل ب',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);

        $today = now()->toDateString();

        $this->get(route('fleet.routes.index', ['date' => $today]))
            ->assertOk()
            ->assertSee('خطوط السير');

        $this->post(route('fleet.routes.store'), [
            'agent_id' => $agent->id,
            'route_date' => $today,
            'customer_ids' => [$customerA->id, $customerB->id],
            'notes' => 'جولة صباحية',
        ])->assertRedirect();

        $route = FleetRoute::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($route);
        $this->assertSame(FleetRoute::STATUS_PLANNED, $route->status);
        $this->assertSame(2, $route->stops()->count());

        $this->get(route('fleet.routes.show', $route))
            ->assertOk()
            ->assertSee('عميل أ')
            ->assertSee('عميل ب');

        $stop = FleetRouteStop::query()->where('route_id', $route->id)->orderBy('sort_order')->first();
        $this->assertNotNull($stop);

        $this->patch(route('fleet.route-stops.status', $stop), ['status' => 'visited'])
            ->assertRedirect();

        $this->assertDatabaseHas('fleet_route_stops', [
            'id' => $stop->id,
            'status' => FleetRouteStop::STATUS_VISITED,
        ]);

        $this->post(route('fleet.routes.start', $route))->assertRedirect();
        $route->refresh();
        $this->assertSame(FleetRoute::STATUS_IN_PROGRESS, $route->status);

        $this->get(route('fleet.dashboard'))
            ->assertOk()
            ->assertSee('خطوط السير اليوم');
    }
}
