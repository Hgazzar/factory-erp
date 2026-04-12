<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;
    use ResolvesRouteBindingForTenant;

    public const TYPE_RAW_MATERIAL = 'raw_material';

    public const TYPE_FINISHED_GOOD = 'finished_good';

    public const TYPE_SERVICE = 'service';

    /**
     * قيم نوع الصنف المتوافقة مع عمود type في قاعدة البيانات.
     *
     * @return list<string>
     */
    public static function typeValues(): array
    {
        return [
            self::TYPE_RAW_MATERIAL,
            self::TYPE_FINISHED_GOOD,
            self::TYPE_SERVICE,
        ];
    }

    protected $fillable = [
        'user_id',
        'code',
        'barcode',
        'sku',
        'name_ar',
        'name_en',
        'category_id',
        'unit',
        'unit_id',
        'description',
        'image_path',
        'type',
        'current_stock',
        'min_stock',
        'min_stock_level',
        'max_stock',
        'supplier',
        'material_type',
        'cost',
        'purchase_price',
        'selling_price',
        'sale_price',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'min_stock_level' => 'decimal:4',
            'cost' => 'decimal:4',
            'purchase_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->name_ar ?: ($this->name_en ?: $this->code));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * المخازن والكميات (عبر الجدول الوسيط).
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'item_warehouse')
            ->withPivot('quantity', 'reserved_quantity')
            ->withTimestamps();
    }

    /**
     * سجلات الكمية لكل مخزن.
     */
    public function itemWarehouses(): HasMany
    {
        return $this->hasMany(ItemWarehouse::class);
    }

    public function deliveryOrderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function productionOrderItems(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function productionOrderIngredients(): HasMany
    {
        return $this->hasMany(ProductionOrderIngredient::class);
    }

    /**
     * مكونات BOM المبدئية لمنتج تام (كمية لكل وحدة منتج).
     */
    public function bomComponents(): HasMany
    {
        return $this->hasMany(ItemBomComponent::class, 'finished_item_id');
    }

    /**
     * إجمالي الكمية في كل المخازن.
     */
    public function getTotalQuantityAttribute(): float
    {
        return (float) $this->itemWarehouses()->sum('quantity');
    }

    /**
     * إجمالي الكمية المحجوزة.
     */
    public function getTotalReservedQuantityAttribute(): float
    {
        return (float) $this->itemWarehouses()->sum('reserved_quantity');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * سعر البيع الفعلي: من قائمة الأسعار النشطة (إن وُجدت) وإلا سعر الصنف الافتراضي.
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        $list = PriceList::defaultForType(PriceList::TYPE_SALE);
        if ($list) {
            $p = $list->priceForItem((int) $this->id);
            if ($p !== null) {
                return $p;
            }
        }

        return (float) ($this->selling_price ?? 0);
    }
}
