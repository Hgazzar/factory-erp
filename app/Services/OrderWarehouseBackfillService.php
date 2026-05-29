<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\ProductionOrder;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * يملأ مستودعات أوامر الإنتاج/التوريد القديمة التي أُنشئت قبل ربط المستودعات.
 * لا يعيد ترحيل مخزون — يحدّث الحقول المرجعية فقط.
 */
final class OrderWarehouseBackfillService
{
    /**
     * @return array{
     *     production: list<array{order_id: int, production_number: string, user_id: int|null, warehouse_id: int|null, status: string, note: string}>,
     *     delivery: list<array{order_id: int, delivery_number: string, user_id: int, warehouse_id: int|null, status: string, note: string}>,
     *     updated_production: int,
     *     updated_delivery: int,
     *     skipped_production: int,
     *     skipped_delivery: int,
     * }
     */
    public function run(bool $dryRun = true, ?int $onlyUserId = null): array
    {
        $productionRows = [];
        $deliveryRows = [];
        $updatedProduction = 0;
        $updatedDelivery = 0;
        $skippedProduction = 0;
        $skippedDelivery = 0;

        $productionQuery = ProductionOrder::query()
            ->where(function ($q): void {
                $q->whereNull('raw_materials_warehouse_id')
                    ->orWhereNull('finished_goods_warehouse_id');
            })
            ->orderBy('id');

        foreach ($productionQuery->cursor() as $order) {
            $tenantId = $this->inferProductionOrderTenantId((int) $order->id);

            if ($onlyUserId !== null && $tenantId !== null && $tenantId !== $onlyUserId) {
                continue;
            }

            if ($onlyUserId !== null && $tenantId === null) {
                $skippedProduction++;
                $productionRows[] = $this->productionRow($order, null, null, 'تخطي — لا يُستنتج مستأجر (فلتر user-id)');

                continue;
            }

            $warehouseId = $tenantId !== null ? $this->resolveDefaultWarehouseId($tenantId) : null;

            if ($warehouseId === null) {
                $skippedProduction++;
                $productionRows[] = $this->productionRow(
                    $order,
                    $tenantId,
                    null,
                    $tenantId === null
                        ? 'تخطي — لا user_id على بنود الأمر'
                        : 'تخطي — لا مستودع افتراضي/نشط لهذا المستأجر'
                );

                continue;
            }

            $needsRm = $order->raw_materials_warehouse_id === null;
            $needsFg = $order->finished_goods_warehouse_id === null;

            if (! $dryRun) {
                $payload = [];
                if ($needsRm) {
                    $payload['raw_materials_warehouse_id'] = $warehouseId;
                }
                if ($needsFg) {
                    $payload['finished_goods_warehouse_id'] = $warehouseId;
                }
                if ($payload !== []) {
                    ProductionOrder::query()->whereKey($order->id)->update($payload);
                }
            }

            $updatedProduction++;
            $productionRows[] = $this->productionRow(
                $order,
                $tenantId,
                $warehouseId,
                $dryRun ? 'معاينة — سيُعيَّن المستودع الافتراضي' : 'تم التعيين'
            );
        }

        $deliveryQuery = DeliveryOrder::withoutGlobalScopes()
            ->whereNull('warehouse_id')
            ->orderBy('id');

        if ($onlyUserId !== null) {
            $deliveryQuery->where('user_id', $onlyUserId);
        }

        foreach ($deliveryQuery->cursor() as $delivery) {
            $tenantId = (int) $delivery->user_id;
            $warehouseId = $this->resolveDefaultWarehouseId($tenantId);

            if ($warehouseId === null) {
                $skippedDelivery++;
                $deliveryRows[] = $this->deliveryRow(
                    $delivery,
                    null,
                    'تخطي — لا مستودع افتراضي/نشط'
                );

                continue;
            }

            if (! $dryRun) {
                DeliveryOrder::withoutGlobalScopes()
                    ->whereKey($delivery->id)
                    ->update(['warehouse_id' => $warehouseId]);
            }

            $updatedDelivery++;
            $deliveryRows[] = $this->deliveryRow(
                $delivery,
                $warehouseId,
                $dryRun ? 'معاينة' : 'تم التعيين'
            );
        }

        return [
            'production' => $productionRows,
            'delivery' => $deliveryRows,
            'updated_production' => $updatedProduction,
            'updated_delivery' => $updatedDelivery,
            'skipped_production' => $skippedProduction,
            'skipped_delivery' => $skippedDelivery,
        ];
    }

    public function resolveDefaultWarehouseId(int $tenantUserId): ?int
    {
        $id = Warehouse::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * يستنتج مستأجر أمر الإنتاج من user_id على الأصناف المرتبطة (خامات أو منتج تام).
     */
    public function inferProductionOrderTenantId(int $productionOrderId): ?int
    {
        $fromIngredients = DB::table('production_ingredients')
            ->join('items', 'items.id', '=', 'production_ingredients.item_id')
            ->where('production_ingredients.production_order_id', $productionOrderId)
            ->distinct()
            ->pluck('items.user_id');

        $fromFinished = DB::table('production_items')
            ->join('items', 'items.id', '=', 'production_items.item_id')
            ->where('production_items.production_order_id', $productionOrderId)
            ->distinct()
            ->pluck('items.user_id');

        /** @var Collection<int, int> $userIds */
        $userIds = $fromIngredients
            ->merge($fromFinished)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($userIds->count() === 1) {
            return (int) $userIds->first();
        }

        return null;
    }

    /**
     * @return array{order_id: int, production_number: string, user_id: int|null, warehouse_id: int|null, status: string, note: string}
     */
    private function productionRow(
        ProductionOrder $order,
        ?int $tenantId,
        ?int $warehouseId,
        string $note,
    ): array {
        return [
            'order_id' => (int) $order->id,
            'production_number' => (string) $order->production_number,
            'user_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'status' => (string) $order->status,
            'note' => $note,
        ];
    }

    /**
     * @return array{order_id: int, delivery_number: string, user_id: int, warehouse_id: int|null, status: string, note: string}
     */
    private function deliveryRow(DeliveryOrder $delivery, ?int $warehouseId, string $note): array
    {
        return [
            'order_id' => (int) $delivery->id,
            'delivery_number' => (string) $delivery->delivery_number,
            'user_id' => (int) $delivery->user_id,
            'warehouse_id' => $warehouseId,
            'status' => (string) $delivery->status,
            'note' => $note,
        ];
    }
}
