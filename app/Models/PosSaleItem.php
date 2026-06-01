<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleItem extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'pos_product_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'vat_percent',
        'vat_amount',
        'line_subtotal',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'float',
            'unit_price' => 'float',
            'vat_percent' => 'float',
            'vat_amount' => 'float',
            'line_subtotal' => 'float',
            'line_total' => 'float',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
