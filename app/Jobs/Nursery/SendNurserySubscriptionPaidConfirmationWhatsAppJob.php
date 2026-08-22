<?php

declare(strict_types=1);

namespace App\Jobs\Nursery;

use App\Services\Nursery\NurseryWhatsAppNotifier;
use App\Services\Nursery\NurseryWhatsAppOutboxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendNurserySubscriptionPaidConfirmationWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $subscriptionId,
        public readonly ?int $outboxId = null,
    ) {}

    public function handle(NurseryWhatsAppNotifier $notifier, NurseryWhatsAppOutboxService $outbox): void
    {
        if ($this->outboxId === null) {
            $notifier->notifySubscriptionPaidConfirmation($this->tenantUserId, $this->subscriptionId);

            return;
        }

        $outbox->process($this->outboxId, function () use ($notifier): bool {
            return $notifier->notifySubscriptionPaidConfirmation($this->tenantUserId, $this->subscriptionId);
        });
    }
}
