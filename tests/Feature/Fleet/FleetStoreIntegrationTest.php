<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetRoute;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Services\Store\StoreCheckoutService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetStoreIntegrationTest extends FleetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncEnabledModuleKeys(
            (int) $this->tenant->id,
            ['core', 'fleet', 'finance', 'pos'],
        );

        TenantStoreSetting::forTenant((int) $this->tenant->id)->update([
            'is_store_enabled' => true,
            'field_delivery_enabled' => true,
            'cod_enabled' => true,
        ]);
    }

    #[Test]
    public function field_delivery_checkout_places_order_in_fleet_pool(): void
    {
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'منتج متجر',
            'sale_price' => 50,
            'vat_percent' => 0,
            'current_quantity' => 20,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $sale = app(StoreCheckoutService::class)->placeOnlineOrder(
            (int) $this->tenant->id,
            ['name' => 'عميل متجر', 'phone' => '0501112233', 'address' => 'الرياض'],
            [['pos_product_id' => $product->id, 'quantity' => 1]],
            null,
            PosSale::PAYMENT_COD,
            null,
            PosSale::FULFILLMENT_FIELD_DELIVERY,
        );

        $this->assertSame(PosSale::FULFILLMENT_FIELD_DELIVERY, $sale->fulfillment_mode);
        $this->assertSame(PosSale::FULFILLMENT_STATUS_PENDING, $sale->fulfillment_status);
        $this->assertDatabaseHas('fleet_customers', [
            'user_id' => $this->tenant->id,
            'phone' => '0501112233',
        ]);

        $this->get(route('fleet.store-orders.index'))
            ->assertOk()
            ->assertSee($sale->invoice_number ?? (string) $sale->id)
            ->assertSee('عميل متجر');
    }

    #[Test]
    public function admin_can_assign_store_order_to_route(): void
    {
        $agent = FleetAgent::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'مندوب 1',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);

        $route = FleetRoute::query()->create([
            'user_id' => $this->tenant->id,
            'agent_id' => $agent->id,
            'route_date' => now()->toDateString(),
            'status' => FleetRoute::STATUS_PLANNED,
        ]);

        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'صنف',
            'sale_price' => 10,
            'vat_percent' => 0,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $sale = app(StoreCheckoutService::class)->placeOnlineOrder(
            (int) $this->tenant->id,
            ['name' => 'زائر', 'phone' => '0509998877', 'address' => 'جدة'],
            [['pos_product_id' => $product->id, 'quantity' => 1]],
            null,
            PosSale::PAYMENT_COD,
            null,
            PosSale::FULFILLMENT_FIELD_DELIVERY,
        );

        $this->post(route('fleet.store-orders.assign-route', $sale->id), [
            'route_id' => $route->id,
        ])->assertRedirect();

        $sale->refresh();
        $this->assertSame(PosSale::FULFILLMENT_STATUS_ASSIGNED, $sale->fulfillment_status);
        $this->assertSame($agent->id, $sale->assigned_agent_id);

        $this->assertDatabaseHas('fleet_route_stops', [
            'route_id' => $route->id,
            'pos_sale_id' => $sale->id,
        ]);
    }
}
