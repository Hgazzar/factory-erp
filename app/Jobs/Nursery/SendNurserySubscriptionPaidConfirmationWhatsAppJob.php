<?php

declare(strict_types=1);

namespace App\Jobs\Nursery;

use App\Services\Nursery\NurseryWhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendNurserySubscriptionPaidConfirmationWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $subscriptionId,
    ) {}

    public function handle(NurseryWhatsAppNotifier $notifier): void
    {
        $notifier->notifySubscriptionPaidConfirmation($this->tenantUserId, $this->subscriptionId);
    }
}
