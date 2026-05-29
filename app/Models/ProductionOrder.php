<?php

namespace App\Models;

use App\Services\InventoryAccountingService;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'production_number',
        'status',
        'start_date',
        'end_date',
        'raw_materials_warehouse_id',
        'finished_goods_warehouse_id',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function productionItems(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductionOrderIngredient::class);
    }

    public function rawMaterialsWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'raw_materials_warehouse_id');
    }

    public function finishedGoodsWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'finished_goods_warehouse_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * إتمام الإنتاج: صرف خامات من مستودع محدد، إدخال منتج تام لمستودع محدد، وقيد محاسبي.
     *
     * @param  array<int, string|float|int>  $producedByLineId  مفاتيحها id لسجل production_items والقيمة الكمية المنتَجة
     */
    public function markCompleted(array $producedByLineId): void
    {
        $orderId = (int) $this->getKey();

        DB::transaction(function () use ($orderId, $producedByLineId) {
            /** @var self $order */
            $order = static::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();

            $statusBefore = $order->status;

            if (! in_array($order->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true)) {
                throw new RuntimeException('يمكن إتمام الإنتاج فقط لأمر في حالة «معلق» أو «قيد التنفيذ».');
            }

            $rmWarehouseId = (int) ($order->raw_materials_warehouse_id ?? 0);
            $fgWarehouseId = (int) ($order->finished_goods_warehouse_id ?? 0);
            if ($rmWarehouseId < 1 || $fgWarehouseId < 1) {
                throw new RuntimeException('يجب تحديد مستودع الخامات ومستودع المنتج التام على أمر الإنتاج قبل الإتمام.');
            }

            $prodLines = ProductionOrderItem::query()
                ->where('production_order_id', $order->id)
                ->orderBy('id')
                ->get();

            if ($prodLines->isEmpty()) {
                throw new RuntimeException('لا توجد بنود منتج تام على أمر الإنتاج.');
            }

            foreach ($prodLines as $line) {
                $raw = $producedByLineId[$line->id] ?? null;
                $qty = $raw !== null && $raw !== '' ? (float) $raw : null;
                if ($qty === null || $qty <= 0) {
                    throw new RuntimeException('أدخل كمية منتَجة أكبر من صفر لكل بند منتج تام.');
                }
                $line->produced_quantity = $qty;
                $line->save();
            }

            $ingredientRows = ProductionOrderIngredient::query()
                ->where('production_order_id', $order->id)
                ->orderBy('item_id')
                ->get();

            if ($ingredientRows->isEmpty()) {
                throw new RuntimeException('لا توجد مواد خام مسجلة على أمر الإنتاج.');
            }

            $inventory = app(InventoryService::class);
            $materialsValue = 0.0;

            foreach ($ingredientRows as $row) {
                $item = Item::query()->whereKey($row->item_id)->firstOrFail();

                if ($item->type !== Item::TYPE_RAW_MATERIAL) {
                    throw new RuntimeException(
                        sprintf('الصنف «%s» ليس مادة خام؛ يجب أن تكون جميع بنود الاستهلاك من نوع مادة خام.', $item->code)
                    );
                }

                $need = (float) $row->quantity_to_consume;
                $unitCost = (float) ($item->cost ?? 0);
                $materialsValue += $need * $unitCost;

                $inventory->consumeForProductionOrder($item, $rmWarehouseId, $need, $order);
            }

            $materialsValue = round($materialsValue, 4);
            $totalProduced = (float) $prodLines->sum(fn (ProductionOrderItem $line) => (float) $line->produced_quantity);

            foreach ($prodLines as $line) {
                $item = Item::query()->whereKey($line->item_id)->firstOrFail();

                if ($item->type !== Item::TYPE_FINISHED_GOOD) {
                    throw new RuntimeException(
                        sprintf('الصنف «%s» ليس منتجاً تاماً؛ يجب أن تكون بنود التصنيع من نوع منتج تام.', $item->code)
                    );
                }

                $add = (float) $line->produced_quantity;
                $share = $totalProduced > 0 ? ($add / $totalProduced) : 0.0;
                $lineMaterials = round($materialsValue * $share, 4);
                $unitBatchCost = $add > 0 ? round($lineMaterials / $add, 4) : 0.0;

                $inventory->receiveProductionOrderOutput($item, $fgWarehouseId, $add, $order, $unitBatchCost);
            }

            $journalEntry = null;
            if ($materialsValue > 0) {
                $journalEntry = app(InventoryAccountingService::class)
                    ->createProductionCompletionEntry($order, $materialsValue);
            }

            $order->journal_entry_id = $journalEntry?->id;
            $order->status = self::STATUS_COMPLETED;
            $order->end_date = now()->toDateString();
            $order->save();

            AuditTrail::log('complete', 'production_orders', $order->id, [
                'status' => $statusBefore,
                'production_number' => $order->production_number,
            ], [
                'status' => self::STATUS_COMPLETED,
                'production_number' => $order->production_number,
                'journal_entry_id' => $order->journal_entry_id,
                'raw_materials_warehouse_id' => $rmWarehouseId,
                'finished_goods_warehouse_id' => $fgWarehouseId,
            ]);
        });

        $this->refresh();
    }
}
