<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderIngredient;
use App\Models\ProductionOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

abstract class HttpWorkflowTestCase extends InventoryAccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function makeWorkerUser(): User
    {
        return User::factory()->create(['role' => 'worker']);
    }

    /**
     * @return array{order: ProductionOrder, fgLine: ProductionOrderItem, raw: Item, finished: Item, rmWarehouse: Warehouse, fgWarehouse: Warehouse}
     */
    protected function seedProductionOrderReadyToComplete(
        float $rawStock = 100,
        float $consumeQty = 30,
        float $producedQty = 5,
    ): array {
        $rmWarehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'RM-HTTP']);
        $fgWarehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'FG-HTTP']);

        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create([
            'current_stock' => $rawStock,
            'cost' => 10,
        ]);
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create([
            'current_stock' => 0,
            'cost' => 0,
        ]);

        $this->givenStock($raw, $rmWarehouse, $rawStock, unitCost: 10);

        $order = ProductionOrder::query()->create([
            'production_number' => 'PO-HTTP-'.fake()->unique()->numerify('####'),
            'status' => ProductionOrder::STATUS_IN_PROGRESS,
            'raw_materials_warehouse_id' => $rmWarehouse->id,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
        ]);

        $fgLine = ProductionOrderItem::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $finished->id,
            'planned_quantity' => $producedQty,
        ]);

        ProductionOrderIngredient::query()->create([
            'production_order_id' => $order->id,
            'item_id' => $raw->id,
            'quantity_to_consume' => $consumeQty,
        ]);

        return compact('order', 'fgLine', 'raw', 'finished', 'rmWarehouse', 'fgWarehouse');
    }

    /**
     * @return array{delivery: DeliveryOrder, finished: Item, raw: Item, warehouse: Warehouse}
     */
    protected function seedDeliveryOrderReadyToDeliver(): array
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create(['code' => 'DO-HTTP']);

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
            'delivery_number' => 'DO-HTTP-'.fake()->unique()->numerify('####'),
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

        return compact('delivery', 'finished', 'raw', 'warehouse');
    }
}
