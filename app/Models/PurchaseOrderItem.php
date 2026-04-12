<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'vat_amount',
        'line_total',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:4',
            'tax_percent' => 'decimal:4',
            'vat_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
