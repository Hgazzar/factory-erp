<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Events\Invoicing\InvoicePosted;
use App\Models\CompanySetting;
use App\Models\CrmActivity;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Support\Invoicing\InvoiceOrderLinkGuard;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ترحيل فاتورة المبيعات: مخزون + COGS + قيد إيراد + نشاط CRM.
 */
final class SalesInvoicePostingService
{
    public function __construct(
        private readonly SalesInventoryService $inventory,
        private readonly SalesAccountingService $accounting,
    ) {}

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array{item_id: int, quantity: float, unit_price: float, discount_percent?: float, tax_percent?: float|null, vat_percent?: float|null, description?: string|null}>  $lines
     */
    public function createAndPost(int $tenantUserId, array $header, array $lines): SalesInvoice
    {
        $normalized = $this->normalizeLines($tenantUserId, $lines);
        if ($normalized === []) {
            throw new RuntimeException('يجب إضافة على الأقل بنداً صالحاً.');
        }

        $totals = $this->calculateTotals($normalized);
        $warehouseId = (int) $header['warehouse_id'];

        foreach ($normalized as $line) {
            $this->inventory->assertStockAvailable(
                $tenantUserId,
                $warehouseId,
                (int) $line['item_id'],
                (float) $line['quantity'],
            );
        }

        return DB::transaction(function () use ($tenantUserId, $header, $normalized, $totals): SalesInvoice {
            $paymentMethod = (string) ($header['payment_method'] ?? SalesInvoice::PAYMENT_CREDIT);

            $invoiceAttributes = [
                'user_id' => $tenantUserId,
                'customer_id' => (int) $header['customer_id'],
                'warehouse_id' => (int) $header['warehouse_id'],
                'date' => $header['date'],
                'due_date' => $header['due_date'],
                'reference' => $header['reference'] ?? null,
                'payment_method' => $paymentMethod,
                'subtotal' => $totals['subtotal'],
                'vat_rate' => $totals['avg_vat_rate'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['grand_total'],
                'paid_amount' => 0,
                'status' => SalesInvoice::STATUS_DRAFT,
                'invoice_status' => 'draft',
            ];

            if (! empty($header['quotation_id'])) {
                $invoiceAttributes['quotation_id'] = (int) $header['quotation_id'];
            }

            if (! empty($header['sales_order_id'])) {
                $invoiceAttributes['sales_order_id'] = (int) $header['sales_order_id'];
            }

            $invoiceAttributes['posting_source'] = (string) (
                $header['posting_source']
                ?? (! empty($header['sales_order_id'])
                    ? SalesInvoice::POSTING_SOURCE_ORDER
                    : SalesInvoice::POSTING_SOURCE_DIRECT)
            );

            foreach (['notes', 'internal_notes', 'terms'] as $optionalTextField) {
                if (! empty($header[$optionalTextField])) {
                    $invoiceAttributes[$optionalTextField] = $header[$optionalTextField];
                }
            }

            $invoice = SalesInvoice::query()->create($invoiceAttributes);

            foreach ($normalized as $line) {
                SalesInvoiceItem::query()->create([
                    'user_id' => $tenantUserId,
                    'sales_invoice_id' => $invoice->id,
                    ...$line,
                ]);
            }

            return $this->postExisting($invoice->fresh(['items.item', 'warehouse', 'customer']));
        });
    }

    public function postExisting(SalesInvoice $invoice): SalesInvoice
    {
        if ($invoice->isPosted()) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice): SalesInvoice {
            /** @var SalesInvoice $locked */
            $locked = SalesInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->loadMissing(['items.item', 'warehouse', 'customer']);

            if ($locked->isPosted()) {
                return $locked;
            }

            if ($locked->items->isEmpty()) {
                throw new RuntimeException('لا يمكن ترحيل فاتورة بدون بنود.');
            }

            InvoiceOrderLinkGuard::assertSalesInvoiceMayPost($locked);

            $tenantUserId = (int) $locked->user_id;

            $this->inventory->postInvoice($locked, $tenantUserId);
            $revenueJournalId = $this->accounting->postRevenueJournal($locked);
            $cogsJournalId = $this->accounting->postCogsJournal($locked->fresh(['items.item']));

            $now = now();
            $locked->forceFill([
                'journal_entry_id' => $revenueJournalId,
                'cogs_journal_entry_id' => $cogsJournalId,
                'posted_at' => $now,
                'inventory_posted_at' => $now,
                'invoice_status' => 'issued',
            ]);

            if ($locked->isCashSale()) {
                $locked->paid_amount = (float) $locked->total;
            }

            $locked->refreshPaymentStatus();
            $locked->save();

            $this->recordCrmActivity($locked, $tenantUserId);

            $result = $locked->fresh(['items.item', 'warehouse', 'customer', 'journalEntry', 'cogsJournalEntry']);

            DB::afterCommit(function () use ($result): void {
                event(new InvoicePosted($result, InvoicePosted::TYPE_SALES));
            });

            return $result;
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
            ->map(function (array $line) use ($defaultVat): array {
                $qty = (float) ($line['quantity'] ?? 0);
                $price = (float) ($line['unit_price'] ?? 0);
                if (array_key_exists('discount', $line) && ! array_key_exists('discount_percent', $line)) {
                    $discount = round(max(0, (float) $line['discount']), 4);
                } else {
                    $discountPercent = (float) ($line['discount_percent'] ?? 0);
                    $discount = round($qty * $price * $discountPercent / 100, 4);
                }
                $vatPercent = array_key_exists('vat_percent', $line)
                    ? ($line['vat_percent'] !== null ? (float) $line['vat_percent'] : $defaultVat)
                    : (array_key_exists('tax_percent', $line)
                        ? ($line['tax_percent'] !== null ? (float) $line['tax_percent'] : $defaultVat)
                        : $defaultVat);
                $lineNet = max(0, $qty * $price - $discount);
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
        $subtotal = 0.0;
        $vatAmount = 0.0;

        foreach ($lines as $line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $discount = (float) ($line['discount'] ?? 0);
            $vatPercent = (float) ($line['vat_percent'] ?? 0);
            $lineNet = max(0, $qty * $price - $discount);
            $subtotal += $lineNet;
            $vatAmount += $lineNet * $vatPercent / 100;
        }

        $netAfterDiscount = $subtotal;
        $grandTotal = $netAfterDiscount + $vatAmount;
        $avgVatRate = $netAfterDiscount > 0 ? ($vatAmount / $netAfterDiscount * 100) : 0;

        return [
            'subtotal' => round($subtotal, 4),
            'vat_amount' => round($vatAmount, 4),
            'grand_total' => round($grandTotal, 4),
            'avg_vat_rate' => round($avgVatRate, 2),
        ];
    }

    private function recordCrmActivity(SalesInvoice $invoice, int $tenantUserId): void
    {
        if (! $invoice->customer_id) {
            return;
        }

        $ref = $invoice->reference ?: ('SINV-'.$invoice->id);
        $total = number_format((float) $invoice->total, 2, '.', '');

        CrmActivity::query()->create([
            'customer_id' => $invoice->customer_id,
            'sales_invoice_id' => $invoice->id,
            'user_id' => (int) (auth()->id() ?? $tenantUserId),
            'type' => CrmActivity::TYPE_SALES_INVOICE,
            'note' => "ترحيل فاتورة مبيعات {$ref} — إجمالي {$total}",
            'result' => 'posted',
        ]);
    }
}
