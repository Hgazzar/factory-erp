<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * خصم مخزون فاتورة المبيعات وتسجيل تكلفة الوحدة (COGS) عند الترحيل.
 */
final class SalesInventoryService
{
    public function postInvoice(SalesInvoice $invoice, int $tenantUserId): void
    {
        if ($invoice->isInventoryPosted()) {
            return;
        }

        $invoice->loadMissing(['items.item', 'warehouse']);
        $warehouse = $invoice->warehouse;
        if ($warehouse === null) {
            throw new RuntimeException('المستودع غير محدد للفاتورة.');
        }

        $touchedItemIds = [];

        foreach ($invoice->items as $line) {
            $this->issueLine($tenantUserId, $invoice, $line, $warehouse);
            if ($this->isStockableLine($line)) {
                $touchedItemIds[] = (int) $line->item_id;
            }
        }

        $this->syncCurrentStock($tenantUserId, array_values(array_unique($touchedItemIds)));
    }

    /**
     * @throws RuntimeException
     */
    public function assertStockAvailable(int $tenantUserId, int $warehouseId, int $itemId, float $needQty): void
    {
        if ($needQty <= 0) {
            return;
        }

        $item = Item::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($itemId)
            ->first();

        if ($item === null || ! $this->isStockableItem($item)) {
            return;
        }

        $pivot = ItemWarehouse::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $label = $item->code ?? (string) $itemId;

        if ($pivot === null) {
            throw new RuntimeException(sprintf(
                'لا يوجد رصيد مسجّل للصنف «%s» في المخزن المحدد. سجّل توريداً أو تأكد من ربط الصنف بالمخزن.',
                $label
            ));
        }

        $available = (float) $pivot->available_quantity;
        if ($available + 0.0000001 < $needQty) {
            throw new RuntimeException(sprintf(
                'الكمية المتاحة للصنف «%s» غير كافية (متاح: %s، مطلوب: %s).',
                $label,
                $this->formatQty($available),
                $this->formatQty($needQty)
            ));
        }
    }

    public function issueLine(
        int $tenantUserId,
        SalesInvoice $invoice,
        SalesInvoiceItem $line,
        Warehouse $warehouse,
    ): float {
        $item = Item::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($line->item_id)
            ->firstOrFail();

        $unitCost = round((float) ($item->cost ?? 0), 4);
        $line->forceFill(['unit_cost' => $unitCost])->save();

        if (! $this->isStockableItem($item)) {
            return $unitCost;
        }

        $qty = (float) $line->quantity;
        if ($qty <= 0) {
            return $unitCost;
        }

        $pivot = ItemWarehouse::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('item_id', $line->item_id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $available = $pivot ? (float) $pivot->available_quantity : 0.0;
        if ($available + 0.0000001 < $qty) {
            $label = $item->code ?? '#'.$line->item_id;
            throw new RuntimeException(sprintf(
                'الكمية المتاحة في المستودع للصنف %s غير كافية (متاح: %s).',
                $label,
                $this->formatQty($available)
            ));
        }

        if ($pivot === null) {
            throw new RuntimeException('تعذر العثور على رصيد الصنف في المستودع.');
        }

        $pivot->quantity = max(0, (float) $pivot->quantity - $qty);
        $pivot->save();

        InventoryTransaction::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'item_id' => $line->item_id,
            'warehouse_id' => $warehouse->id,
            'type' => 'sales_invoice_out',
            'quantity' => $qty,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => 'فاتورة مبيعات '.($invoice->reference ?: 'SINV-'.$invoice->id),
        ]);

        return $unitCost;
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

    private function isStockableLine(SalesInvoiceItem $line): bool
    {
        $item = $line->relationLoaded('item') ? $line->item : null;

        return $item !== null && $this->isStockableItem($item);
    }

    private function isStockableItem(Item $item): bool
    {
        return $item->type !== Item::TYPE_SERVICE;
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') ?: '0';
    }
}
