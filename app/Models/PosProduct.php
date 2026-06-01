<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToTenantContextScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTenantContextScope::class])]
class PosProduct extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'pos_product_category_id',
        'pos_product_brand_id',
        'name',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'sale_price',
        'vat_percent',
        'opening_quantity',
        'current_quantity',
        'low_stock_alert_quantity',
        'is_active',
        'is_published_online',
        'image_url',
        'gallery_urls',
        'compare_at_price',
        'avg_rating',
        'review_count',
        'is_featured',
        'is_trending',
        'is_bestseller',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'float',
            'sale_price' => 'float',
            'compare_at_price' => 'float',
            'vat_percent' => 'float',
            'opening_quantity' => 'float',
            'current_quantity' => 'float',
            'low_stock_alert_quantity' => 'float',
            'avg_rating' => 'float',
            'review_count' => 'integer',
            'is_active' => 'boolean',
            'is_published_online' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_bestseller' => 'boolean',
            'gallery_urls' => 'array',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PosProductCategory::class, 'pos_product_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PosProductBrand::class, 'pos_product_brand_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_product_id');
    }
}
