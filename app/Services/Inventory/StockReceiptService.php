<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\StockIn;
use App\Models\StockInLine;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Tenant\TenantContext;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * إذن الإضافة المخزني (Stock In / Stock Receipt) — Headless-ready.
 */
final class StockReceiptService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @param  array{
     *     supplier_id: int,
     *     settlement_type: string,
     *     reference?: string|null,
     *     date: string,
     *     notes?: string|null
     * }  $header
     * @param  list<array{item_id: int, warehouse_id: int, quantity: float, purchase_price: float}>  $lines
     */
    public function createReceipt(int $tenantUserId, array $header, array $lines): StockIn
    {
        $normalizedLines = collect($lines)
            ->map(fn (array $line) => [
                'item_id' => (int) $line['item_id'],
                'warehouse_id' => (int) $line['warehouse_id'],
                'quantity' => (float) $line['quantity'],
                'purchase_price' => (float) $line['purchase_price'],
            ])
            ->filter(fn (array $l) => $l['quantity'] > 0)
            ->values();

        if ($normalizedLines->isEmpty()) {
            throw new RuntimeException('يجب إضافة على الأقل بنداً صالحاً.');
        }

        $this->assertSupplierBelongsToTenant($tenantUserId, (int) $header['supplier_id']);

        foreach ($normalizedLines as $line) {
            $this->assertItemBelongsToTenant($tenantUserId, $line['item_id']);
            $this->assertWarehouseBelongsToTenant($tenantUserId, $line['warehouse_id']);
        }

        return DB::transaction(function () use ($tenantUserId, $header, $normalizedLines): StockIn {
            $stockIn = StockIn::query()->create([
                'user_id' => $tenantUserId,
                'document_number' => null,
                'supplier_id' => $header['supplier_id'],
                'settlement_type' => $header['settlement_type'],
                'reference' => $header['reference'] ?? null,
                'date' => $header['date'],
                'notes' => $header['notes'] ?? null,
            ]);

            $stockIn->update([
                'document_number' => 'STIN-'.str_pad((string) $stockIn->id, 6, '0', STR_PAD_LEFT),
            ]);
            $stockIn->refresh();

            $touchedItemIds = collect();

            foreach ($normalizedLines as $line) {
                $stockLine = StockInLine::query()->create([
                    'stock_in_id' => $stockIn->id,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'quantity' => $line['quantity'],
                    'purchase_price' => $line['purchase_price'],
                ]);

                InventoryTransaction::query()->create([
                    'user_id' => $tenantUserId,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'quantity' => $line['quantity'],
                    'type' => 'stock_in',
                    'reference_id' => $stockLine->id,
                    'reference_type' => 'stock_in_lines',
                    'notes' => 'إذن إضافة مخزني '.$stockIn->document_number,
                ]);

                $pivot = ItemWarehouse::query()->firstOrCreate(
                    [
                        'user_id' => $tenantUserId,
                        'item_id' => $line['item_id'],
                        'warehouse_id' => $line['warehouse_id'],
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                    ]
                );

                $oldQty = (float) $pivot->quantity;
                $qty = $line['quantity'];
                $unitCost = $line['purchase_price'];

                $item = Item::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($line['item_id'])
                    ->firstOrFail();

                $oldCost = (float) ($item->cost ?? 0);

                $pivot->quantity = $oldQty + $qty;
                $pivot->save();

                if ($oldQty + $qty > 0 && $unitCost >= 0) {
                    $newAvgCost = ($oldQty * $oldCost + $qty * $unitCost) / ($oldQty + $qty);
                    Item::query()
                        ->withoutGlobalScopes()
                        ->where('user_id', $tenantUserId)
                        ->whereKey($line['item_id'])
                        ->update(['cost' => round($newAvgCost, 4)]);
                }

                $touchedItemIds->push($line['item_id']);
            }

            foreach ($touchedItemIds->unique() as $itemId) {
                $sum = ItemWarehouse::query()
                    ->withoutGlobalScopes()
                    ->where('item_id', $itemId)
                    ->where('user_id', $tenantUserId)
                    ->sum(DB::raw('quantity - reserved_quantity'));

                Item::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($itemId)
                    ->update(['current_stock' => $sum]);
            }

            $grandTotal = round(
                (float) $normalizedLines->sum(fn (array $line) => $line['quantity'] * $line['purchase_price']),
                4
            );

            if ($grandTotal > 0) {
                $this->postAccountingEntry($tenantUserId, $stockIn, $header, $grandTotal);
            }

            return $stockIn->load(['supplier', 'lines.item', 'lines.warehouse']);
        });
    }

    public function findReceiptForTenant(int $tenantUserId, int $stockInId): StockIn
    {
        $stockIn = StockIn::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($stockInId)
            ->with(['supplier', 'lines.item', 'lines.warehouse'])
            ->first();

        if ($stockIn === null) {
            throw new RuntimeException('إذن الإضافة المخزني غير موجود أو لا ينتمي لهذا المستأجر.');
        }

        return $stockIn;
    }

    /**
     * @return array<string, mixed>
     */
    public function createFormOptions(int $tenantUserId): array
    {
        return [
            'suppliers' => Supplier::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Supplier $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ])
                ->values()
                ->all(),
            'warehouses' => Warehouse::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->active()
                ->orderBy('name_ar')
                ->get(['id', 'code', 'name_ar', 'name_en'])
                ->map(fn (Warehouse $w) => [
                    'id' => $w->id,
                    'code' => $w->code,
                    'name_ar' => $w->name_ar,
                    'name_en' => $w->name_en,
                ])
                ->values()
                ->all(),
            'items' => Item::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->active()
                ->orderBy('code')
                ->get(['id', 'code', 'name_ar', 'name_en'])
                ->map(fn (Item $i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'name_ar' => $i->name_ar,
                    'name_en' => $i->name_en,
                ])
                ->values()
                ->all(),
            'settlement_types' => [
                ['value' => 'on_account', 'label_ar' => 'على الحساب'],
                ['value' => 'cash', 'label_ar' => 'نقدي'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiDetail(StockIn $stockIn): array
    {
        $lineTotal = $stockIn->lines->sum(
            fn ($l) => (float) $l->quantity * (float) $l->purchase_price
        );

        return [
            'id' => $stockIn->id,
            'document_number' => $stockIn->document_number,
            'date' => $stockIn->date?->format('Y-m-d'),
            'settlement_type' => $stockIn->settlement_type,
            'reference' => $stockIn->reference,
            'notes' => $stockIn->notes,
            'line_value_total' => round((float) $lineTotal, 4),
            'supplier' => $stockIn->supplier ? [
                'id' => $stockIn->supplier->id,
                'name' => $stockIn->supplier->name,
                'code' => $stockIn->supplier->code,
            ] : null,
            'lines' => $stockIn->lines->map(fn (StockInLine $line) => [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'item_code' => $line->item?->code,
                'item_name' => $line->item?->name_ar ?? $line->item?->name_en,
                'warehouse_id' => $line->warehouse_id,
                'warehouse_name' => $line->warehouse?->name_ar ?? $line->warehouse?->name_en,
                'quantity' => (float) $line->quantity,
                'purchase_price' => (float) $line->purchase_price,
                'line_total' => round((float) $line->quantity * (float) $line->purchase_price, 4),
            ])->values()->all(),
        ];
    }

    public function resolveTenantUserId(?\App\Models\User $user = null): int
    {
        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId !== null) {
            return $tenantUserId;
        }

        if ($this->tenantContext->isPlatformOperator($user) && auth()->check()) {
            return (int) auth()->id();
        }

        throw new RuntimeException('تعذّر تحديد المستأجر لهذه العملية.');
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function postAccountingEntry(int $tenantUserId, StockIn $stockIn, array $header, float $grandTotal): void
    {
        $inventoryAccount = DefaultLedgerAccounts::inventoryReceipts();
        $creditAccount = $header['settlement_type'] === 'cash'
            ? DefaultLedgerAccounts::cashOnHand()
            : DefaultLedgerAccounts::accountsPayable();

        $supplier = Supplier::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($header['supplier_id'])
            ->first();

        $supplierLabel = $supplier?->getLocalizedDisplayName() ?? (string) $header['supplier_id'];

        $entry = JournalEntry::query()->create([
            'user_id' => $tenantUserId,
            'date' => $header['date'],
            'reference' => $stockIn->document_number,
            'description' => 'إذن إضافة مخزني — مورد: '.$supplierLabel,
            'total' => $grandTotal,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $inventoryAccount->id,
            'description' => 'زيادة مخزون (توريد)',
            'debit' => $grandTotal,
            'credit' => 0,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => $header['settlement_type'] === 'cash'
                ? 'صرف نقدي لشراء مخزون'
                : 'ذمة مورد — توريد مخزون',
            'debit' => 0,
            'credit' => $grandTotal,
        ]);
    }

    private function assertItemBelongsToTenant(int $tenantUserId, int $itemId): void
    {
        $exists = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($itemId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('الصنف غير موجود أو لا ينتمي لهذا المستأجر.');
        }
    }

    private function assertWarehouseBelongsToTenant(int $tenantUserId, int $warehouseId): void
    {
        $exists = Warehouse::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($warehouseId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('المستودع غير موجود أو لا ينتمي لهذا المستأجر.');
        }
    }

    private function assertSupplierBelongsToTenant(int $tenantUserId, int $supplierId): void
    {
        $exists = Supplier::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($supplierId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('المورد غير موجود أو لا ينتمي لهذا المستأجر.');
        }
    }
}
