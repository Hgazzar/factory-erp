<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Events\Invoicing\InvoicePosted;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Support\Invoicing\InvoiceOrderLinkGuard;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ترحيل فاتورة المشتريات: مخزون + متوسط تكلفة + قيد محاسبي (مدين مخزون / دائن مورد).
 */
final class PurchaseInvoicePostingService
{
    public function __construct(
        private readonly PurchaseInventoryService $inventory,
        private readonly PurchaseAccountingService $accounting,
    ) {}

    /**
     * ينشئ الفاتورة ويرحّلها (مخزون + GL) في معاملة واحدة.
     *
     * @param  array<string, mixed>  $header
     * @param  list<array{item_id: int, quantity: float, unit_price: float, discount?: float, vat_percent?: float, description?: string|null}>  $lines
     */
    public function createAndPost(int $tenantUserId, array $header, array $lines): PurchaseInvoice
    {
        $normalized = $this->normalizeLines($lines);
        if ($normalized === []) {
            throw new RuntimeException('يجب إضافة على الأقل بنداً صالحاً.');
        }

        $totals = $this->calculateTotals($normalized);

        return DB::transaction(function () use ($tenantUserId, $header, $normalized, $totals): PurchaseInvoice {
            $invoice = PurchaseInvoice::query()->create([
                'user_id' => $tenantUserId,
                'supplier_id' => (int) $header['supplier_id'],
                'purchase_order_id' => ! empty($header['purchase_order_id']) ? (int) $header['purchase_order_id'] : null,
                'posting_source' => (string) (
                    $header['posting_source']
                    ?? (! empty($header['purchase_order_id'])
                        ? PurchaseInvoice::POSTING_SOURCE_ORDER
                        : PurchaseInvoice::POSTING_SOURCE_DIRECT)
                ),
                'warehouse_id' => (int) $header['warehouse_id'],
                'date' => $header['date'],
                'due_date' => $header['due_date'],
                'reference' => $header['reference'] ?? null,
                'supplier_invoice_number' => $header['supplier_invoice_number'] ?? null,
                'currency' => $header['currency'] ?? 'SAR',
                'subtotal' => $totals['subtotal'],
                'vat_rate' => $totals['avg_vat_rate'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['grand_total'],
                'paid_amount' => 0,
                'status' => PurchaseInvoice::STATUS_DRAFT,
                'notes' => $header['notes'] ?? null,
                'internal_notes' => $header['internal_notes'] ?? null,
            ]);

            foreach ($normalized as $line) {
                PurchaseInvoiceItem::query()->create([
                    'user_id' => $tenantUserId,
                    'purchase_invoice_id' => $invoice->id,
                    ...$line,
                ]);
            }

            return $this->postExisting($invoice->fresh(['items.item', 'warehouse', 'supplier']));
        });
    }

    /**
     * ترحيل فاتورة موجودة (مسودة → غير مدفوعة).
     */
    public function postExisting(PurchaseInvoice $invoice): PurchaseInvoice
    {
        if ($invoice->isPosted()) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice): PurchaseInvoice {
            /** @var PurchaseInvoice $locked */
            $locked = PurchaseInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->loadMissing(['items.item', 'warehouse', 'supplier']);

            if ($locked->isPosted()) {
                return $locked;
            }

            if ($locked->items->isEmpty()) {
                throw new RuntimeException('لا يمكن ترحيل فاتورة بدون بنود.');
            }

            InvoiceOrderLinkGuard::assertPurchaseInvoiceMayPost($locked);

            $tenantUserId = (int) $locked->user_id;

            $this->inventory->postInvoice($locked, $tenantUserId);
            $journalId = $this->accounting->postPurchaseInvoice($locked);

            $now = now();
            $locked->forceFill([
                'journal_entry_id' => $journalId,
                'posted_at' => $now,
                'inventory_posted_at' => $now,
                'status' => PurchaseInvoice::STATUS_UNPAID,
            ]);
            $locked->refreshPaymentStatus();
            $locked->save();

            $result = $locked->fresh(['items.item', 'warehouse', 'supplier', 'journalEntry']);

            DB::afterCommit(function () use ($result): void {
                event(new InvoicePosted($result, InvoicePosted::TYPE_PURCHASE));
            });

            return $result;
        });
    }

    public function weightedAverageUnitCost(Item $item, float $incomingQty, float $incomingUnitCost): float
    {
        return $this->inventory->weightedAverageUnitCost($item, $incomingQty, $incomingUnitCost);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function normalizeLines(array $lines): array
    {
        return collect($lines)
            ->map(function (array $line): array {
                $qty = (float) ($line['quantity'] ?? 0);
                $price = (float) ($line['unit_price'] ?? 0);
                $discount = (float) ($line['discount'] ?? 0);
                $vatPercent = (float) ($line['vat_percent'] ?? 15);
                $lineNet = $qty * $price - $discount;
                $lineVat = $lineNet * $vatPercent / 100;

                return [
                    'item_id' => (int) $line['item_id'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $discount,
                    'vat_percent' => $vatPercent,
                    'line_total' => round($lineNet + $lineVat, 4),
                ];
            })
            ->filter(fn (array $l) => $l['quantity'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{subtotal: float, vat_amount: float, grand_total: float, avg_vat_rate: float}
     */
    public function calculateTotals(array $lines): array
    {
        $subtotal = collect($lines)->sum(fn (array $l) => (float) $l['quantity'] * (float) $l['unit_price']);
        $totalDiscount = collect($lines)->sum(fn (array $l) => (float) ($l['discount'] ?? 0));
        $netAfterDiscount = $subtotal - $totalDiscount;
        $vatAmount = collect($lines)->sum(function (array $l): float {
            $net = (float) $l['quantity'] * (float) $l['unit_price'] - (float) ($l['discount'] ?? 0);

            return $net * (float) ($l['vat_percent'] ?? 15) / 100;
        });
        $grandTotal = $netAfterDiscount + $vatAmount;
        $avgVatRate = $netAfterDiscount > 0 ? ($vatAmount / $netAfterDiscount * 100) : 0;

        return [
            'subtotal' => round($subtotal, 4),
            'vat_amount' => round($vatAmount, 4),
            'grand_total' => round($grandTotal, 4),
            'avg_vat_rate' => round($avgVatRate, 2),
        ];
    }
}
