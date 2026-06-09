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

final class FleetAgentApiTest extends FleetTestCase
{
    private string $slug = 'test-fleet';

    private FleetAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب API',
            'phone' => '0501234567',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);
        $this->agent->setApiPin('1234');
    }

    #[Test]
    public function agent_can_login_and_fetch_today_route(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل API',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);

        $route = FleetRoute::query()->create([
            'user_id' => (int) $this->tenant->id,
            'agent_id' => $this->agent->id,
            'route_date' => now()->toDateString(),
            'status' => FleetRoute::STATUS_PLANNED,
        ]);

        FleetRouteStop::query()->create([
            'user_id' => (int) $this->tenant->id,
            'route_id' => $route->id,
            'customer_id' => $customer->id,
            'sort_order' => 1,
            'status' => FleetRouteStop::STATUS_PENDING,
        ]);

        $login = $this->postJson(route('api.v1.fleet.agent.auth.login'), [
            'tenant_slug' => $this->slug,
            'phone' => '0501234567',
            'pin' => '1234',
            'device_name' => 'phpunit',
        ]);

        $login->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.agent.id', $this->agent->id);

        $token = (string) $login->json('data.token');
        $this->assertNotSame('', $token);

        $this->withToken($token)
            ->getJson(route('api.v1.fleet.agent.me'))
            ->assertOk()
            ->assertJsonPath('data.agent.name', 'مندوب API')
            ->assertJsonPath('data.dashboard.routes_today', 1);

        $this->withToken($token)
            ->getJson(route('api.v1.fleet.agent.routes.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data.routes');

        $stop = FleetRouteStop::query()->where('route_id', $route->id)->first();
        $this->assertNotNull($stop);

        $this->withToken($token)
            ->patchJson(route('api.v1.fleet.agent.route-stops.status', $stop->id), ['status' => 'visited'])
            ->assertOk()
            ->assertJsonPath('data.stop.status', FleetRouteStop::STATUS_VISITED);
    }

    #[Test]
    public function agent_can_create_and_confirm_collection_via_api(): void
    {
        $product = FleetProduct::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'صنف API',
            'sale_price' => 50,
            'is_active' => true,
        ]);

        $issue = FleetCustodyIssue::query()->create([
            'user_id' => (int) $this->tenant->id,
            'agent_id' => $this->agent->id,
            'issue_number' => 'FL-ISS-API-1',
            'issued_on' => now()->toDateString(),
            'status' => FleetCustodyIssue::STATUS_ISSUED,
        ]);

        $issue->lines()->create([
            'user_id' => (int) $this->tenant->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $login = $this->postJson(route('api.v1.fleet.agent.auth.login'), [
            'tenant_slug' => $this->slug,
            'phone' => '0501234567',
            'pin' => '1234',
        ]);

        $token = (string) $login->json('data.token');

        $create = $this->withToken($token)->postJson(route('api.v1.fleet.agent.collections.store'), [
            'payment_method' => 'cod',
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.collection.status', FleetCollection::STATUS_DRAFT)
            ->assertJsonPath('data.collection.subtotal', 100);

        $collectionId = (int) $create->json('data.collection.id');

        $this->withToken($token)
            ->postJson(route('api.v1.fleet.agent.collections.confirm', $collectionId))
            ->assertOk()
            ->assertJsonPath('data.collection.status', FleetCollection::STATUS_CONFIRMED);
    }

    #[Test]
    public function login_fails_with_wrong_pin(): void
    {
        $this->postJson(route('api.v1.fleet.agent.auth.login'), [
            'tenant_slug' => $this->slug,
            'phone' => '0501234567',
            'pin' => '9999',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'login_failed');
    }
}
