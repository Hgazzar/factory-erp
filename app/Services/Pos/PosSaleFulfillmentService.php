<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\AuditLog;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Services\Store\StoreOrderWhatsAppNotifier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PosSaleFulfillmentService
{
    public function __construct(
        private readonly PosSaleService $posSaleService,
        private readonly StoreOrderWhatsAppNotifier $whatsappNotifier,
    ) {}

    public function markDelivered(int $tenantUserId, int $saleId, ?int $actingUserId = null): PosSale
    {
        return DB::transaction(function () use ($tenantUserId, $saleId, $actingUserId): PosSale {
            /** @var PosSale $sale */
            $sale = $this->lockOnlineCodSale($tenantUserId, $saleId);

            if ($sale->status === PosSale::STATUS_DELIVERED) {
                return $sale->fresh(['items.product', 'journalEntry']);
            }

            if ($sale->status !== PosSale::STATUS_PENDING) {
                throw new InvalidArgumentException('يمكن تأكيد التسليم للطلبات المعلّقة فقط.');
            }

            if ($sale->journal_entry_id === null) {
                $entry = $this->posSaleService->postSaleJournal(
                    $sale,
                    $tenantUserId,
                    PosSale::PAYMENT_COD,
                    $actingUserId,
                );

                $sale->forceFill(['journal_entry_id' => $entry->id])->save();
            }

            $sale->forceFill([
                'status' => PosSale::STATUS_DELIVERED,
                'delivered_at' => now(),
            ])->save();

            AuditLog::logModuleEvent('online_store_order_delivered', [
                'pos_sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'journal_entry_id' => $sale->journal_entry_id,
            ], $sale);

            $fresh = $sale->fresh(['items.product', 'journalEntry']);

            DB::afterCommit(fn () => $this->whatsappNotifier->dispatchDeliveredNotification($tenantUserId, $saleId));

            return $fresh;
        });
    }

    public function markCollected(int $tenantUserId, int $saleId, ?int $actingUserId = null): PosSale
    {
        return DB::transaction(function () use ($tenantUserId, $saleId, $actingUserId): PosSale {
            /** @var PosSale $sale */
            $sale = $this->lockOnlineCollectibleSale($tenantUserId, $saleId);

            if ($sale->status === PosSale::STATUS_COLLECTED || $sale->status === PosSale::STATUS_COMPLETED) {
                return $sale->fresh(['items.product', 'journalEntry', 'collectionJournalEntry']);
            }

            if ($sale->status === PosSale::STATUS_PENDING_VERIFICATION) {
                if ($sale->payment_receipt_path === null || trim((string) $sale->payment_receipt_path) === '') {
                    throw new InvalidArgumentException('لا يوجد إيصال تحويل مرفق لهذا الطلب.');
                }

                if ($sale->journal_entry_id !== null) {
                    return $sale->fresh(['items.product', 'journalEntry']);
                }

                $entry = $this->posSaleService->postSaleJournal(
                    $sale,
                    $tenantUserId,
                    PosSale::PAYMENT_BANK,
                    $actingUserId,
                );

                $sale->forceFill([
                    'journal_entry_id' => $entry->id,
                    'status' => PosSale::STATUS_COLLECTED,
                ])->save();

                AuditLog::logModuleEvent('online_store_transfer_verified', [
                    'pos_sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'journal_entry_id' => $entry->id,
                    'total_amount' => (string) $sale->total_amount,
                ], $sale);

                $fresh = $sale->fresh(['items.product', 'journalEntry']);

                DB::afterCommit(fn () => $this->whatsappNotifier->dispatchInvoiceNotification($tenantUserId, $saleId));

                return $fresh;
            }

            if ($sale->status === PosSale::STATUS_DELIVERED) {
                if ($sale->collection_journal_entry_id !== null) {
                    return $sale->fresh(['items.product', 'journalEntry', 'collectionJournalEntry']);
                }

                $collectionEntry = $this->posSaleService->postCollectionJournal(
                    $sale,
                    $tenantUserId,
                    $actingUserId,
                );

                $sale->forceFill([
                    'collection_journal_entry_id' => $collectionEntry->id,
                    'status' => PosSale::STATUS_COLLECTED,
                ])->save();

                AuditLog::logModuleEvent('online_store_order_collected', [
                    'pos_sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'journal_entry_id' => $sale->journal_entry_id,
                    'collection_journal_entry_id' => $collectionEntry->id,
                    'total_amount' => (string) $sale->total_amount,
                ], $sale);

                $fresh = $sale->fresh(['items.product', 'journalEntry', 'collectionJournalEntry']);

                DB::afterCommit(fn () => $this->whatsappNotifier->dispatchInvoiceNotification($tenantUserId, $saleId));

                return $fresh;
            }

            if ($sale->status !== PosSale::STATUS_PENDING) {
                throw new InvalidArgumentException('يمكن تأكيد التحصيل للطلبات المعلّقة أو المُسلّمة أو بانتظار التحقق فقط.');
            }

            if ($sale->journal_entry_id !== null) {
                return $sale->fresh(['items.product', 'journalEntry']);
            }

            $entry = $this->posSaleService->postSaleJournal(
                $sale,
                $tenantUserId,
                PosSale::PAYMENT_CASH,
                $actingUserId,
            );

            $sale->forceFill([
                'journal_entry_id' => $entry->id,
                'status' => PosSale::STATUS_COLLECTED,
            ])->save();

            AuditLog::logModuleEvent('online_store_order_collected', [
                'pos_sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'journal_entry_id' => $entry->id,
                'total_amount' => (string) $sale->total_amount,
            ], $sale);

            $fresh = $sale->fresh(['items.product', 'journalEntry']);

            DB::afterCommit(fn () => $this->whatsappNotifier->dispatchInvoiceNotification($tenantUserId, $saleId));

            return $fresh;
        });
    }

    public function voidSale(int $tenantUserId, int $saleId, ?int $actingUserId = null): PosSale
    {
        return DB::transaction(function () use ($tenantUserId, $saleId, $actingUserId): PosSale {
            /** @var PosSale $sale */
            $sale = PosSale::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($saleId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status === PosSale::STATUS_VOIDED) {
                return $sale->fresh(['items.product']);
            }

            if ($sale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
                throw new InvalidArgumentException('الإلغاء متاح لطلبات المتجر الإلكتروني فقط.');
            }

            if (! in_array($sale->status, [PosSale::STATUS_PENDING, PosSale::STATUS_PENDING_VERIFICATION], true)) {
                throw new InvalidArgumentException('يمكن إلغاء الطلبات المعلّقة أو بانتظار التحقق فقط.');
            }

            if ($sale->journal_entry_id !== null || $sale->collection_journal_entry_id !== null) {
                throw new InvalidArgumentException('لا يمكن إلغاء طلب مُرحّل محاسبياً.');
            }

            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                /** @var PosProduct $product */
                $product = PosProduct::withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($item->pos_product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product->current_quantity = round(
                    (float) $product->current_quantity + (float) $item->quantity,
                    4,
                );
                $product->save();
            }

            $sale->forceFill(['status' => PosSale::STATUS_VOIDED])->save();

            AuditLog::logModuleEvent('online_store_order_voided', [
                'pos_sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total_amount' => (string) $sale->total_amount,
            ], $sale);

            return $sale->fresh(['items.product']);
        });
    }

    private function lockOnlineCodSale(int $tenantUserId, int $saleId): PosSale
    {
        /** @var PosSale $sale */
        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($sale->payment_method !== PosSale::PAYMENT_COD
            || $sale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
            throw new InvalidArgumentException('هذه العملية متاحة لطلبات المتجر (COD) فقط.');
        }

        return $sale;
    }

    private function lockOnlineCollectibleSale(int $tenantUserId, int $saleId): PosSale
    {
        /** @var PosSale $sale */
        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($sale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
            throw new InvalidArgumentException('هذه العملية متاحة لطلبات المتجر الإلكتروني فقط.');
        }

        if ($sale->payment_method === PosSale::PAYMENT_COD) {
            return $sale;
        }

        if ($sale->payment_method === PosSale::PAYMENT_MANUAL_TRANSFER
            && $sale->status === PosSale::STATUS_PENDING_VERIFICATION) {
            return $sale;
        }

        throw new InvalidArgumentException('تأكيد التحصيل/التحقق غير متاح لنوع هذا الطلب.');
    }
}
