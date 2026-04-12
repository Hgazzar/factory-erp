<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends Model
{
    public $timestamps = false;

    protected $table = 'production_items';

    protected $fillable = [
        'production_order_id',
        'item_id',
        'planned_quantity',
        'produced_quantity',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'produced_quantity' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
