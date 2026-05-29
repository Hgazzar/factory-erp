<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Inventory;

use App\Models\Item;
use App\Models\ManufacturingRun;
use App\Models\ServicePart;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\InventoryTestCase;

final class InventoryServiceTest extends InventoryTestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);
    }

    #[Test]
    public function receive_manufacturing_output_increases_only_target_warehouse(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);
        $warehouseA = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'WH-A']);
        $warehouseB = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'WH-B']);

        $run = ManufacturingRun::factory()
            ->forTenant($this->tenant)
            ->forWarehouse($warehouseA)
            ->forFinishedItem($finished)
            ->create();

        $this->service->receiveManufacturingOutput(
            $finished,
            (int) $warehouseA->id,
            50.0,
            $run,
            unitBatchCost: 10.0,
        );

        $this->assertPivotQuantity($finished, $warehouseA, 50.0);
        $this->assertPivotQuantity($finished, $warehouseB, 0.0);
        $this->assertItemCurrentStock($finished, 50.0);
        $this->assertStockMovementExists(
            $finished,
            $warehouseA,
            'manufacturing_in',
            50.0,
            ManufacturingRun::class,
            (int) $run->id,
        );
    }

    #[Test]
    public function receive_manufacturing_output_recalculates_weighted_average_cost_across_batches(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $run = ManufacturingRun::factory()
            ->forTenant($this->tenant)
            ->forWarehouse($warehouse)
            ->forFinishedItem($finished)
            ->create();

        $this->service->receiveManufacturingOutput($finished, (int) $warehouse->id, 10.0, $run, 100.0);
        $this->assertItemCost($finished, 100.0);

        $this->service->receiveManufacturingOutput($finished, (int) $warehouse->id, 10.0, $run, 200.0);

        // (10×100 + 10×200) / 20 = 150
        $this->assertItemCost($finished, 150.0);
        $this->assertPivotQuantity($finished, $warehouse, 20.0);
        $this->assertItemCurrentStock($finished, 20.0);
    }

    #[Test]
    public function receive_manufacturing_output_applies_weighted_average_when_stock_already_exists(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create();
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->givenStock($finished, $warehouse, quantity: 20.0, unitCost: 50.0);

        $run = ManufacturingRun::factory()
            ->forTenant($this->tenant)
            ->forWarehouse($warehouse)
            ->forFinishedItem($finished)
            ->create();

        $this->service->receiveManufacturingOutput($finished, (int) $warehouse->id, 10.0, $run, 80.0);

        // (20×50 + 10×80) / 30 = 60
        $this->assertItemCost($finished, 60.0);
        $this->assertPivotQuantity($finished, $warehouse, 30.0);
    }

    #[Test]
    public function consume_for_manufacturing_decreases_warehouse_and_records_negative_movement(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create();
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->givenStock($raw, $warehouse, quantity: 100.0);

        $run = ManufacturingRun::factory()
            ->forTenant($this->tenant)
            ->forWarehouse($warehouse)
            ->create();

        $this->service->consumeForManufacturing($raw, (int) $warehouse->id, 30.0, $run);

        $this->assertPivotQuantity($raw, $warehouse, 70.0);
        $this->assertItemCurrentStock($raw, 70.0);
        $this->assertStockMovementExists(
            $raw,
            $warehouse,
            'manufacturing_out',
            -30.0,
            ManufacturingRun::class,
            (int) $run->id,
        );
    }

    #[Test]
    public function consume_for_manufacturing_only_affects_specified_warehouse(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create();
        $warehouseA = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'WH-RAW-A']);
        $warehouseB = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'WH-RAW-B']);

        $this->givenStock($raw, $warehouseA, quantity: 100.0);
        $this->givenStock($raw, $warehouseB, quantity: 40.0);

        $run = ManufacturingRun::factory()->forTenant($this->tenant)->create();

        $this->service->consumeForManufacturing($raw, (int) $warehouseA->id, 25.0, $run);

        $this->assertPivotQuantity($raw, $warehouseA, 75.0);
        $this->assertPivotQuantity($raw, $warehouseB, 40.0);
        $this->assertItemCurrentStock($raw, 115.0);
    }

    #[Test]
    public function consume_for_manufacturing_rejects_when_available_quantity_is_insufficient(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['code' => 'RM-LOW']);
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->givenStock($raw, $warehouse, quantity: 5.0);

        $run = ManufacturingRun::factory()->forTenant($this->tenant)->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('الكمية المتاحة للصنف «RM-LOW» غير كافية');

        $this->service->consumeForManufacturing($raw, (int) $warehouse->id, 10.0, $run);
    }

    #[Test]
    public function consume_for_manufacturing_respects_reserved_quantity_in_availability(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['code' => 'RM-RES']);
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->givenStock($raw, $warehouse, quantity: 20.0, reserved: 18.0);

        $run = ManufacturingRun::factory()->forTenant($this->tenant)->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('الكمية المتاحة للصنف «RM-RES» غير كافية');

        $this->service->consumeForManufacturing($raw, (int) $warehouse->id, 5.0, $run);
    }

    #[Test]
    public function issue_for_service_decreases_stock_and_creates_service_out_movement(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create();
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $this->givenStock($raw, $warehouse, quantity: 60.0);

        $reference = ServicePart::query()->create([
            'item_id' => $raw->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15.0,
            'unit_cost' => 5.0,
        ]);

        $this->service->issueForService($raw, (int) $warehouse->id, 15.0, $reference);

        $this->assertPivotQuantity($raw, $warehouse, 45.0);
        $this->assertItemCurrentStock($raw, 45.0);
        $this->assertStockMovementExists(
            $raw,
            $warehouse,
            'service_out',
            -15.0,
            ServicePart::class,
            (int) $reference->id,
        );
    }

    #[Test]
    public function receive_then_consume_leaves_consistent_balances_and_movement_history(): void
    {
        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();

        $run = ManufacturingRun::factory()
            ->forTenant($this->tenant)
            ->forWarehouse($warehouse)
            ->forFinishedItem($item)
            ->create();

        $this->service->receiveManufacturingOutput($item, (int) $warehouse->id, 100.0, $run, 12.0);
        $this->service->consumeForManufacturing($item, (int) $warehouse->id, 35.0, $run);

        $this->assertPivotQuantity($item, $warehouse, 65.0);
        $this->assertItemCurrentStock($item, 65.0);

        $movementCount = StockMovement::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->count();

        $this->assertSame(2, $movementCount);
    }
}
