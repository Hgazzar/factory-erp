<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Services\Store\StoreOrderWhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendStoreOrderReceivedWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $saleId,
    ) {}

    public function handle(StoreOrderWhatsAppNotifier $notifier): void
    {
        $notifier->notifyOrderReceived($this->tenantUserId, $this->saleId);
    }
}
