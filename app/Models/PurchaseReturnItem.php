<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'item_id',
        'quantity',
        'unit_price',
        'vat_percent',
        'line_status',
        'reason',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'vat_percent' => 'decimal:2',
            'line_total' => 'decimal:4',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
