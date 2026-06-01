<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * طبقة منطق الأصناف والمخزون (Headless-ready) — قراءة الكatalog والكميات.
 *
 * ملاحظة: App\Services\InventoryService (الجذر) يبقى مسؤولاً عن حركات الصرف/الإضافة
 * الداخلية (تصنيع، POS، خدمات…) ولا يُعاد تسميته لتجنّب كسر الاستدعاءات الحالية.
 */
final class InventoryItemService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     warehouse_id?: int,
     *     category?: string,
     *     status?: string,
     *     per_page?: int,
     *     page?: int
     * }  $filters
     */
    public function paginateItems(int $tenantUserId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));

        return $this->buildItemsQuery($tenantUserId, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Item>
     */
    public function listItemsForExport(int $tenantUserId, array $filters = []): Collection
    {
        return $this->buildItemsQuery($tenantUserId, $filters)->get();
    }

    public function findItemForTenant(int $tenantUserId, int $itemId): Item
    {
        $item = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($itemId)
            ->with([
                'unit:id,name_ar,code',
                'warehouses:id,name_ar,name_en,code',
                'bomComponents.componentItem:id,code,name_ar,type',
                'attachments',
            ])
            ->withSum('warehouses as total_stock', 'item_warehouse.quantity')
            ->first();

        if ($item === null) {
            throw new RuntimeException('الصنف غير موجود أو لا ينتمي لهذا المستأجر.');
        }

        return $item;
    }

    /**
     * كميات الصنف لكل مستودع (مع المتاح = quantity - reserved).
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function warehouseQuantities(int $tenantUserId, int $itemId): SupportCollection
    {
        $this->assertItemBelongsToTenant($tenantUserId, $itemId);

        return ItemWarehouse::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('item_id', $itemId)
            ->with('warehouse:id,code,name_ar,name_en')
            ->get()
            ->map(fn (ItemWarehouse $row) => [
                'warehouse_id' => $row->warehouse_id,
                'warehouse_code' => $row->warehouse?->code,
                'warehouse_name' => $row->warehouse?->name_ar ?? $row->warehouse?->name_en,
                'quantity' => (float) $row->quantity,
                'reserved_quantity' => (float) $row->reserved_quantity,
                'available_quantity' => (float) $row->available_quantity,
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createItem(
        int $tenantUserId,
        array $attributes,
        int $warehouseId,
        float $initialQuantity,
    ): Item {
        $this->assertWarehouseBelongsToTenant($tenantUserId, $warehouseId);

        return DB::transaction(function () use ($tenantUserId, $attributes, $warehouseId, $initialQuantity): Item {
            $item = Item::query()->create(array_merge($attributes, [
                'user_id' => $tenantUserId,
            ]));

            $item->warehouses()->attach($warehouseId, [
                'user_id' => $tenantUserId,
                'quantity' => $initialQuantity,
                'reserved_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($initialQuantity > 0) {
                Item::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($item->id)
                    ->update(['current_stock' => $initialQuantity]);
            }

            return $item->fresh(['unit:id,name_ar,code']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateItem(int $tenantUserId, int $itemId, array $attributes): Item
    {
        $item = $this->findItemForTenant($tenantUserId, $itemId);

        $item->update(array_merge($attributes, [
            'user_id' => $tenantUserId,
        ]));

        return $item->fresh(['unit:id,name_ar,code', 'attachments']);
    }

    public function deleteItem(int $tenantUserId, int $itemId): void
    {
        $item = $this->findItemForTenant($tenantUserId, $itemId);

        DB::transaction(function () use ($item, $tenantUserId, $itemId): void {
            ItemWarehouse::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('item_id', $itemId)
                ->delete();

            $item->warehouses()->detach();
            $item->delete();
        });
    }

    public function resolveTenantUserId(?User $user = null): int
    {
        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId !== null) {
            return $tenantUserId;
        }

        if ($this->tenantContext->isPlatformOperator($user) && auth()->check()) {
            return (int) auth()->id();
        }

        throw new RuntimeException('تعذّر تحديد المستأجر لهذه العملية.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiSummary(Item $item): array
    {
        $stock = (float) ($item->total_stock ?? $item->current_stock ?? 0);

        return [
            'id' => $item->id,
            'code' => $item->code,
            'barcode' => $item->barcode,
            'name_ar' => $item->name_ar,
            'name_en' => $item->name_en,
            'type' => $item->type,
            'unit' => $item->unit ? [
                'id' => $item->unit->id,
                'code' => $item->unit->code,
                'name_ar' => $item->unit->name_ar,
            ] : null,
            'cost' => $item->cost !== null ? (float) $item->cost : null,
            'selling_price' => $item->selling_price !== null ? (float) $item->selling_price : null,
            'min_stock' => (float) ($item->min_stock ?? 0),
            'total_stock' => $stock,
            'stock_status' => $this->stockStatusLabel($stock, (float) ($item->min_stock ?? 0)),
            'is_active' => (bool) $item->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiDetail(Item $item, SupportCollection $quantities): array
    {
        $summary = $this->toApiSummary($item);

        $summary['description'] = $item->description;
        $summary['supplier'] = $item->supplier;
        $summary['material_type'] = $item->material_type;
        $summary['warehouses'] = $quantities->all();
        $summary['bom_components'] = $item->relationLoaded('bomComponents')
            ? $item->bomComponents->map(fn ($c) => [
                'component_item_id' => $c->component_item_id,
                'component_code' => $c->componentItem?->code,
                'component_name' => $c->componentItem?->name_ar,
                'quantity_per_unit' => (float) $c->quantity_per_unit,
            ])->values()->all()
            : [];

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildItemsQuery(int $tenantUserId, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        $category = (string) ($filters['category'] ?? '');
        $status = (string) ($filters['status'] ?? '');

        $stockSubquery = '(SELECT COALESCE(SUM(iw.quantity), 0) FROM item_warehouse iw WHERE iw.item_id = items.id)';

        $query = Item::query()
            ->withoutGlobalScopes()
            ->where('items.user_id', $tenantUserId)
            ->with(['unit:id,name_ar,code', 'warehouses:id,name_ar,name_en,code', 'attachments'])
            ->withSum('warehouses as total_stock', 'item_warehouse.quantity');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%');
            });
        }

        if ($warehouseId > 0) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouses.id', $warehouseId);
            });
        }

        if (in_array($category, Item::typeValues(), true)) {
            $query->where('type', $category);
        }

        if (in_array($status, ['available', 'low', 'out'], true)) {
            if ($status === 'out') {
                $query->whereRaw($stockSubquery.' <= 0');
            } elseif ($status === 'low') {
                $query->whereRaw($stockSubquery.' > 0')
                    ->whereRaw('COALESCE(items.min_stock, 0) > 0')
                    ->whereRaw($stockSubquery.' <= items.min_stock');
            } else {
                $query->whereRaw('('.$stockSubquery.' > COALESCE(items.min_stock, 0)) OR ('.$stockSubquery.' > 0 AND COALESCE(items.min_stock, 0) = 0)');
            }
        }

        return $query->orderBy('code');
    }

    private function assertItemBelongsToTenant(int $tenantUserId, int $itemId): void
    {
        $exists = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($itemId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('الصنف غير موجود أو لا ينتمي لهذا المستأجر.');
        }
    }

    private function assertWarehouseBelongsToTenant(int $tenantUserId, int $warehouseId): void
    {
        $exists = Warehouse::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($warehouseId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('المستودع غير موجود أو لا ينتمي لهذا المستأجر.');
        }
    }

    private function stockStatusLabel(float $stock, float $minStock): string
    {
        if ($stock <= 0) {
            return 'out';
        }

        if ($minStock > 0 && $stock <= $minStock) {
            return 'low';
        }

        return 'available';
    }
}
