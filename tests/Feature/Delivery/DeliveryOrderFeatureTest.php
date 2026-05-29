<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\InventoryAccountingTestCase;

final class DeliveryOrderFeatureTest extends InventoryAccountingTestCase
{
    #[Test]
    public function mark_as_delivered_success_updates_status_stock_warehouse_movements_and_cogs_journal(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'DO-WH']);

        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 20,
            'cost' => 50,
        ]);
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => 100,
            'cost' => 12,
        ]);

        $this->givenStock($finished, $warehouse, 20, unitCost: 50);
        $this->givenStock($raw, $warehouse, 100, unitCost: 12);

        $delivery = DeliveryOrder::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-FEAT-001',
            'status' => DeliveryOrder::STATUS_PENDING,
            'warehouse_id' => $warehouse->id,
        ]);

        DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $finished->id,
            'quantity' => 4,
        ]);
        DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $raw->id,
            'quantity' => 10,
        ]);

        $fgQty = 4.0;
        $rmQty = 10.0;
        $expectedCogs = round($fgQty * 50 + $rmQty * 12, 4);
        $expectedFgCredit = round($fgQty * 50, 4);
        $expectedRmCredit = round($rmQty * 12, 4);

        $delivery->markAsDelivered();

        $delivery->refresh();
        $finished->refresh();
        $raw->refresh();

        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->delivery_date);
        $this->assertNotNull($delivery->journal_entry_id);

        $this->assertItemCurrentStock($finished, 16.0);
        $this->assertItemCurrentStock($raw, 90.0);
        $this->assertPivotQuantity($finished, $warehouse, 16.0);
        $this->assertPivotQuantity($raw, $warehouse, 90.0);

        $this->assertStockMovementExists(
            $finished,
            $warehouse,
            'delivery_out',
            -$fgQty,
            DeliveryOrder::class,
            (int) $delivery->id,
        );
        $this->assertStockMovementExists(
            $raw,
            $warehouse,
            'delivery_out',
            -$rmQty,
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
        $this->assertEqualsWithDelta(
            $expectedFgCredit,
            $this->journalLineAmount($entry, $this->ledger['fg'], 'credit'),
            0.0001
        );
        $this->assertEqualsWithDelta(
            $expectedRmCredit,
            $this->journalLineAmount($entry, $this->ledger['rm'], 'credit'),
            0.0001
        );
    }

    #[Test]
    public function mark_as_delivered_fails_when_warehouse_stock_is_insufficient(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 2,
            'cost' => 40,
        ]);

        $this->givenStock($finished, $warehouse, 2, unitCost: 40);

        $delivery = DeliveryOrder::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-FEAT-002',
            'status' => DeliveryOrder::STATUS_PENDING,
            'warehouse_id' => $warehouse->id,
        ]);

        DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $finished->id,
            'quantity' => 5,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('غير كاف');

        $delivery->markAsDelivered();

        $this->assertSame(DeliveryOrder::STATUS_PENDING, $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($finished, 2.0);
        $this->assertPivotQuantity($finished, $warehouse, 2.0);
    }

    #[Test]
    public function mark_as_delivered_fails_when_warehouse_is_not_configured(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 10,
            'cost' => 25,
        ]);

        $delivery = DeliveryOrder::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-FEAT-003',
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $finished->id,
            'quantity' => 2,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('يجب تحديد مستودع التوريد');

        $delivery->markAsDelivered();
    }

    #[Test]
    public function mark_as_delivered_fails_when_order_is_not_pending(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 10,
            'cost' => 25,
        ]);

        $this->givenStock($finished, $warehouse, 10, unitCost: 25);

        $delivery = DeliveryOrder::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'delivery_number' => 'DO-FEAT-004',
            'status' => DeliveryOrder::STATUS_PENDING,
            'warehouse_id' => $warehouse->id,
        ]);

        DeliveryOrderItem::query()->create([
            'delivery_order_id' => $delivery->id,
            'item_id' => $finished->id,
            'quantity' => 2,
        ]);

        $delivery->markAsDelivered();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('يمكن تأكيد التسليم فقط');

        $delivery->markAsDelivered();
    }
}
