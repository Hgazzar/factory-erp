<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Models\StoreBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class StorefrontCatalogService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function activeBanners(int $tenantUserId): array
    {
        return StoreBanner::query()
            ->where('tenant_user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (StoreBanner $b): array => [
                'id' => (int) $b->id,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'cta_label' => $b->cta_label,
                'cta_url' => $b->cta_url,
                'image_url' => $b->image_url,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id:int, name:string}>
     */
    public function categories(int $tenantUserId): array
    {
        return PosProductCategory::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $q) => $q
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->where('is_published_online', true))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
            ->values()
            ->all();
    }

    /**
     * @param  array{q?:string, category_id?:int, sort?:string, min_price?:float, max_price?:float, page?:int, per_page?:int, featured?:bool, trending?:bool, bestseller?:bool}  $filters
     */
    public function paginatedProducts(int $tenantUserId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->publishedQuery($tenantUserId);

        if (! empty($filters['category_id'])) {
            $query->where('pos_product_category_id', (int) $filters['category_id']);
        }

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%');
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('sale_price', '>=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('sale_price', '<=', (float) $filters['max_price']);
        }
        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }
        if (! empty($filters['trending'])) {
            $query->where('is_trending', true);
        }
        if (! empty($filters['bestseller'])) {
            $query->where('is_bestseller', true);
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('sale_price'),
            'price_desc' => $query->orderByDesc('sale_price'),
            'rating' => $query->orderByDesc('avg_rating'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('id'),
        };

        $perPage = max(8, min((int) ($filters['per_page'] ?? 12), 48));

        return $query->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1))
            ->through(fn (PosProduct $p) => $this->cardPayload($p));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function productList(int $tenantUserId, int $limit, ?string $scope = null): array
    {
        $query = $this->publishedQuery($tenantUserId)->with(['category:id,name']);

        match ($scope) {
            'featured' => $query->where('is_featured', true),
            'trending' => $query->where('is_trending', true),
            'bestseller' => $query->where('is_bestseller', true),
            default => null,
        };

        return $query->limit(max(1, min($limit, 24)))
            ->get()
            ->map(fn (PosProduct $p) => $this->cardPayload($p))
            ->values()
            ->all();
    }

    public function findPublishedProduct(int $tenantUserId, int $productId): ?PosProduct
    {
        return $this->publishedQuery($tenantUserId)
            ->with(['category:id,name'])
            ->whereKey($productId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(PosProduct $product, string $tenantSlug): array
    {
        $card = $this->cardPayload($product);
        $gallery = is_array($product->gallery_urls) ? $product->gallery_urls : [];
        if ($product->image_url) {
            array_unshift($gallery, $product->image_url);
        }
        $gallery = array_values(array_unique(array_filter($gallery)));

        return array_merge($card, [
            'description' => $product->description,
            'seo_title' => $product->seo_title ?: $product->name,
            'seo_description' => $product->seo_description,
            'gallery' => $gallery ?: [$this->placeholderImage($product->name)],
            'url' => route('store.portal.product', ['tenant_slug' => $tenantSlug, 'product' => $product->id]),
            'related' => [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function cardPayload(PosProduct $product): array
    {
        $sale = round((float) $product->sale_price, 2);
        $compare = $product->compare_at_price !== null ? round((float) $product->compare_at_price, 2) : null;
        $discountPercent = ($compare !== null && $compare > $sale)
            ? (int) round((1 - $sale / $compare) * 100)
            : 0;

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => $product->sku,
            'sale_price' => $sale,
            'compare_at_price' => $compare,
            'discount_percent' => $discountPercent,
            'vat_percent' => round((float) $product->vat_percent, 2),
            'image_url' => $product->image_url ?: $this->placeholderImage($product->name),
            'category_id' => $product->pos_product_category_id ? (int) $product->pos_product_category_id : null,
            'category_name' => $product->category?->name,
            'avg_rating' => round((float) $product->avg_rating, 1),
            'review_count' => (int) $product->review_count,
            'in_stock' => (float) $product->current_quantity > 0,
            'stock_qty' => round((float) $product->current_quantity, 2),
            'is_featured' => (bool) $product->is_featured,
            'is_trending' => (bool) $product->is_trending,
            'is_bestseller' => (bool) $product->is_bestseller,
        ];
    }

    private function publishedQuery(int $tenantUserId): Builder
    {
        // بدون Global Scope: الزائر قد يكون مسجّل دخوله كمستأجر آخر (ERP) بينما slug المتجر يخص tenant مختلف.
        return PosProduct::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->where('is_published_online', true);
    }

    private function placeholderImage(string $name): string
    {
        $seed = rawurlencode(mb_substr($name, 0, 24));

        return "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80&fit=crop&auto=format&sig={$seed}";
    }
}
