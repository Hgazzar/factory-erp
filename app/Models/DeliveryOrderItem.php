<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'delivery_order_id',
        'sales_order_item_id',
        'item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
