<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\Account;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * تأثير فاتورة المشتريات على المخزون ومتوسط التكلفة.
 */
final class PurchaseInventoryService
{
    /**
     * @return float متوسط التكلفة الجديد للصنف
     */
    public function weightedAverageUnitCost(Item $item, float $incomingQty, float $incomingUnitCost): float
    {
        if ($incomingQty <= 0) {
            return (float) ($item->cost ?? 0);
        }

        $oldQty = (float) ($item->current_stock ?? 0);
        $oldCost = (float) ($item->cost ?? 0);

        if ($oldQty <= 0) {
            return round($incomingUnitCost, 4);
        }

        return round((($oldQty * $oldCost) + ($incomingQty * $incomingUnitCost)) / ($oldQty + $incomingQty), 4);
    }

    public function postInvoice(PurchaseInvoice $invoice, int $tenantUserId): void
    {
        if ($invoice->isInventoryPosted()) {
            return;
        }

        $invoice->loadMissing(['items', 'warehouse']);
        $warehouse = $invoice->warehouse;
        if ($warehouse === null) {
            throw new \RuntimeException('المستودع غير محدد للفاتورة.');
        }

        $touchedItemIds = [];

        foreach ($invoice->items as $line) {
            $this->receiveLine($tenantUserId, $invoice, $line, $warehouse);
            $touchedItemIds[] = (int) $line->item_id;
        }

        $this->syncCurrentStock($tenantUserId, array_values(array_unique($touchedItemIds)));
    }

    /**
     * زيادة كمية الصنف في المستودع وتحديث متوسط التكلفة وسجل الحركة.
     */
    public function receiveLine(
        int $tenantUserId,
        PurchaseInvoice $invoice,
        PurchaseInvoiceItem $line,
        Warehouse $warehouse,
    ): float {
        $item = Item::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($line->item_id)
            ->firstOrFail();

        $qty = (float) $line->quantity;
        if ($qty <= 0) {
            return (float) ($item->cost ?? 0);
        }

        $unitCost = $this->netUnitCost($line);
        $oldQty = (float) ($item->current_stock ?? 0);
        $newAvg = $this->weightedAverageUnitCost($item, $qty, $unitCost);

        $pivot = ItemWarehouse::withoutGlobalScopes()->firstOrCreate(
            [
                'user_id' => $tenantUserId,
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );

        $pivot->quantity = (float) $pivot->quantity + $qty;
        $pivot->save();

        Item::withoutGlobalScopes()
            ->whereKey($item->id)
            ->update([
                'cost' => $newAvg,
                'current_stock' => round($oldQty + $qty, 4),
            ]);

        InventoryTransaction::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'purchase_invoice_in',
            'quantity' => $qty,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'فاتورة مشتريات #'.$invoice->id,
        ]);

        $line->forceFill(['weighted_unit_cost' => $newAvg])->save();

        return $newAvg;
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
                ->update(['current_stock' => round($sum, 4)]);
        }
    }

    private function netUnitCost(PurchaseInvoiceItem $line): float
    {
        $qty = max(0.0001, (float) $line->quantity);
        $gross = (float) $line->unit_price * $qty;
        $net = max(0, $gross - (float) ($line->discount ?? 0));

        return round($net / $qty, 4);
    }
}
