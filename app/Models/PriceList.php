<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    protected $fillable = [
        'code',
        'name',
        'currency',
        'type',
        'pricing_method',
        'default_margin_percent',
        'valid_from',
        'valid_to',
        'priority',
        'is_default',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'priority' => 'integer',
            'default_margin_percent' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public const TYPE_SALE = 'sale';
    public const TYPE_PURCHASE = 'purchase';
    public const PRICING_FIXED = 'fixed';
    public const PRICING_MARGIN = 'margin';

    public static function types(): array
    {
        return [
            self::TYPE_SALE => 'بيع',
            self::TYPE_PURCHASE => 'شراء',
        ];
    }

    public static function pricingMethods(): array
    {
        return [
            self::PRICING_FIXED => 'سعر ثابت',
            self::PRICING_MARGIN => 'هامش على التكلفة',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }

    /** القائمة الافتراضية للنوع: is_default أولاً، ثم أعلى أولوية، ضمن الصلاحية */
    public static function defaultForType(string $type): ?self
    {
        $today = now()->toDateString();
        return static::where('type', $type)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->first();
    }

    /** سعر الصنف من هذه القائمة أو null */
    public function priceForItem(int $itemId): ?float
    {
        $row = $this->items()->where('item_id', $itemId)->first();
        return $row ? (float) $row->price : null;
    }
}
