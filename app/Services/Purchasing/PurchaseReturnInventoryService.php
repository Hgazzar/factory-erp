<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * خصم مخزون مرتجع المشتريات من المستودع.
 */
final class PurchaseReturnInventoryService
{
    public function postReturn(PurchaseReturn $purchaseReturn, int $tenantUserId): void
    {
        $purchaseReturn->loadMissing(['items.item', 'warehouse']);
        $warehouse = $purchaseReturn->warehouse;
        if ($warehouse === null) {
            throw new RuntimeException('المستودع غير محدد لمرتجع المشتريات.');
        }

        $touchedItemIds = [];

        foreach ($purchaseReturn->items as $line) {
            $this->returnLine($tenantUserId, $purchaseReturn, $line, $warehouse);
            $touchedItemIds[] = (int) $line->item_id;
        }

        $this->syncCurrentStock($tenantUserId, array_values(array_unique($touchedItemIds)));
    }

    public function returnLine(
        int $tenantUserId,
        PurchaseReturn $purchaseReturn,
        PurchaseReturnItem $line,
        Warehouse $warehouse,
    ): void {
        $qty = (float) $line->quantity;
        if ($qty <= 0) {
            return;
        }

        $pivot = ItemWarehouse::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('item_id', $line->item_id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $available = $pivot ? (float) $pivot->quantity : 0.0;
        if ($available + 0.0001 < $qty) {
            $item = Item::withoutGlobalScopes()->find($line->item_id);
            $label = $item?->code ?? '#'.$line->item_id;
            throw new RuntimeException("الكمية المتاحة في المستودع للصنف {$label} غير كافية للمرتجع (متاح: {$available}).");
        }

        if (! $pivot) {
            throw new RuntimeException('تعذر العثور على رصيد الصنف في المستودع.');
        }

        $pivot->quantity = max(0, (float) $pivot->quantity - $qty);
        $pivot->save();

        $item = Item::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($line->item_id)
            ->first();

        if ($item) {
            $oldStock = (float) ($item->current_stock ?? 0);
            Item::withoutGlobalScopes()
                ->whereKey($item->id)
                ->update(['current_stock' => round(max(0, $oldStock - $qty), 4)]);
        }

        InventoryTransaction::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'item_id' => $line->item_id,
            'warehouse_id' => $warehouse->id,
            'type' => 'purchase_return_out',
            'quantity' => $qty,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'notes' => 'مرتجع مشتريات '.($purchaseReturn->code ?: '#'.$purchaseReturn->id),
        ]);
    }

    /**
     * @param  list<int>  $itemIds
     */
    private function syncCurrentStock(int $tenantUserId, array $itemIds): void
    {
        foreach ($itemIds as $itemId) {
            $sum = (float) ItemWarehouse::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('item_id', $itemId)
                ->sum(DB::raw('quantity - reserved_quantity'));

            Item::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($itemId)
                ->update(['current_stock' => round(max(0, $sum), 4)]);
        }
    }
}
