<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * خصم المخزون لقطع الغيار والصيانة: item_warehouse + current_stock + حركة مخزن.
 */
class InventoryService
{
    public function issueForService(Item $item, int $warehouseId, float $quantity, Model $reference): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($item, $warehouseId, $quantity, $reference) {
            /** @var Item $lockedItem */
            $lockedItem = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($lockedItem->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                throw new RuntimeException('لا يمكن صرف صنف من نوع «خدمة» أو غير قابل للتخزين من المخزون.');
            }

            $pivot = ItemWarehouse::query()
                ->where('item_id', $lockedItem->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $pivot) {
                throw new RuntimeException('لا يوجد رصيد لهذا الصنف في المستودع المحدد.');
            }

            $available = (float) $pivot->available_quantity;
            if ($available + 0.0000001 < $quantity) {
                throw new RuntimeException(
                    sprintf(
                        'الكمية المتاحة للصنف «%s» غير كافية (متاح: %s، مطلوب: %s).',
                        $lockedItem->code,
                        rtrim(rtrim(number_format($available, 4, '.', ''), '0'), '.') ?: '0',
                        rtrim(rtrim(number_format($quantity, 4, '.', ''), '0'), '.') ?: '0'
                    )
                );
            }

            $pivot->decrement('quantity', $quantity);

            $current = (float) ($lockedItem->current_stock ?? 0);
            if ($current + 0.0000001 < $quantity) {
                throw new RuntimeException(
                    sprintf(
                        'رصيد الصنف «%s» العام غير متوافق مع المستودع؛ راجع أرصدة المخزون.',
                        $lockedItem->code
                    )
                );
            }

            $lockedItem->current_stock = $current - $quantity;
            $lockedItem->save();

            StockMovement::query()->create([
                'warehouse_id' => $warehouseId,
                'item_id' => $lockedItem->id,
                'quantity' => -$quantity,
                'movement_type' => 'service_out',
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
            ]);
        });
    }
}
