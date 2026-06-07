<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Jobs\Store\SendStoreOrderDeliveredWhatsAppJob;
use App\Jobs\Store\SendStoreOrderInvoiceWhatsAppJob;
use App\Jobs\Store\SendStoreOrderReceivedWhatsAppJob;
use App\Models\PosSale;
use Illuminate\Support\Facades\Log;

final class StoreOrderWhatsAppNotifier
{
    public function __construct(
        private readonly StoreWhatsAppNotificationService $whatsapp,
        private readonly StorePosSalePdfService $pdf,
    ) {}

    public function dispatchOrderReceived(int $tenantUserId, int $saleId): void
    {
        SendStoreOrderReceivedWhatsAppJob::dispatch($tenantUserId, $saleId);
    }

    public function dispatchDeliveredNotification(int $tenantUserId, int $saleId): void
    {
        SendStoreOrderDeliveredWhatsAppJob::dispatch($tenantUserId, $saleId);
    }

    public function dispatchInvoiceNotification(int $tenantUserId, int $saleId): void
    {
        SendStoreOrderInvoiceWhatsAppJob::dispatch($tenantUserId, $saleId);
    }

    public function notifyOrderReceived(int $tenantUserId, int $saleId): void
    {
        $sale = $this->loadOnlineSale($tenantUserId, $saleId);
        if ($sale === null || $sale->whatsapp_received_notified_at !== null) {
            return;
        }

        try {
            if ($this->whatsapp->sendOrderReceived($tenantUserId, $sale)) {
                $sale->forceFill(['whatsapp_received_notified_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::error('Store WhatsApp order received notification failed', [
                'pos_sale_id' => $saleId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyDelivered(int $tenantUserId, int $saleId): void
    {
        $sale = $this->loadOnlineSale($tenantUserId, $saleId);
        if ($sale === null || $sale->whatsapp_delivered_notified_at !== null) {
            return;
        }

        if ($sale->status !== PosSale::STATUS_DELIVERED) {
            return;
        }

        try {
            if ($this->whatsapp->sendOrderDelivered($tenantUserId, $sale)) {
                $sale->forceFill(['whatsapp_delivered_notified_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::error('Store WhatsApp delivered notification failed', [
                'pos_sale_id' => $saleId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyInvoice(int $tenantUserId, int $saleId): void
    {
        $sale = $this->loadOnlineSale($tenantUserId, $saleId);
        if ($sale === null || $sale->whatsapp_invoice_notified_at !== null) {
            return;
        }

        if (! in_array($sale->status, [PosSale::STATUS_COLLECTED, PosSale::STATUS_COMPLETED], true)) {
            return;
        }

        try {
            $pdfPath = $this->pdf->storeReceiptFile($sale, $tenantUserId);
            $invoiceUrl = $this->pdf->signedInvoiceUrl($sale);

            if ($this->whatsapp->sendOrderInvoice($tenantUserId, $sale, $pdfPath, $invoiceUrl)) {
                $sale->forceFill(['whatsapp_invoice_notified_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::error('Store WhatsApp invoice notification failed', [
                'pos_sale_id' => $saleId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function loadOnlineSale(int $tenantUserId, int $saleId): ?PosSale
    {
        /** @var PosSale|null $sale */
        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->first();

        return $sale?->loadMissing(['items.product']);
    }
}
