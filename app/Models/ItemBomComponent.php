<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBomComponent extends Model
{
    protected $fillable = [
        'finished_item_id',
        'component_item_id',
        'quantity_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_unit' => 'decimal:4',
        ];
    }

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}
