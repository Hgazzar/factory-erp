<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\NurseryOutboundMessage;
use App\Models\Nursery\Subscription;
use Illuminate\Support\Facades\Log;

final class NurseryWhatsAppNotifier
{
    public function __construct(
        private readonly NurseryWhatsAppNotificationService $whatsapp,
        private readonly NurseryWhatsAppOutboxService $outbox,
    ) {}

    public function dispatchSubscriptionPaidConfirmation(int $tenantUserId, int $subscriptionId): void
    {
        $this->outbox->enqueueSubscriptionMessage(
            $tenantUserId,
            $subscriptionId,
            NurseryOutboundMessage::TYPE_SUBSCRIPTION_PAID_CONFIRMATION,
        );
    }

    public function dispatchPaymentReminder(int $tenantUserId, int $subscriptionId): void
    {
        $this->outbox->enqueueSubscriptionMessage(
            $tenantUserId,
            $subscriptionId,
            NurseryOutboundMessage::TYPE_PAYMENT_REMINDER,
        );
    }

    public function dispatchRenewalReminder(int $tenantUserId, int $subscriptionId): void
    {
        $this->outbox->enqueueSubscriptionMessage(
            $tenantUserId,
            $subscriptionId,
            NurseryOutboundMessage::TYPE_RENEWAL_REMINDER,
        );
    }

    public function notifySubscriptionPaidConfirmation(int $tenantUserId, int $subscriptionId): bool
    {
        $subscription = $this->loadSubscription($tenantUserId, $subscriptionId);
        if ($subscription === null) {
            return false;
        }

        try {
            return $this->whatsapp->sendSubscriptionPaidConfirmation($tenantUserId, $subscription);
        } catch (\Throwable $e) {
            Log::error('Nursery WhatsApp paid confirmation failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyPaymentReminder(int $tenantUserId, int $subscriptionId): bool
    {
        $subscription = $this->loadSubscription($tenantUserId, $subscriptionId);
        if ($subscription === null) {
            return false;
        }

        if ($subscription->payment_reminder_sent_at !== null) {
            return true;
        }

        try {
            if (! $this->whatsapp->sendPaymentReminder($tenantUserId, $subscription)) {
                return false;
            }

            $subscription->forceFill(['payment_reminder_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Nursery WhatsApp payment reminder failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyRenewalReminder(int $tenantUserId, int $subscriptionId): bool
    {
        $subscription = $this->loadSubscription($tenantUserId, $subscriptionId);
        if ($subscription === null) {
            return false;
        }

        if ($subscription->renewal_reminder_sent_at !== null) {
            return true;
        }

        try {
            if (! $this->whatsapp->sendRenewalReminder($tenantUserId, $subscription)) {
                return false;
            }

            $subscription->forceFill(['renewal_reminder_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Nursery WhatsApp renewal reminder failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function loadSubscription(int $tenantUserId, int $subscriptionId): ?Subscription
    {
        /** @var Subscription|null $subscription */
        $subscription = Subscription::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($subscriptionId)
            ->first();

        return $subscription?->loadMissing(['child.guardian', 'plan']);
    }
}
