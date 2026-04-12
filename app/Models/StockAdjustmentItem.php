<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'item_id',
        'quantity',
        'unit_cost',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'  => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
