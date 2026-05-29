<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

abstract class InventoryTestCase extends AccountingTestCase
{
    protected function givenStock(
        Item $item,
        Warehouse $warehouse,
        float $quantity,
        float $reserved = 0,
        ?float $unitCost = null,
    ): ItemWarehouse {
        $pivot = ItemWarehouse::withoutGlobalScopes()->updateOrCreate(
            [
                'user_id' => $this->tenant->id,
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => $quantity,
                'reserved_quantity' => $reserved,
            ]
        );

        if ($unitCost !== null) {
            Item::withoutGlobalScopes()
                ->whereKey($item->id)
                ->update(['cost' => $unitCost]);
        }

        $this->recalculateItemCurrentStock($item);

        return $pivot->fresh();
    }

    protected function recalculateItemCurrentStock(Item $item): void
    {
        $sum = (float) ItemWarehouse::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('user_id', $this->tenant->id)
            ->sum(DB::raw('quantity - reserved_quantity'));

        Item::withoutGlobalScopes()
            ->whereKey($item->id)
            ->update(['current_stock' => round($sum, 4)]);
    }

    protected function pivotQuantity(Item $item, Warehouse $warehouse): float
    {
        return (float) ItemWarehouse::withoutGlobalScopes()
            ->where('user_id', $this->tenant->id)
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity');
    }

    protected function itemCurrentStock(Item $item): float
    {
        return (float) Item::withoutGlobalScopes()
            ->whereKey($item->id)
            ->value('current_stock');
    }

    protected function itemCost(Item $item): float
    {
        return (float) Item::withoutGlobalScopes()
            ->whereKey($item->id)
            ->value('cost');
    }

    protected function assertPivotQuantity(Item $item, Warehouse $warehouse, float $expected): void
    {
        $this->assertEqualsWithDelta(
            $expected,
            $this->pivotQuantity($item, $warehouse),
            0.0001,
            sprintf(
                'Expected warehouse %s quantity %.4f for item %s',
                $warehouse->code,
                $expected,
                $item->code
            )
        );
    }

    protected function assertItemCurrentStock(Item $item, float $expected): void
    {
        $this->assertEqualsWithDelta(
            $expected,
            $this->itemCurrentStock($item),
            0.0001,
            sprintf('Expected item %s current_stock %.4f', $item->code, $expected)
        );
    }

    protected function assertItemCost(Item $item, float $expected): void
    {
        $this->assertEqualsWithDelta(
            $expected,
            $this->itemCost($item),
            0.0001,
            sprintf('Expected item %s weighted cost %.4f', $item->code, $expected)
        );
    }

    protected function assertStockMovementExists(
        Item $item,
        Warehouse $warehouse,
        string $movementType,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        $query = StockMovement::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('movement_type', $movementType);

        if ($referenceType !== null) {
            $query->where('reference_type', $referenceType);
        }
        if ($referenceId !== null) {
            $query->where('reference_id', $referenceId);
        }

        $movement = $query->latest('id')->first();

        $this->assertNotNull(
            $movement,
            sprintf('Expected stock_movement [%s] for item %s', $movementType, $item->code)
        );

        $this->assertEqualsWithDelta(
            $quantity,
            (float) $movement->quantity,
            0.0001,
            sprintf('Expected movement quantity %.4f', $quantity)
        );
    }
}
