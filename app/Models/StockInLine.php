<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInLine extends Model
{
    protected $fillable = [
        'stock_in_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'purchase_price' => 'decimal:4',
        ];
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
