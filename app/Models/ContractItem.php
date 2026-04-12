<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    protected $fillable = [
        'contract_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'tax_percent',
        'line_total',
        'sort_order',
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
