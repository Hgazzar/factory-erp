<?php

namespace App\Models;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use App\Services\InventoryAccountingService;
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

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * تأكيد التسليم: خصم من current_stock للأصناف من نوع منتج تام أو مادة خام فقط (تُتجاهل الخدمات).
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

            $statusBefore = $delivery->status;

            $lines = $delivery->items()->with('item:id,code,name_ar,type,current_stock,cost')->get();
            $split = app(InventoryAccountingService::class)->summarizeDeliveryLinesForCost($lines);

            foreach ($lines as $line) {
                $item = Item::query()->whereKey($line->item_id)->lockForUpdate()->firstOrFail();

                if (! in_array($item->type, [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD], true)) {
                    continue;
                }

                $current = (float) ($item->current_stock ?? 0);
                $need = (float) $line->quantity;

                if ($current + 0.0000001 < $need) {
                    throw new RuntimeException(
                        sprintf(
                            'رصيد الصنف «%s» غير كافٍ للتسليم (المتاح: %s، المطلوب: %s).',
                            $item->code,
                            rtrim(rtrim(number_format($current, 4, '.', ''), '0'), '.') ?: '0',
                            rtrim(rtrim(number_format($need, 4, '.', ''), '0'), '.') ?: '0'
                        )
                    );
                }

                $item->current_stock = $current - $need;
                $item->save();
            }

            $journalEntry = app(InventoryAccountingService::class)->createDeliveryCostEntry($delivery, $split);

            $delivery->journal_entry_id = $journalEntry?->id;
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
                'journal_entry_id' => $delivery->journal_entry_id,
            ]);
        });

        $this->refresh();
    }
}
