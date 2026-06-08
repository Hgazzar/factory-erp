<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosSale;
use App\Services\Pos\PosSaleService;
use App\Core\Payment\PaymentWebhookResult;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StorePaymobWebhookService
{
    public function __construct(
        private readonly PosSaleService $posSales,
        private readonly StoreOrderWhatsAppNotifier $notifier,
    ) {}

    public function applyPaymentResult(int $tenantUserId, PaymentWebhookResult $result): ?PosSale
    {
        if (! $result->success) {
            return null;
        }

        return DB::transaction(function () use ($tenantUserId, $result): ?PosSale {
            /** @var PosSale|null $sale */
            $sale = PosSale::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->where('payment_gateway_reference', $result->gatewayReference)
                ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
                ->lockForUpdate()
                ->first();

            if ($sale === null) {
                return null;
            }

            if ($sale->status === PosSale::STATUS_COMPLETED) {
                return $sale;
            }

            if ($sale->journal_entry_id === null) {
                $entry = $this->posSales->postSaleJournal(
                    $sale,
                    $tenantUserId,
                    PosSale::PAYMENT_CARD,
                );
                $sale->forceFill(['journal_entry_id' => $entry->id])->save();
            }

            $sale->forceFill(['status' => PosSale::STATUS_COMPLETED])->save();

            $this->notifier->dispatchInvoiceNotification($tenantUserId, (int) $sale->id);

            return $sale->fresh(['items.product', 'journalEntry']);
        });
    }

    public function findTenantByGatewayReference(string $reference): ?int
    {
        $sale = PosSale::withoutGlobalScopes()
            ->where('payment_gateway_reference', $reference)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->first(['user_id']);

        return $sale ? (int) $sale->user_id : null;
    }
}
