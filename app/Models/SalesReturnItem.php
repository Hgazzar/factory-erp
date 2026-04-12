<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $table = 'sales_return_items';

    protected $fillable = [
        'sales_return_id',
        'item_id',
        'quantity',
        'unit_price',
        'tax_percent',
        'line_total',
        'line_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_percent' => 'decimal:2',
            'line_total' => 'decimal:4',
        ];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
