<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ربط تسجيل الإنتاج اللحظي بصرف خامات حسب BOM وإدخال المنتج التام.
 */
final class ProductionEntryInventoryService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {
    }

    /**
     * @return array{applied: bool, total_material_cost: float, unit_batch_cost: float}
     */
    public function applyInventoryForLog(ProductionLog $log, int $warehouseId): array
    {
        $log->load(['item.bomComponents.componentItem']);

        $item = $log->item;
        if (! $item || $item->type !== Item::TYPE_FINISHED_GOOD) {
            return ['applied' => false, 'total_material_cost' => 0.0, 'unit_batch_cost' => 0.0];
        }

        $goodQty = (float) $log->quantity;
        if ($goodQty <= 0) {
            return ['applied' => false, 'total_material_cost' => 0.0, 'unit_batch_cost' => 0.0];
        }

        $bom = $item->bomComponents;
        if ($bom->isEmpty()) {
            $unitBatch = (float) ($item->cost ?? 0);
            $this->inventory->receiveProductionEntryOutput($item, $warehouseId, $goodQty, $log, $unitBatch);
            $log->forceFill(['inventory_synced_at' => now()])->save();

            Log::info('inventory.production_entry', [
                'production_log_id' => $log->id,
                'warehouse_id' => $warehouseId,
                'mode' => 'fg_only_no_bom',
                'finished_item_id' => $item->id,
                'good_qty' => $goodQty,
                'unit_batch_cost' => $unitBatch,
            ]);

            return [
                'applied' => true,
                'total_material_cost' => round($goodQty * $unitBatch, 4),
                'unit_batch_cost' => $unitBatch,
            ];
        }

        return DB::transaction(function () use ($log, $warehouseId, $item, $bom, $goodQty): array {
            $totalMaterialCost = 0.0;

            foreach ($bom as $line) {
                $comp = $line->componentItem;
                if (! $comp) {
                    throw new RuntimeException('مكوّن BOM غير صالح.');
                }
                $perUnit = (float) $line->quantity_per_unit;
                if ($perUnit <= 0) {
                    continue;
                }
                $need = round($perUnit * $goodQty, 4);
                if ($need <= 0) {
                    continue;
                }

                $this->inventory->consumeForProductionEntry($comp, $warehouseId, $need, $log);
                $unitCost = (float) ($comp->cost ?? 0);
                $totalMaterialCost += round($need * $unitCost, 4);
            }

            if ($totalMaterialCost <= 0.0001) {
                throw new RuntimeException('تعذر احتساب تكلفة المواد: تحقق من تكاليف أصناف الخامات في BOM.');
            }

            $unitBatch = round($totalMaterialCost / $goodQty, 4);

            $this->inventory->receiveProductionEntryOutput($item, $warehouseId, $goodQty, $log, $unitBatch);

            $log->forceFill(['inventory_synced_at' => now()])->save();

            Log::info('inventory.production_entry', [
                'production_log_id' => $log->id,
                'warehouse_id' => $warehouseId,
                'mode' => 'bom',
                'finished_item_id' => $item->id,
                'good_qty' => $goodQty,
                'total_material_cost' => $totalMaterialCost,
                'unit_batch_cost' => $unitBatch,
                'bom_lines' => $bom->count(),
            ]);

            return [
                'applied' => true,
                'total_material_cost' => round($totalMaterialCost, 4),
                'unit_batch_cost' => $unitBatch,
            ];
        });
    }
}
