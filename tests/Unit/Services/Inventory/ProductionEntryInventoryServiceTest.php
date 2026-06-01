<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Inventory;

use App\Models\BomList;
use App\Models\BomListLine;
use App\Models\Item;
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

        $this->createActiveBomList($finished, $raw, quantityPerUnit: 2.0);

        $this->givenStock($raw, $warehouse, quantity: 100.0);

        $log = ProductionLog::query()->create([
            'user_id' => $this->tenant->id,
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
            'user_id' => $this->tenant->id,
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

        $this->createActiveBomList($finished, $raw, quantityPerUnit: 3.0);

        $this->givenStock($raw, $warehouse, quantity: 5.0);

        $log = ProductionLog::query()->create([
            'user_id' => $this->tenant->id,
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

        $this->createActiveBomList($finished, $raw, quantityPerUnit: 1.0);

        $this->givenStock($raw, $warehouse, quantity: 50.0);

        $log = ProductionLog::query()->create([
            'user_id' => $this->tenant->id,
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
            'user_id' => $this->tenant->id,
            'item_id' => $raw->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'logged_at' => now(),
        ]);

        $result = $this->service->applyInventoryForLog($log, (int) $warehouse->id);

        $this->assertFalse($result['applied']);
        $this->assertSame(0, StockMovement::withoutGlobalScopes()->count());
    }

    private function createActiveBomList(Item $finished, Item $raw, float $quantityPerUnit, float $scrapPercent = 0): BomList
    {
        $bom = BomList::query()->create([
            'user_id' => $this->tenant->id,
            'item_id' => $finished->id,
            'name' => 'Test BOM',
            'version' => '1.0',
            'status' => BomList::STATUS_ACTIVE,
        ]);

        BomListLine::query()->create([
            'bom_list_id' => $bom->id,
            'component_item_id' => $raw->id,
            'quantity' => $quantityPerUnit,
            'scrap_percent' => $scrapPercent,
            'sort_order' => 0,
        ]);

        return $bom;
    }
}
