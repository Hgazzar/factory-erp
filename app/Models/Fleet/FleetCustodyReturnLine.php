<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetCustodyReturnLine extends Model
{
    protected $table = 'fleet_custody_return_lines';

    protected $fillable = [
        'user_id',
        'return_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FleetProduct::class, 'product_id');
    }
}
