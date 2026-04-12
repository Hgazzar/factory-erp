<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstalledAsset extends Model
{
    public const DEFAULT_WARRANTY_MONTHS = 12;

    protected $fillable = [
        'delivery_order_id',
        'delivery_order_item_id',
        'item_id',
        'installation_location',
        'warranty_start',
        'warranty_end',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'warranty_start' => 'date',
            'warranty_end' => 'date',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function deliveryOrderItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'installed_asset_id');
    }

    public function isWarrantyActive(?Carbon $on = null): bool
    {
        $on = $on ?? Carbon::today();
        if (! $this->warranty_end) {
            return false;
        }

        return $this->warranty_end->greaterThanOrEqualTo($on);
    }

    public static function syncFromDeliveredOrder(DeliveryOrder $delivery): void
    {
        $delivery->loadMissing(['items.item']);

        foreach ($delivery->items as $line) {
            $item = $line->item;
            if (! $item || $item->type !== Item::TYPE_FINISHED_GOOD) {
                continue;
            }

            if (static::query()->where('delivery_order_item_id', $line->id)->exists()) {
                continue;
            }

            $start = $delivery->delivery_date?->toDateString() ?? now()->toDateString();
            $end = Carbon::parse($start)->addMonths(self::DEFAULT_WARRANTY_MONTHS)->toDateString();

            static::query()->create([
                'delivery_order_id' => $delivery->id,
                'delivery_order_item_id' => $line->id,
                'item_id' => $item->id,
                'warranty_start' => $start,
                'warranty_end' => $end,
            ]);
        }
    }
}
