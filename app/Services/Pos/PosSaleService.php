<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\AuditLog;
use App\Models\PosDevice;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Services\FinancialRecordingService;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class PosSaleService
{
    public function __construct(
        private readonly FinancialRecordingService $financialRecording,
    ) {}

    /**
     * @param  array{
     *   pos_device_id:int,
     *   pos_session_id?:int|null,
     *   payment_method?:string|null,
     *   payment_splits?:list<array{method:string, amount:float|int|string}>,
     *   sale_channel?:string|null,
     *   customer_name?:string|null,
     *   customer_phone?:string|null,
     *   customer_address?:string|null,
     *   sold_at?:string|null,
     *   lines:list<array{pos_product_id:int, quantity:float|int|string, unit_price?:float|int|string|null}>
     * }  $payload
     */
    public function processSale(int $tenantUserId, array $payload, ?int $actingUserId = null): PosSale
    {
        $lines = $payload['lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            throw new InvalidArgumentException('لا يمكن إتمام البيع بدون بنود.');
        }

        $paymentMethod = (string) ($payload['payment_method'] ?? PosSale::PAYMENT_CASH);
        $allowedMethods = [
            PosSale::PAYMENT_CASH,
            PosSale::PAYMENT_BANK,
            PosSale::PAYMENT_CARD,
            PosSale::PAYMENT_MIXED,
            PosSale::PAYMENT_COD,
        ];
        $paymentMethod = in_array($paymentMethod, $allowedMethods, true)
            ? $paymentMethod
            : PosSale::PAYMENT_CASH;

        $paymentSplits = $this->normalizePaymentSplits($payload['payment_splits'] ?? []);

        $soldAt = isset($payload['sold_at']) && trim((string) $payload['sold_at']) !== ''
            ? (string) $payload['sold_at']
            : now()->toDateString();

        return DB::transaction(function () use ($tenantUserId, $payload, $lines, $paymentMethod, $paymentSplits, $soldAt, $actingUserId): PosSale {
            $deviceId = (int) ($payload['pos_device_id'] ?? 0);
            if ($deviceId < 1) {
                throw new InvalidArgumentException('جهاز نقطة البيع مطلوب.');
            }

            $deviceExists = PosDevice::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($deviceId)
                ->exists();
            if (! $deviceExists) {
                throw new InvalidArgumentException('جهاز نقطة البيع غير صالح لهذا المستأجر.');
            }

            $preparedLines = [];
            $subtotal = 0.0;
            $vatTotal = 0.0;
            $cogsTotal = 0.0;

            foreach ($lines as $line) {
                $productId = (int) ($line['pos_product_id'] ?? 0);
                $qty = round((float) ($line['quantity'] ?? 0), 4);
                if ($productId < 1 || $qty <= 0) {
                    throw new InvalidArgumentException('بيانات بند البيع غير صالحة.');
                }

                /** @var PosProduct $product */
                $product = PosProduct::withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($productId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $product->is_active) {
                    throw new InvalidArgumentException("الصنف {$product->name} غير نشط.");
                }

                if ((float) $product->current_quantity + 0.0001 < $qty) {
                    throw new RuntimeException("الكمية غير كافية للصنف {$product->name}.");
                }

                $unitPrice = array_key_exists('unit_price', $line) && $line['unit_price'] !== null
                    ? round((float) $line['unit_price'], 4)
                    : round((float) $product->sale_price, 4);

                if ($unitPrice < 0) {
                    throw new InvalidArgumentException('سعر البيع لا يمكن أن يكون سالباً.');
                }

                $lineSubtotal = round($qty * $unitPrice, 4);
                $lineVat = round($lineSubtotal * ((float) $product->vat_percent / 100), 4);
                $lineTotal = round($lineSubtotal + $lineVat, 4);
                $lineCogs = round($qty * (float) $product->cost_price, 4);

                $preparedLines[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $lineSubtotal,
                    'line_vat' => $lineVat,
                    'line_total' => $lineTotal,
                    'line_cogs' => $lineCogs,
                ];

                $subtotal += $lineSubtotal;
                $vatTotal += $lineVat;
                $cogsTotal += $lineCogs;
            }

            $subtotal = round($subtotal, 4);
            $vatTotal = round($vatTotal, 4);
            $totalAmount = round($subtotal + $vatTotal, 4);
            $cogsTotal = round($cogsTotal, 4);

            if ($paymentMethod === PosSale::PAYMENT_MIXED) {
                $paymentSplits = $this->validateMixedPaymentSplits($paymentSplits, $totalAmount);
            }

            $sale = PosSale::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'pos_device_id' => $deviceId,
                'pos_session_id' => isset($payload['pos_session_id']) ? (int) $payload['pos_session_id'] : null,
                'receipt_number' => PosSale::nextReceiptNumber($tenantUserId),
                'invoice_number' => PosSale::nextInvoiceNumber($tenantUserId),
                'total_price' => $totalAmount,
                'subtotal_amount' => $subtotal,
                'vat_amount' => $vatTotal,
                'total_amount' => $totalAmount,
                'cogs_amount' => $cogsTotal,
                'payment_method' => $paymentMethod,
                'sale_channel' => isset($payload['sale_channel']) ? trim((string) $payload['sale_channel']) : null,
                'customer_name' => isset($payload['customer_name']) ? trim((string) $payload['customer_name']) : null,
                'customer_phone' => isset($payload['customer_phone']) ? trim((string) $payload['customer_phone']) : null,
                'customer_address' => isset($payload['customer_address']) ? trim((string) $payload['customer_address']) : null,
                'coupon_code' => isset($payload['coupon_code']) ? trim((string) $payload['coupon_code']) : null,
                'discount_amount' => round((float) ($payload['discount_amount'] ?? 0), 4),
                'status' => PosSale::STATUS_COMPLETED,
                'created_at' => $soldAt,
                'updated_at' => now(),
            ]);

            foreach ($preparedLines as $entry) {
                /** @var PosProduct $product */
                $product = $entry['product'];

                PosSaleItem::query()->create([
                    'pos_sale_id' => $sale->id,
                    'pos_product_id' => $product->id,
                    'quantity' => $entry['quantity'],
                    'unit_cost' => (float) $product->cost_price,
                    'unit_price' => $entry['unit_price'],
                    'vat_percent' => (float) $product->vat_percent,
                    'vat_amount' => $entry['line_vat'],
                    'line_subtotal' => $entry['line_subtotal'],
                    'line_total' => $entry['line_total'],
                ]);

                $product->current_quantity = round((float) $product->current_quantity - (float) $entry['quantity'], 4);
                $product->save();
            }

            $entry = $this->recordAccountingEntry(
                $sale,
                $tenantUserId,
                $subtotal,
                $vatTotal,
                $totalAmount,
                $cogsTotal,
                $paymentMethod,
                $actingUserId,
                $paymentSplits,
            );

            $sale->journal_entry_id = $entry->id;
            $sale->save();

            return $sale->fresh(['items.product', 'journalEntry']);
        });
    }

    public function updateProductPrice(PosProduct $product, float $newSalePrice, ?string $reason = null): PosProduct
    {
        $old = (float) $product->sale_price;
        $newSalePrice = round($newSalePrice, 4);
        if ($newSalePrice < 0) {
            throw new InvalidArgumentException('السعر الجديد غير صالح.');
        }

        $product->sale_price = $newSalePrice;
        $product->save();

        AuditLog::logModuleEvent('pos_product_price_changed', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'old_sale_price' => $old,
            'new_sale_price' => $newSalePrice,
            'reason' => $reason,
        ], $product);

        return $product->fresh();
    }

    public function adjustStock(PosProduct $product, float $deltaQty, ?string $reason = null): PosProduct
    {
        $deltaQty = round($deltaQty, 4);
        if ($deltaQty === 0.0) {
            return $product;
        }

        $before = (float) $product->current_quantity;
        $after = round($before + $deltaQty, 4);
        if ($after < 0) {
            throw new InvalidArgumentException('لا يمكن تعديل المخزون إلى قيمة سالبة.');
        }

        $product->current_quantity = $after;
        $product->save();

        AuditLog::logModuleEvent('pos_product_stock_adjusted', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'before_quantity' => $before,
            'delta_quantity' => $deltaQty,
            'after_quantity' => $after,
            'reason' => $reason,
        ], $product);

        return $product->fresh();
    }

    /**
     * @param  list<array{method:string, amount:float}>  $paymentSplits
     */
    private function recordAccountingEntry(
        PosSale $sale,
        int $tenantUserId,
        float $subtotal,
        float $vatTotal,
        float $totalAmount,
        float $cogsTotal,
        string $paymentMethod,
        ?int $actingUserId,
        array $paymentSplits = [],
    ): \App\Models\JournalEntry {
        $salesRevenue = DefaultLedgerAccounts::salesRevenueForTenant($tenantUserId);
        $vatPayable = DefaultLedgerAccounts::vatPayableForTenant($tenantUserId);
        $cogs = DefaultLedgerAccounts::ensureCogsRootForTenant($tenantUserId);
        $inventory = DefaultLedgerAccounts::inventoryFinishedGoods($tenantUserId);

        $lines = [];

        if ($paymentMethod === PosSale::PAYMENT_MIXED && $paymentSplits !== []) {
            foreach ($paymentSplits as $split) {
                $asset = DefaultLedgerAccounts::paymentSourceAssetForTenant($split['method'], $tenantUserId);
                $lines[] = [
                    'account_id' => $asset->id,
                    'debit' => $split['amount'],
                    'credit' => 0,
                    'description' => 'تحصيل مبيعات POS (مقسم) — '.$sale->invoice_number,
                ];
            }
        } elseif ($paymentMethod === PosSale::PAYMENT_COD) {
            $receivable = DefaultLedgerAccounts::accountsReceivableForTenant($tenantUserId);
            $lines[] = [
                'account_id' => $receivable->id,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'مبيعات أونلاين (دفع عند الاستلام) — '.$sale->invoice_number,
            ];
        } else {
            $cashOrBank = DefaultLedgerAccounts::paymentSourceAssetForTenant($paymentMethod, $tenantUserId);
            $lines[] = [
                'account_id' => $cashOrBank->id,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'تحصيل مبيعات POS — '.$sale->invoice_number,
            ];
        }

        $lines[] = [
            'account_id' => $salesRevenue->id,
            'debit' => 0,
            'credit' => $subtotal,
            'description' => 'إيراد مبيعات POS',
        ];

        if ($vatTotal > 0.0001) {
            $lines[] = [
                'account_id' => $vatPayable->id,
                'debit' => 0,
                'credit' => $vatTotal,
                'description' => 'ضريبة مبيعات POS',
            ];
        }

        if ($cogsTotal > 0.0001) {
            $lines[] = [
                'account_id' => $cogs->id,
                'debit' => $cogsTotal,
                'credit' => 0,
                'description' => 'تكلفة البضاعة المباعة POS',
            ];
            $lines[] = [
                'account_id' => $inventory->id,
                'debit' => 0,
                'credit' => $cogsTotal,
                'description' => 'إخراج تكلفة من المخزون POS',
            ];
        }

        return $this->financialRecording->recordBalancedJournal(
            $tenantUserId,
            now()->toDateString(),
            $sale->invoice_number,
            'فاتورة POS '.$sale->invoice_number,
            $lines,
            $actingUserId ?? $tenantUserId,
        );
    }

    /**
     * @param  mixed  $raw
     * @return list<array{method:string, amount:float}>
     */
    private function normalizePaymentSplits(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $split) {
            if (! is_array($split)) {
                continue;
            }

            $method = (string) ($split['method'] ?? '');
            if (! in_array($method, [PosSale::PAYMENT_CASH, PosSale::PAYMENT_BANK, PosSale::PAYMENT_CARD], true)) {
                continue;
            }

            $amount = round((float) ($split['amount'] ?? 0), 4);
            if ($amount <= 0) {
                continue;
            }

            $normalized[] = [
                'method' => $method,
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{method:string, amount:float}>  $paymentSplits
     * @return list<array{method:string, amount:float}>
     */
    private function validateMixedPaymentSplits(array $paymentSplits, float $totalAmount): array
    {
        if ($paymentSplits === []) {
            throw new InvalidArgumentException('الدفع المقسم يتطلب تحديد مبالغ نقدي وبطاقة.');
        }

        if (count($paymentSplits) < 2) {
            throw new InvalidArgumentException('الدفع المقسم يتطلب طريقتي دفع على الأقل.');
        }

        $splitTotal = round(array_sum(array_column($paymentSplits, 'amount')), 4);
        if (abs($splitTotal - $totalAmount) > 0.02) {
            throw new InvalidArgumentException('مجموع الدفع المقسم لا يساوي إجمالي الفاتورة.');
        }

        return $paymentSplits;
    }
}
