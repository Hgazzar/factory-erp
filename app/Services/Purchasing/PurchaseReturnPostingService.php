<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * ترحيل مرتجع المشتريات: مخزون + قيد عكسي + تعديل الفاتورة الأصلية.
 */
final class PurchaseReturnPostingService
{
    public function __construct(
        private readonly PurchaseReturnInventoryService $inventory,
        private readonly PurchaseReturnAccountingService $accounting,
    ) {}

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function createAndPost(int $tenantUserId, array $header, array $lines): PurchaseReturn
    {
        $normalized = $this->normalizeLines($tenantUserId, $lines);
        if ($normalized === []) {
            throw new RuntimeException('يجب إضافة على الأقل بنداً صالحاً للمرتجع.');
        }

        $invoice = null;
        if (! empty($header['purchase_invoice_id'])) {
            $invoice = $this->resolveInvoiceForReturn(
                $tenantUserId,
                (int) $header['purchase_invoice_id'],
                (int) $header['supplier_id'],
            );
            $this->validateReturnQuantitiesAgainstInvoice($invoice, $normalized);
        }

        $totals = $this->calculateTotals($normalized, $tenantUserId);

        return DB::transaction(function () use ($tenantUserId, $header, $normalized, $totals, $invoice): PurchaseReturn {
            $purchaseReturn = PurchaseReturn::query()->create([
                'user_id' => $tenantUserId,
                'code' => null,
                'date' => $header['date'],
                'supplier_id' => (int) $header['supplier_id'],
                'purchase_invoice_id' => $invoice?->id,
                'warehouse_id' => (int) $header['warehouse_id'],
                'reason_type' => $header['reason_type'],
                'reason' => $header['reason'] ?? null,
                'reference' => $header['reference'] ?? null,
                'notes' => $header['notes'] ?? null,
                'internal_notes' => $header['internal_notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['grand_total'],
                'currency' => $header['currency'] ?? 'SAR',
                'status' => PurchaseReturn::STATUS_PENDING,
            ]);

            foreach ($normalized as $line) {
                PurchaseReturnItem::query()->create([
                    'user_id' => $tenantUserId,
                    'purchase_return_id' => $purchaseReturn->id,
                    ...$line,
                ]);
            }

            return $this->postExisting($purchaseReturn->fresh(['items.item', 'warehouse', 'supplier', 'purchaseInvoice']));
        });
    }

    public function postExisting(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if ($purchaseReturn->isPosted()) {
            return $purchaseReturn;
        }

        return DB::transaction(function () use ($purchaseReturn): PurchaseReturn {
            /** @var PurchaseReturn $locked */
            $locked = PurchaseReturn::query()
                ->whereKey($purchaseReturn->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->loadMissing(['items.item', 'warehouse', 'supplier', 'purchaseInvoice']);

            if ($locked->isPosted()) {
                return $locked;
            }

            if ($locked->items->isEmpty()) {
                throw new RuntimeException('لا يمكن ترحيل مرتجع بدون بنود.');
            }

            $tenantUserId = (int) $locked->user_id;

            $this->inventory->postReturn($locked, $tenantUserId);
            $journalId = $this->accounting->postPurchaseReturn($locked);

            if ($locked->purchase_invoice_id) {
                $this->adjustLinkedInvoice($locked);
            }

            $now = now();
            $locked->forceFill([
                'journal_entry_id' => $journalId,
                'posted_at' => $now,
                'inventory_posted_at' => $now,
                'status' => PurchaseReturn::STATUS_COMPLETED,
                'code' => $locked->code ?: ('PR-'.$locked->id),
            ]);
            $locked->save();

            return $locked->fresh(['items.item', 'warehouse', 'supplier', 'purchaseInvoice', 'journalEntry']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function normalizeLines(int $tenantUserId, array $lines): array
    {
        $defaultVat = CompanySetting::resolvedDefaultVatPercent($tenantUserId);

        return collect($lines)
            ->map(function (array $line) use ($tenantUserId, $defaultVat): array {
                $qty = (float) ($line['quantity'] ?? 0);
                $price = (float) ($line['unit_price'] ?? 0);
                $discount = (float) ($line['discount'] ?? 0);
                $vatPercent = isset($line['vat_percent']) && $line['vat_percent'] !== ''
                    ? (float) $line['vat_percent']
                    : $defaultVat;
                $lineNet = max(0, $qty * $price - $discount);
                $lineVat = $lineNet * $vatPercent / 100;

                $unitCost = isset($line['unit_cost']) ? (float) $line['unit_cost'] : null;
                if ($unitCost === null && ! empty($line['purchase_invoice_item_id'])) {
                    $invLine = PurchaseInvoiceItem::withoutGlobalScopes()->find($line['purchase_invoice_item_id']);
                    $unitCost = $invLine?->weighted_unit_cost !== null
                        ? (float) $invLine->weighted_unit_cost
                        : (float) ($invLine?->unit_price ?? 0);
                }
                if ($unitCost === null && ! empty($line['item_id'])) {
                    $unitCost = (float) (Item::withoutGlobalScopes()->whereKey($line['item_id'])->value('cost') ?? 0);
                }

                return [
                    'purchase_invoice_item_id' => isset($line['purchase_invoice_item_id']) ? (int) $line['purchase_invoice_item_id'] : null,
                    'item_id' => (int) $line['item_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $discount,
                    'vat_percent' => $vatPercent,
                    'unit_cost' => $unitCost,
                    'line_status' => $line['line_status'] ?? null,
                    'reason' => $line['reason'] ?? null,
                    'line_total' => round($lineNet + $lineVat, 4),
                ];
            })
            ->filter(fn (array $l) => $l['quantity'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{subtotal: float, vat_amount: float, grand_total: float}
     */
    public function calculateTotals(array $lines, int $tenantUserId): array
    {
        $subtotal = collect($lines)->sum(fn (array $l) => max(0, (float) $l['quantity'] * (float) $l['unit_price'] - (float) ($l['discount'] ?? 0)));
        $vatAmount = collect($lines)->sum(function (array $l) use ($tenantUserId): float {
            $net = max(0, (float) $l['quantity'] * (float) $l['unit_price'] - (float) ($l['discount'] ?? 0));
            $vat = isset($l['vat_percent']) ? (float) $l['vat_percent'] : CompanySetting::resolvedDefaultVatPercent($tenantUserId);

            return $net * $vat / 100;
        });

        return [
            'subtotal' => round($subtotal, 4),
            'vat_amount' => round($vatAmount, 4),
            'grand_total' => round($subtotal + $vatAmount, 4),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function validateReturnQuantitiesAgainstInvoice(PurchaseInvoice $invoice, array $lines): void
    {
        $invoice->loadMissing('items');

        foreach ($lines as $line) {
            $itemId = (int) $line['item_id'];
            $qty = (float) $line['quantity'];

            $invoiceLine = $invoice->items->firstWhere('item_id', $itemId);
            if (! $invoiceLine) {
                throw new InvalidArgumentException('الصنف #'.$itemId.' غير موجود في فاتورة المشتريات المرتبطة.');
            }

            $alreadyReturned = (float) PurchaseReturnItem::query()
                ->whereHas('purchaseReturn', fn ($q) => $q
                    ->where('purchase_invoice_id', $invoice->id)
                    ->whereNotNull('posted_at'))
                ->where('item_id', $itemId)
                ->sum('quantity');

            $maxReturnable = max(0, (float) $invoiceLine->quantity - $alreadyReturned);
            if ($qty > $maxReturnable + 0.0001) {
                throw new InvalidArgumentException(
                    'كمية المرتجع للصنف #'.$itemId.' تتجاوز المتاح ('.rtrim(rtrim(number_format($maxReturnable, 4, '.', ''), '0'), '.').').'
                );
            }
        }
    }

    private function resolveInvoiceForReturn(int $tenantUserId, int $invoiceId, int $supplierId): PurchaseInvoice
    {
        /** @var PurchaseInvoice $invoice */
        $invoice = PurchaseInvoice::query()
            ->whereKey($invoiceId)
            ->where('user_id', $tenantUserId)
            ->firstOrFail();

        if ((int) $invoice->supplier_id !== $supplierId) {
            throw new InvalidArgumentException('فاتورة المشتريات لا تخص المورد المحدد.');
        }

        if (! $invoice->isPosted()) {
            throw new InvalidArgumentException('لا يمكن ربط المرتجع بفاتورة غير مرحّلة.');
        }

        return $invoice;
    }

    private function adjustLinkedInvoice(PurchaseReturn $purchaseReturn): void
    {
        /** @var PurchaseInvoice|null $invoice */
        $invoice = PurchaseInvoice::query()
            ->whereKey($purchaseReturn->purchase_invoice_id)
            ->lockForUpdate()
            ->first();

        if (! $invoice) {
            return;
        }

        $returnTotal = round((float) $purchaseReturn->total, 4);
        $returnVat = round((float) $purchaseReturn->vat_amount, 4);
        $returnSubtotal = round((float) $purchaseReturn->subtotal, 4);

        $invoice->subtotal = max(0, round((float) ($invoice->subtotal ?? 0) - $returnSubtotal, 4));
        $invoice->vat_amount = max(0, round((float) ($invoice->vat_amount ?? 0) - $returnVat, 4));
        $invoice->total = max(0, round((float) $invoice->total - $returnTotal, 4));

        if ((float) ($invoice->paid_amount ?? 0) > (float) $invoice->total) {
            $invoice->paid_amount = (float) $invoice->total;
        }

        $invoice->refreshPaymentStatus();
        $invoice->save();
    }

    /**
     * @return array{available: float, item_id: int, purchase_invoice_item_id: int|null}
     */
    public function maxReturnableForInvoiceLine(PurchaseInvoice $invoice, int $itemId): array
    {
        $invoiceLine = $invoice->items()->where('item_id', $itemId)->first();
        if (! $invoiceLine) {
            return ['available' => 0.0, 'item_id' => $itemId, 'purchase_invoice_item_id' => null];
        }

        $alreadyReturned = (float) PurchaseReturnItem::query()
            ->whereHas('purchaseReturn', fn ($q) => $q
                ->where('purchase_invoice_id', $invoice->id)
                ->whereNotNull('posted_at'))
            ->where('item_id', $itemId)
            ->sum('quantity');

        return [
            'available' => max(0, (float) $invoiceLine->quantity - $alreadyReturned),
            'item_id' => $itemId,
            'purchase_invoice_item_id' => (int) $invoiceLine->id,
        ];
    }
}
