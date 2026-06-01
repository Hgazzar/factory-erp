<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقرير تقييم المخزون: كمية × تكلفة الوحدة لكل صنف.
 */
final class InventoryValuationReportService
{
    /**
     * @return Collection<int, object{
     *     item_id: int,
     *     code: string,
     *     name: string,
     *     quantity: float,
     *     unit_cost: float,
     *     total_value: float,
     * }>
     */
    public function rows(int $tenantUserId, ?string $search = null): Collection
    {
        $totalsByItem = DB::table('item_warehouse')
            ->join('items', 'items.id', '=', 'item_warehouse.item_id')
            ->where('items.user_id', $tenantUserId)
            ->when(filled($search), function ($q) use ($search): void {
                $term = '%'.trim($search).'%';
                $q->where(function ($sub) use ($term): void {
                    $sub->where('items.code', 'like', $term)
                        ->orWhere('items.name_ar', 'like', $term)
                        ->orWhere('items.name_en', 'like', $term);
                });
            })
            ->groupBy('item_warehouse.item_id')
            ->select([
                'item_warehouse.item_id',
                DB::raw('MAX(items.code) as code'),
                DB::raw('MAX(items.name_ar) as name_ar'),
                DB::raw('MAX(items.name_en) as name_en'),
                DB::raw('MAX(items.cost) as cost'),
                DB::raw('SUM(item_warehouse.quantity) as total_qty'),
            ])
            ->orderBy('items.code')
            ->get();

        return $totalsByItem->map(function ($row): object {
            $qty = (float) ($row->total_qty ?? 0);
            $unitCost = (float) ($row->cost ?? 0);
            $name = trim((string) ($row->name_ar ?? ''));
            if ($name === '') {
                $name = trim((string) ($row->name_en ?? ''));
            }
            if ($name === '') {
                $name = (string) ($row->code ?? '—');
            }

            return (object) [
                'item_id' => (int) $row->item_id,
                'code' => (string) ($row->code ?? ''),
                'name' => $name,
                'quantity' => round($qty, 4),
                'unit_cost' => round($unitCost, 4),
                'total_value' => round($qty * $unitCost, 4),
            ];
        });
    }

    public function grandTotal(Collection $rows): float
    {
        return round((float) $rows->sum(fn ($r) => (float) $r->total_value), 4);
    }
}
