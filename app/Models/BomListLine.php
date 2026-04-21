<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomListLine extends Model
{
    protected $fillable = [
        'bom_list_id',
        'component_item_id',
        'quantity',
        'unit',
        'scrap_percent',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'scrap_percent' => 'decimal:4',
        ];
    }

    public function bomList(): BelongsTo
    {
        return $this->belongsTo(BomList::class);
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}
