<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'item_id',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_percent',
        'tax_percent',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'line_total' => 'decimal:4',
        ];
    }

    /** Alias for product line (items table = products in ERP). */
    public function getProductIdAttribute(): ?int
    {
        return $this->item_id;
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
