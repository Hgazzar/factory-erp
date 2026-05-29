<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
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
    public function admin_deliver_via_http_updates_inventory_financials_and_redirects_with_success(): void
    {
        $seed = $this->seedDeliveryOrderReadyToDeliver();
        $delivery = $seed['delivery'];
        $expectedCogs = round(4 * 50 + 10 * 12, 4);

        $response = $this->actingAs($this->tenant)->post(
            route('sales.delivery-orders.deliver', $delivery),
        );

        $response->assertRedirect(route('sales.delivery-orders.show', $delivery->id));
        $response->assertSessionHas(
            'success',
            'تم تأكيد التسليم وتحديث رصيد الأصناف (حسب نوع الصنف).',
        );

        $delivery->refresh();
        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->journal_entry_id);

        $this->assertItemCurrentStock($seed['finished'], 16.0);
        $this->assertItemCurrentStock($seed['raw'], 90.0);
        $this->assertPivotQuantity($seed['finished'], $seed['warehouse'], 16.0);
        $this->assertPivotQuantity($seed['raw'], $seed['warehouse'], 90.0);

        $this->assertStockMovementExists(
            $seed['finished'],
            $seed['warehouse'],
            'delivery_out',
            -4.0,
            DeliveryOrder::class,
            (int) $delivery->id,
        );
        $this->assertStockMovementExists(
            $seed['raw'],
            $seed['warehouse'],
            'delivery_out',
            -10.0,
            DeliveryOrder::class,
            (int) $delivery->id,
        );

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($delivery->journal_entry_id);
        $this->assertJournalIsBalanced($entry, $expectedCogs);
        $this->assertEqualsWithDelta(
            $expectedCogs,
            $this->journalLineAmount($entry, $this->ledger['cogs'], 'debit'),
            0.0001
        );
    }

    #[Test]
    public function deliver_business_failure_rolls_back_and_shows_error_without_changing_data(): void
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
        $response->assertSessionHas('error');

        $this->assertSame(DeliveryOrder::STATUS_PENDING, $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($finished, 2.0);
        $this->assertPivotQuantity($finished, $seed['warehouse'], 2.0);
    }
}
