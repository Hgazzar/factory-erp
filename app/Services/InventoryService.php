<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\ManufacturingRun;
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

    /**
     * صرف مخزون لعملية تصنيع (مواد خام أو تام فرعي) — دون منطق مستودعات إضافي؛ يُسجَّل كمرجع تشغيل التصنيع.
     */
    public function consumeForManufacturing(Item $item, int $warehouseId, float $quantity, ManufacturingRun $reference): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($item, $warehouseId, $quantity, $reference) {
            /** @var Item $lockedItem */
            $lockedItem = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($lockedItem->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                throw new RuntimeException('لا يمكن صرف صنف من نوع «خدمة» أو غير قابل للتخزين في التصنيع.');
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
                'movement_type' => 'manufacturing_out',
                'reference_type' => ManufacturingRun::class,
                'reference_id' => $reference->getKey(),
            ]);
        });
    }

    /**
     * إضافة منتج تام من تشغيل تصنيع مع تحديث متوسط تكلفة الصنف (نفس منطق إذن الإضافة على مستوى المستودع).
     */
    public function receiveManufacturingOutput(
        Item $finishedItem,
        int $warehouseId,
        float $quantity,
        ManufacturingRun $reference,
        float $unitBatchCost,
    ): void {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($finishedItem, $warehouseId, $quantity, $reference, $unitBatchCost) {
            /** @var Item $lockedItem */
            $lockedItem = Item::query()->whereKey($finishedItem->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedItem->type !== Item::TYPE_FINISHED_GOOD) {
                throw new RuntimeException('مخرجات التصنيع يجب أن تكون صنفاً من نوع منتج تام.');
            }

            $uid = (int) $lockedItem->user_id;

            $pivot = ItemWarehouse::query()->firstOrCreate(
                [
                    'user_id' => $uid,
                    'item_id' => $lockedItem->id,
                    'warehouse_id' => $warehouseId,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );
            $pivot = ItemWarehouse::query()
                ->whereKey($pivot->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldPivotQty = (float) $pivot->quantity;
            $pivot->increment('quantity', $quantity);

            $newPivotQty = $oldPivotQty + $quantity;
            if ($newPivotQty > 0 && $unitBatchCost >= 0) {
                $oldCost = (float) ($lockedItem->cost ?? 0);
                $newAvgCost = ($oldPivotQty * $oldCost + $quantity * $unitBatchCost) / $newPivotQty;
                Item::query()->whereKey($lockedItem->id)->update(['cost' => round($newAvgCost, 4)]);
            }

            $sum = (float) ItemWarehouse::query()
                ->where('item_id', $lockedItem->id)
                ->where('user_id', $uid)
                ->sum(DB::raw('quantity - reserved_quantity'));

            Item::query()->whereKey($lockedItem->id)->update(['current_stock' => round($sum, 4)]);

            StockMovement::query()->create([
                'user_id' => $uid,
                'warehouse_id' => $warehouseId,
                'item_id' => $lockedItem->id,
                'quantity' => $quantity,
                'movement_type' => 'manufacturing_in',
                'reference_type' => ManufacturingRun::class,
                'reference_id' => $reference->getKey(),
            ]);
        });
    }
}
