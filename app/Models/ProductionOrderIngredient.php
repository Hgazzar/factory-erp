<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderIngredient extends Model
{
    public $timestamps = false;

    protected $table = 'production_ingredients';

    protected $fillable = [
        'production_order_id',
        'item_id',
        'quantity_to_consume',
    ];

    protected function casts(): array
    {
        return [
            'quantity_to_consume' => 'decimal:4',
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
