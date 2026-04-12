<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemWarehouse extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'item_warehouse';

    protected $fillable = [
        'user_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (ItemWarehouse $model): void {
            if (! $model->user_id && $model->item_id) {
                $model->user_id = (int) (Item::withoutGlobalScopes()
                    ->where('id', $model->item_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * الكمية المتاحة فعلياً (الإجمالي - المحجوز).
     */
    public function getAvailableQuantityAttribute(): float
    {
        return (float) max(0, $this->quantity - $this->reserved_quantity);
    }
}
