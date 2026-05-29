<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderIngredient;
use App\Models\ProductionOrderItem;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\InventoryAccountingTestCase;

final class ProductionOrderFeatureTest extends InventoryAccountingTestCase
{
    #[Test]
    public function mark_completed_success_updates_status_stock_warehouses_movements_and_journal(): void
    {
        $rmWarehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'RM-WH']);
        $fgWarehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'FG-WH']);

        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => 100,
            'cost' => 10,
        ]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 0,
            'cost' => 0,
        ]);

        $this->givenStock($raw, $rmWarehouse, 100, unitCost: 10);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-FEAT-001',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
            'raw_materials_warehouse_id' => $rmWarehouse->id,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => 5,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => 30,
        ]);

        $consumeQty = 30.0;
        $producedQty = 5.0;
        $materialsValue = round($consumeQty * 10, 4);

        $order->markCompleted([$fgLine->id => $producedQty]);

        $order->refresh();
        $raw->refresh();
        $finished->refresh();

        $this->assertSame(ProductionOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->end_date);
        $this->assertNotNull($order->journal_entry_id);

        $this->assertItemCurrentStock($raw, 70.0);
        $this->assertItemCurrentStock($finished, $producedQty);
        $this->assertPivotQuantity($raw, $rmWarehouse, 70.0);
        $this->assertPivotQuantity($finished, $fgWarehouse, $producedQty);

        $this->assertStockMovementExists(
            $raw,
            $rmWarehouse,
            'production_order_out',
            -$consumeQty,
            ProductionOrder::class,
            (int) $order->id,
        );
        $this->assertStockMovementExists(
            $finished,
            $fgWarehouse,
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
    public function mark_completed_fails_when_raw_material_warehouse_stock_is_insufficient(): void
    {
        $rmWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $fgWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => 5,
            'cost' => 8,
        ]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['current_stock' => 0]);

        $this->givenStock($raw, $rmWarehouse, 5, unitCost: 8);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-FEAT-002',
            'status' => ProductionOrder::STATUS_PENDING,
            'raw_materials_warehouse_id' => $rmWarehouse->id,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => 2,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => 20,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('غير كافية');

        $order->markCompleted([$fgLine->id => 2]);

        $this->assertSame(ProductionOrder::STATUS_PENDING, $order->fresh()->status);
        $this->assertNull($order->fresh()->journal_entry_id);
        $this->assertItemCurrentStock($raw, 5.0);
        $this->assertPivotQuantity($raw, $rmWarehouse, 5.0);
    }

    #[Test]
    public function mark_completed_fails_when_warehouses_are_not_configured(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['current_stock' => 50, 'cost' => 5]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['current_stock' => 0]);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-FEAT-003',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => 1,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => 10,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('يجب تحديد مستودع');

        $order->markCompleted([$fgLine->id => 1]);
    }

    #[Test]
    public function mark_completed_fails_when_produced_quantity_is_missing(): void
    {
        $rmWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $fgWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => 50,
            'cost' => 5,
        ]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['current_stock' => 0]);

        $this->givenStock($raw, $rmWarehouse, 50, unitCost: 5);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-FEAT-004',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
            'raw_materials_warehouse_id' => $rmWarehouse->id,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => 1,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => 10,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('أدخل كمية منتَجة أكبر من صفر');

        $order->markCompleted([]);

        $this->assertSame(ProductionOrder::STATUS_IN_PROGRESS, $order->fresh()->status);
        $this->assertNull($order->fresh()->journal_entry_id);
        $this->assertPivotQuantity($raw, $rmWarehouse, 50.0);
    }

    #[Test]
    public function mark_completed_fails_when_order_is_already_completed(): void
    {
        $rmWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $fgWarehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => 40,
            'cost' => 4,
        ]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['current_stock' => 0]);

        $this->givenStock($raw, $rmWarehouse, 40, unitCost: 4);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-FEAT-005',
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
            'raw_materials_warehouse_id' => $rmWarehouse->id,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => 1,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => 5,
        ]);

        $order->markCompleted([$fgLine->id => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('يمكن إتمام الإنتاج فقط');

        $order->markCompleted([$fgLine->id => 1]);
    }
}
