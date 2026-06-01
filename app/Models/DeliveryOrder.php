<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeliveryOrder extends Model
{
    use ResolvesRouteBindingForTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'sales_order_id',
        'warehouse_id',
        'delivery_number',
        'status',
        'delivery_date',
        'notes',
        'journal_entry_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * تأكيد التسليم (لوجستي فقط). خصم المخزون والقيود يتم عند ترحيل فاتورة المبيعات.
     */
    public function markAsDelivered(): void
    {
        $id = $this->getKey();

        DB::transaction(function () use ($id) {
            /** @var self $delivery */
            $delivery = static::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if ($delivery->status !== self::STATUS_PENDING) {
                throw new RuntimeException('يمكن تأكيد التسليم فقط لأمر توريد في حالة «قيد الانتظار».');
            }

            $warehouseId = (int) ($delivery->warehouse_id ?? 0);
            if ($warehouseId < 1) {
                throw new RuntimeException('يجب تحديد مستودع التوريد على أمر التوريد قبل التأكيد.');
            }

            $statusBefore = $delivery->status;

            $delivery->status = self::STATUS_DELIVERED;
            if (! $delivery->delivery_date) {
                $delivery->delivery_date = now()->toDateString();
            }
            $delivery->save();

            InstalledAsset::syncFromDeliveredOrder($delivery);

            AuditTrail::log('update', 'delivery_orders', $delivery->id, [
                'status' => $statusBefore,
                'delivery_number' => $delivery->delivery_number,
            ], [
                'status' => self::STATUS_DELIVERED,
                'delivery_number' => $delivery->delivery_number,
                'warehouse_id' => $warehouseId,
            ]);
        });

        $this->refresh();
    }
}
