<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'vat_amount',
        'line_total',
        'total_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'vat_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function deliveryOrderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    /**
     * إجمالي الكميات المسجّلة على أوامر توريد غير الملغاة (قيد الانتظار أو مُسلَّمة).
     */
    public function quantityOnOpenOrDeliveredDeliveries(): float
    {
        return (float) $this->deliveryOrderItems()
            ->whereHas('deliveryOrder', fn ($q) => $q->where('status', '!=', DeliveryOrder::STATUS_CANCELLED))
            ->sum('quantity');
    }

    /**
     * الكمية المتبقية المتاحة لتسجيلها على أمر توريد جديد.
     */
    public function remainingQuantityForDelivery(): float
    {
        return max(0, (float) $this->quantity - $this->quantityOnOpenOrDeliveredDeliveries());
    }
}
