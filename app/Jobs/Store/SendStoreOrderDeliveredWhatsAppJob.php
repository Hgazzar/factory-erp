<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Services\Store\StoreOrderWhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendStoreOrderDeliveredWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $saleId,
    ) {}

    public function handle(StoreOrderWhatsAppNotifier $notifier): void
    {
        $notifier->notifyDelivered($this->tenantUserId, $this->saleId);
    }
}
