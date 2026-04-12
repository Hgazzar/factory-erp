<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $fillable = [
        'user_id',
        'transfer_number',
        'transfer_date',
        'expected_arrival_date',
        'source_warehouse_id',
        'dest_warehouse_id',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'expected_arrival_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dest_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }
}
