<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use Illuminate\Support\Collection;

final class PosCatalogService
{
    /**
     * @return list<array{id:int, name:string, code:?string}>
     */
    public function activeCategories(int $tenantUserId, bool $onlineOnly = false): array
    {
        $query = PosProductCategory::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true);

        if ($onlineOnly) {
            $query->whereHas('products', function ($q) use ($tenantUserId): void {
                $q->where('user_id', $tenantUserId)
                    ->where('is_active', true)
                    ->where('is_published_online', true);
            });
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (PosProductCategory $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'code' => $category->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchProducts(
        int $tenantUserId,
        ?string $query,
        ?int $categoryId = null,
        int $limit = 48,
        bool $onlineOnly = false,
    ): array {
        $builder = PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->with(['category:id,name'])
            ->orderBy('name');

        if ($onlineOnly) {
            $builder->where('is_published_online', true);
        }

        if ($categoryId !== null && $categoryId > 0) {
            $builder->where('pos_product_category_id', $categoryId);
        }

        $term = trim((string) $query);
        if ($term !== '') {
            $builder->where(function ($q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%');
            });
        }

        return $builder
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (PosProduct $product): array => $this->productPayload($product))
            ->values()
            ->all();
    }

    public function findByBarcode(int $tenantUserId, string $barcode): ?PosProduct
    {
        $barcode = trim($barcode);
        if ($barcode === '') {
            return null;
        }

        return PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->where('barcode', $barcode)
            ->with(['category:id,name'])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function productPayload(PosProduct $product): array
    {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'sale_price' => round((float) $product->sale_price, 4),
            'vat_percent' => round((float) $product->vat_percent, 4),
            'current_quantity' => round((float) $product->current_quantity, 4),
            'low_stock_alert_quantity' => round((float) $product->low_stock_alert_quantity, 4),
            'category_id' => $product->pos_product_category_id ? (int) $product->pos_product_category_id : null,
            'category_name' => $product->category?->name,
            'initial' => mb_substr((string) $product->name, 0, 1),
            'description' => $product->description,
            'is_published_online' => (bool) $product->is_published_online,
        ];
    }

    public function findPublishedProduct(int $tenantUserId, int $productId): ?PosProduct
    {
        if ($productId < 1) {
            return null;
        }

        return PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->where('is_published_online', true)
            ->whereKey($productId)
            ->with(['category:id,name'])
            ->first();
    }

    /**
     * @return Collection<int, PosProduct>
     */
    public function productsForGrid(int $tenantUserId, ?int $categoryId = null): Collection
    {
        $builder = PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->with(['category:id,name'])
            ->orderBy('name');

        if ($categoryId !== null && $categoryId > 0) {
            $builder->where('pos_product_category_id', $categoryId);
        }

        return $builder->limit(120)->get();
    }
}
