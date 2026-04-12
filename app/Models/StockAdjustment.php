<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'adjustment_number',
        'adjustment_date',
        'warehouse_id',
        'cost_center_id',
        'type',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);

        static::creating(function (StockAdjustment $model): void {
            if (! $model->user_id && $model->warehouse_id) {
                $model->user_id = (int) (Warehouse::withoutGlobalScopes()
                    ->where('id', $model->warehouse_id)
                    ->value('user_id') ?? auth()->id() ?? 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'stock_adjustment_id');
    }

    public function isAdd(): bool
    {
        return $this->type === 'add';
    }

    public function isDeduct(): bool
    {
        return $this->type === 'deduct';
    }
}
