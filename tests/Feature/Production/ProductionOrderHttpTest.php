<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Models\JournalEntry;
use App\Models\ProductionOrder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HttpWorkflowTestCase;

final class ProductionOrderHttpTest extends HttpWorkflowTestCase
{
    #[Test]
    public function guest_is_redirected_from_complete_and_cannot_change_inventory(): void
    {
        auth()->logout();
        $this->assertGuest();

        $seed = $this->seedProductionOrderReadyToComplete();
        $order = $seed['order'];
        $fgLine = $seed['fgLine'];

        $response = $this->post(route('production-orders.complete', $order), [
            'produced' => [$fgLine->id => 5],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(ProductionOrder::STATUS_IN_PROGRESS, $order->fresh()->status);
        $this->assertItemCurrentStock($seed['raw'], 100.0);
    }

    #[Test]
    public function non_admin_user_is_forbidden_from_complete(): void
    {
        $seed = $this->seedProductionOrderReadyToComplete();
        $worker = $this->makeWorkerUser();

        $response = $this->actingAs($worker)->post(route('production-orders.complete', $seed['order']), [
            'produced' => [$seed['fgLine']->id => 5],
        ]);

        $response->assertForbidden();
        $this->assertSame(ProductionOrder::STATUS_IN_PROGRESS, $seed['order']->fresh()->status);
        $this->assertNull($seed['order']->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($seed['raw'], 100.0);
        $this->assertPivotQuantity($seed['raw'], $seed['rmWarehouse'], 100.0);
    }

    #[Test]
    public function admin_complete_via_http_updates_inventory_financials_and_redirects_with_success(): void
    {
        $seed = $this->seedProductionOrderReadyToComplete();
        $order = $seed['order'];
        $fgLine = $seed['fgLine'];
        $consumeQty = 30.0;
        $producedQty = 5.0;
        $materialsValue = round($consumeQty * 10, 4);

        $response = $this->actingAs($this->tenant)->post(route('production-orders.complete', $order), [
            'produced' => [$fgLine->id => $producedQty],
        ]);

        $response->assertRedirect(route('production-orders.show', $order->id));
        $response->assertSessionHas('success', 'تم إتمام الإنتاج: خصم الخامات وإضافة المنتج التام إلى الرصيد الحالي.');

        $order->refresh();
        $this->assertSame(ProductionOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->journal_entry_id);

        $this->assertItemCurrentStock($seed['raw'], 70.0);
        $this->assertItemCurrentStock($seed['finished'], $producedQty);
        $this->assertPivotQuantity($seed['raw'], $seed['rmWarehouse'], 70.0);
        $this->assertPivotQuantity($seed['finished'], $seed['fgWarehouse'], $producedQty);

        $this->assertStockMovementExists(
            $seed['raw'],
            $seed['rmWarehouse'],
            'production_order_out',
            -$consumeQty,
            ProductionOrder::class,
            (int) $order->id,
        );
        $this->assertStockMovementExists(
            $seed['finished'],
            $seed['fgWarehouse'],
            'production_order_in',
            $producedQty,
            ProductionOrder::class,
            (int) $order->id,
        );

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($order->journal_entry_id);
        $this->assertJournalIsBalanced($entry, $materialsValue);
        $this->assertEqualsWithDelta(
            $materialsValue,
            $this->journalLineAmount($entry, $this->ledger['fg'], 'debit'),
            0.0001
        );
        $this->assertEqualsWithDelta(
            $materialsValue,
            $this->journalLineAmount($entry, $this->ledger['rm'], 'credit'),
            0.0001
        );
    }

    #[Test]
    public function complete_validation_error_does_not_change_order_or_inventory(): void
    {
        $seed = $this->seedProductionOrderReadyToComplete();

        $response = $this->actingAs($this->tenant)->from(route('production-orders.show', $seed['order']))
            ->post(route('production-orders.complete', $seed['order']), []);

        $response->assertRedirect(route('production-orders.show', $seed['order']));
        $response->assertSessionHasErrors(['produced.'.$seed['fgLine']->id]);

        $this->assertSame(ProductionOrder::STATUS_IN_PROGRESS, $seed['order']->fresh()->status);
        $this->assertNull($seed['order']->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($seed['raw'], 100.0);
        $this->assertPivotQuantity($seed['raw'], $seed['rmWarehouse'], 100.0);
    }

    #[Test]
    public function complete_business_failure_rolls_back_and_shows_error_without_changing_data(): void
    {
        $seed = $this->seedProductionOrderReadyToComplete(rawStock: 5, consumeQty: 20);
        $this->assertItemCurrentStock($seed['raw'], 5.0);
        $this->assertPivotQuantity($seed['raw'], $seed['rmWarehouse'], 5.0);

        $response = $this->actingAs($this->tenant)->from(route('production-orders.show', $seed['order']))
            ->post(route('production-orders.complete', $seed['order']), [
                'produced' => [$seed['fgLine']->id => 2],
            ]);

        $response->assertRedirect(route('production-orders.show', $seed['order']));
        $response->assertSessionHas('error');

        $this->assertSame(ProductionOrder::STATUS_IN_PROGRESS, $seed['order']->fresh()->status);
        $this->assertNull($seed['order']->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($seed['raw'], 5.0);
        $this->assertPivotQuantity($seed['raw'], $seed['rmWarehouse'], 5.0);
    }

}
