<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Inventory;

use App\Models\Item;
use App\Models\ItemBomComponent;
use App\Models\ProductionLog;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\ProductionEntryInventoryService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\InventoryTestCase;

final class ProductionEntryInventoryServiceTest extends InventoryTestCase
{
    private ProductionEntryInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProductionEntryInventoryService::class);
    }

    #[Test]
    public function it_consumes_bom_components_and_receives_finished_goods_with_weighted_batch_cost(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 10.0]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);

        ItemBomComponent::query()->create([
            'finished_item_id' => $finished->id,
            'component_item_id' => $raw->id,
            'quantity_per_unit' => 2,
        ]);

        $this->givenStock($raw, $warehouse, quantity: 100.0);

        $log = ProductionLog::query()->create([
            'item_id' => $finished->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'logged_at' => now(),
        ]);

        $result = $this->service->applyInventoryForLog($log, (int) $warehouse->id);

        $this->assertTrue($result['applied']);
        $this->assertEqualsWithDelta(100.0, $result['total_material_cost'], 0.0001);
        $this->assertEqualsWithDelta(20.0, $result['unit_batch_cost'], 0.0001);

        $this->assertPivotQuantity($raw, $warehouse, 90.0);
        $this->assertPivotQuantity($finished, $warehouse, 5.0);
        $this->assertItemCost($finished, 20.0);

        $this->assertStockMovementExists($raw, $warehouse, 'production_entry_out', -10.0);
        $this->assertStockMovementExists($finished, $warehouse, 'production_entry_in', 5.0);

        $this->assertNotNull($log->fresh()->inventory_synced_at);
    }

    #[Test]
    public function it_receives_finished_goods_without_bom_using_existing_item_cost(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 35.0]);

        $log = ProductionLog::query()->create([
            'item_id' => $finished->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
            'logged_at' => now(),
        ]);

        $result = $this->service->applyInventoryForLog($log, (int) $warehouse->id);

        $this->assertTrue($result['applied']);
        $this->assertEqualsWithDelta(140.0, $result['total_material_cost'], 0.0001);
        $this->assertEqualsWithDelta(35.0, $result['unit_batch_cost'], 0.0001);
        $this->assertPivotQuantity($finished, $warehouse, 4.0);
        $this->assertStockMovementExists($finished, $warehouse, 'production_entry_in', 4.0);
    }

    #[Test]
    public function it_rejects_when_bom_raw_material_stock_is_insufficient(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['code' => 'RM-PE', 'cost' => 5.0]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create();

        ItemBomComponent::query()->create([
            'finished_item_id' => $finished->id,
            'component_item_id' => $raw->id,
            'quantity_per_unit' => 3,
        ]);

        $this->givenStock($raw, $warehouse, quantity: 5.0);

        $log = ProductionLog::query()->create([
            'item_id' => $finished->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'logged_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('الكمية المتاحة للصنف «RM-PE» غير كافية');

        $this->service->applyInventoryForLog($log, (int) $warehouse->id);
    }

    #[Test]
    public function it_rejects_when_bom_material_cost_is_zero(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 0]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create();

        ItemBomComponent::query()->create([
            'finished_item_id' => $finished->id,
            'component_item_id' => $raw->id,
            'quantity_per_unit' => 1,
        ]);

        $this->givenStock($raw, $warehouse, quantity: 50.0);

        $log = ProductionLog::query()->create([
            'item_id' => $finished->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'logged_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('تعذر احتساب تكلفة المواد');

        $this->service->applyInventoryForLog($log, (int) $warehouse->id);
    }

    #[Test]
    public function it_does_not_apply_for_non_finished_good_items(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create();

        $log = ProductionLog::query()->create([
            'item_id' => $raw->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'logged_at' => now(),
        ]);

        $result = $this->service->applyInventoryForLog($log, (int) $warehouse->id);

        $this->assertFalse($result['applied']);
        $this->assertSame(0, StockMovement::withoutGlobalScopes()->count());
    }
}
