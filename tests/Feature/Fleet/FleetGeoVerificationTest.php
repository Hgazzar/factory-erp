<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\AuditTrail;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use App\Models\Fleet\FleetVisit;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetGeoVerificationTest extends FleetTestCase
{
    private string $slug = 'test-fleet';

    private FleetAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب GPS',
            'phone' => '0501234567',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);
        $this->agent->setApiPin('1234');
    }

    private function token(): string
    {
        $login = $this->postJson(route('api.v1.fleet.agent.auth.login'), [
            'tenant_slug' => $this->slug,
            'phone' => '0501234567',
            'pin' => '1234',
        ]);

        return (string) $login->json('data.token');
    }

    private function stopFor(FleetCustomer $customer): FleetRouteStop
    {
        $route = FleetRoute::query()->create([
            'user_id' => (int) $this->tenant->id,
            'agent_id' => $this->agent->id,
            'route_date' => now()->toDateString(),
            'status' => FleetRoute::STATUS_PLANNED,
        ]);

        return FleetRouteStop::query()->create([
            'user_id' => (int) $this->tenant->id,
            'route_id' => $route->id,
            'customer_id' => $customer->id,
            'sort_order' => 1,
            'status' => FleetRouteStop::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function visit_inside_radius_of_approved_customer_is_marked_inside(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل معتمد',
            'status' => FleetCustomer::STATUS_ACTIVE,
            'latitude' => 24.7136000,
            'longitude' => 46.6753000,
            'location_status' => FleetCustomer::LOCATION_APPROVED,
        ]);
        $stop = $this->stopFor($customer);

        $this->withToken($this->token())
            ->patchJson(route('api.v1.fleet.agent.route-stops.status', $stop->id), [
                'status' => 'visited',
                'outcome' => 'no_sale',
                'visit_reason' => 'المحل مغلق',
                'lat' => 24.7136500,
                'lng' => 46.6753500,
            ])->assertOk();

        $visit = FleetVisit::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($visit);
        $this->assertSame(FleetVisit::GEOFENCE_INSIDE, $visit->geofence_status);
        $this->assertSame(FleetVisit::OUTCOME_NO_SALE, $visit->outcome);
        $this->assertNotNull($visit->distance_meters);
    }

    #[Test]
    public function visit_outside_radius_is_flagged_outside(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل بعيد',
            'status' => FleetCustomer::STATUS_ACTIVE,
            'latitude' => 24.7136000,
            'longitude' => 46.6753000,
            'location_status' => FleetCustomer::LOCATION_APPROVED,
        ]);
        $stop = $this->stopFor($customer);

        $this->withToken($this->token())
            ->patchJson(route('api.v1.fleet.agent.route-stops.status', $stop->id), [
                'status' => 'visited',
                'outcome' => 'no_sale',
                'visit_reason' => 'المحل مغلق',
                'lat' => 24.8000000,
                'lng' => 46.7500000,
                'is_mocked' => true,
            ])->assertOk();

        $visit = FleetVisit::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($visit);
        $this->assertSame(FleetVisit::GEOFENCE_OUTSIDE, $visit->geofence_status);
        $this->assertTrue($visit->is_mocked);
        $this->assertGreaterThan(1000, (int) $visit->distance_meters);
    }

    #[Test]
    public function cold_start_captures_pending_location_for_customer_without_coordinates(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل جديد',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);
        $stop = $this->stopFor($customer);

        $this->withToken($this->token())
            ->patchJson(route('api.v1.fleet.agent.route-stops.status', $stop->id), [
                'status' => 'visited',
                'outcome' => 'no_sale',
                'visit_reason' => 'لا يوجد طلب',
                'lat' => 24.7136000,
                'lng' => 46.6753000,
            ])->assertOk();

        $customer->refresh();
        $this->assertSame(FleetCustomer::LOCATION_PENDING, $customer->location_status);
        $this->assertSame(FleetCustomer::LOCATION_SOURCE_AGENT, $customer->location_source);
        $this->assertEqualsWithDelta(24.7136000, (float) $customer->latitude, 0.0001);

        $visit = FleetVisit::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($visit);
        $this->assertSame(FleetVisit::GEOFENCE_UNVERIFIED, $visit->geofence_status);
    }

    #[Test]
    public function no_sale_visit_requires_reason(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل بدون سبب',
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);
        $stop = $this->stopFor($customer);

        $this->withToken($this->token())
            ->patchJson(route('api.v1.fleet.agent.route-stops.status', $stop->id), [
                'status' => 'visited',
                'outcome' => 'no_sale',
                'lat' => 24.7136000,
                'lng' => 46.6753000,
            ])->assertStatus(422)
            ->assertJsonValidationErrors('visit_reason');

        $this->assertSame(0, FleetVisit::query()->where('customer_id', $customer->id)->count());
    }

    #[Test]
    public function manager_can_approve_pending_location_and_it_is_audited(): void
    {
        $customer = FleetCustomer::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'عميل معلّق',
            'status' => FleetCustomer::STATUS_ACTIVE,
            'latitude' => 24.7136000,
            'longitude' => 46.6753000,
            'location_status' => FleetCustomer::LOCATION_PENDING,
            'location_source' => FleetCustomer::LOCATION_SOURCE_AGENT,
        ]);

        $this->post(route('fleet.customers.approve-location', $customer->id))
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame(FleetCustomer::LOCATION_APPROVED, $customer->location_status);
        $this->assertSame(FleetCustomer::LOCATION_SOURCE_MANAGER, $customer->location_source);

        $this->assertSame(1, AuditTrail::query()
            ->where('table_name', 'fleet_customers')
            ->where('record_id', $customer->id)
            ->where('action', 'approve_location')
            ->count());
    }
}
