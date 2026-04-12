<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePart extends Model
{
    protected $fillable = [
        'service_order_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
