<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleLine extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'item_id',
        'quantity',
        'unit_price',
        'line_total',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
