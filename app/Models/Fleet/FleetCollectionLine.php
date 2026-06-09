<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetCollectionLine extends Model
{
    protected $table = 'fleet_collection_lines';

    protected $fillable = [
        'user_id',
        'collection_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
            'line_total' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FleetProduct::class, 'product_id');
    }
}
