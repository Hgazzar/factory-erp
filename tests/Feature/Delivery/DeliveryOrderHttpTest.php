<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\StockMovement;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HttpWorkflowTestCase;

final class DeliveryOrderHttpTest extends HttpWorkflowTestCase
{
    #[Test]
    public function guest_is_redirected_from_deliver_and_cannot_change_inventory(): void
    {
        auth()->logout();
        $this->assertGuest();

        $seed = $this->seedDeliveryOrderReadyToDeliver();

        $response = $this->post(route('sales.delivery-orders.deliver', $seed['delivery']));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(DeliveryOrder::STATUS_PENDING, $seed['delivery']->fresh()->status);
        $this->assertItemCurrentStock($seed['finished'], 20.0);
    }

    #[Test]
    public function non_admin_user_is_forbidden_from_deliver(): void
    {
        $seed = $this->seedDeliveryOrderReadyToDeliver();
        $worker = $this->makeWorkerUser();

        $response = $this->actingAs($worker)->post(
            route('sales.delivery-orders.deliver', $seed['delivery']),
        );

        $response->assertForbidden();
        $this->assertSame(DeliveryOrder::STATUS_PENDING, $seed['delivery']->fresh()->status);
        $this->assertNull($seed['delivery']->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($seed['finished'], 20.0);
        $this->assertPivotQuantity($seed['finished'], $seed['warehouse'], 20.0);
    }

    #[Test]
    public function another_tenants_admin_is_forbidden_from_deliver(): void
    {
        $seed = $this->seedDeliveryOrderReadyToDeliver();
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($otherAdmin)->post(
            route('sales.delivery-orders.deliver', $seed['delivery']),
        );

        $response->assertForbidden();
        $this->assertSame(DeliveryOrder::STATUS_PENDING, $seed['delivery']->fresh()->status);
        $this->assertItemCurrentStock($seed['finished'], 20.0);
    }

    #[Test]
    public function admin_deliver_via_http_updates_status_without_inventory_or_journal(): void
    {
        $seed = $this->seedDeliveryOrderReadyToDeliver();
        $delivery = $seed['delivery'];

        $response = $this->actingAs($this->tenant)->post(
            route('sales.delivery-orders.deliver', $delivery),
        );

        $response->assertRedirect(route('sales.delivery-orders.show', $delivery->id));
        $response->assertSessionHas('success');

        $delivery->refresh();
        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $delivery->status);
        $this->assertNull($delivery->journal_entry_id);

        $this->assertItemCurrentStock($seed['finished'], 20.0);
        $this->assertItemCurrentStock($seed['raw'], 100.0);
        $this->assertPivotQuantity($seed['finished'], $seed['warehouse'], 20.0);
        $this->assertPivotQuantity($seed['raw'], $seed['warehouse'], 100.0);

        $this->assertSame(
            0,
            StockMovement::withoutGlobalScopes()
                ->where('reference_type', DeliveryOrder::class)
                ->where('reference_id', $delivery->id)
                ->count()
        );
        $this->assertSame(0, JournalEntry::query()->count());
    }

    #[Test]
    public function deliver_succeeds_even_when_warehouse_stock_is_lower_than_delivery_qty(): void
    {
        $seed = $this->seedDeliveryOrderReadyToDeliver();

        $finished = $seed['finished'];
        $delivery = $seed['delivery'];

        Item::withoutGlobalScopes()->whereKey($finished->id)->update(['current_stock' => 2]);
        ItemWarehouse::withoutGlobalScopes()
            ->where('item_id', $finished->id)
            ->where('warehouse_id', $seed['warehouse']->id)
            ->update(['quantity' => 2]);

        $response = $this->actingAs($this->tenant)->from(route('sales.delivery-orders.show', $delivery))
            ->post(route('sales.delivery-orders.deliver', $delivery));

        $response->assertRedirect(route('sales.delivery-orders.show', $delivery));
        $response->assertSessionHas('success');

        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $delivery->fresh()->status);
        $this->assertItemCurrentStock($finished, 2.0);
    }
}
