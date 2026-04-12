<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAuditLine extends Model
{
    protected $fillable = [
        'inventory_audit_id',
        'item_id',
        'book_quantity',
        'actual_quantity',
        'unit_cost',
        'difference',
        'difference_value',
    ];

    protected function casts(): array
    {
        return [
            'book_quantity'    => 'decimal:4',
            'actual_quantity'  => 'decimal:4',
            'unit_cost'        => 'decimal:4',
            'difference'       => 'decimal:4',
            'difference_value' => 'decimal:4',
        ];
    }

    public function inventoryAudit(): BelongsTo
    {
        return $this->belongsTo(InventoryAudit::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
