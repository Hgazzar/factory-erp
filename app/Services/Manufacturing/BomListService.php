<?php

declare(strict_types=1);

namespace App\Services\Manufacturing;

use App\Models\BomList;
use App\Models\BomListLine;
use App\Models\ItemBomComponent;
use App\Services\ManufacturingService;
use Illuminate\Support\Facades\DB;

/**
 * مصدر الحقيقة لقوائم المواد (BOM) — Admin + Floor.
 */
final class BomListService
{
    public function activeBomForItem(int $tenantUserId, int $finishedItemId): ?BomList
    {
        return BomList::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('item_id', $finishedItemId)
            ->where('status', BomList::STATUS_ACTIVE)
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'), 'lines.componentItem'])
            ->orderByDesc('id')
            ->first();
    }

    public function consumptionQuantityForLine(BomListLine $line, float $finishedGoodQty): float
    {
        return ManufacturingService::plannedConsumptionFromBomLine(
            (float) $line->quantity,
            (float) ($line->scrap_percent ?? 0),
            $finishedGoodQty
        );
    }

    /**
     * @param  list<array{component_item_id: int, quantity_per_unit: float|int|string}>  $components
     */
    public function syncActiveBomFromItemComponents(int $tenantUserId, int $finishedItemId, array $components): BomList
    {
        return DB::transaction(function () use ($tenantUserId, $finishedItemId, $components): BomList {
            BomList::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('item_id', $finishedItemId)
                ->where('status', BomList::STATUS_ACTIVE)
                ->update(['status' => BomList::STATUS_OBSOLETE]);

            $bom = BomList::query()->create([
                'user_id' => $tenantUserId,
                'item_id' => $finishedItemId,
                'name' => 'وصفة الصنف',
                'version' => now()->format('Y.m.d-His'),
                'status' => BomList::STATUS_ACTIVE,
                'labor_cost' => 0,
                'overhead_cost' => 0,
                'header_notes' => 'Synced from item BOM editor',
            ]);

            foreach ($components as $i => $row) {
                BomListLine::query()->create([
                    'bom_list_id' => $bom->id,
                    'component_item_id' => (int) $row['component_item_id'],
                    'quantity' => (float) $row['quantity_per_unit'],
                    'scrap_percent' => 0,
                    'sort_order' => $i,
                ]);
            }

            $this->mirrorBomListToItemComponents($bom->fresh(['lines']));

            return $bom;
        });
    }

    public function mirrorBomListToItemComponents(BomList $bom): void
    {
        $bom->loadMissing('lines');

        ItemBomComponent::query()
            ->where('finished_item_id', $bom->item_id)
            ->delete();

        foreach ($bom->lines as $line) {
            ItemBomComponent::query()->create([
                'finished_item_id' => $bom->item_id,
                'component_item_id' => $line->component_item_id,
                'quantity_per_unit' => (float) $line->quantity,
            ]);
        }
    }

    public function afterBomListPersisted(BomList $bom): void
    {
        if ($bom->status !== BomList::STATUS_ACTIVE) {
            return;
        }

        BomList::query()
            ->withoutGlobalScopes()
            ->where('user_id', $bom->user_id)
            ->where('item_id', $bom->item_id)
            ->where('status', BomList::STATUS_ACTIVE)
            ->whereKeyNot($bom->id)
            ->update(['status' => BomList::STATUS_OBSOLETE]);

        $this->mirrorBomListToItemComponents($bom);
    }
}
