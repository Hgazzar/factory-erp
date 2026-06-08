<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurseryRenewalReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurserySubscriptionPaidConfirmationWhatsAppJob;
use App\Models\Nursery\Subscription;
use Illuminate\Support\Facades\Log;

final class NurseryWhatsAppNotifier
{
    public function __construct(
        private readonly NurseryWhatsAppNotificationService $whatsapp,
    ) {}

    public function dispatchSubscriptionPaidConfirmation(int $tenantUserId, int $subscriptionId): void
    {
        SendNurserySubscriptionPaidConfirmationWhatsAppJob::dispatch($tenantUserId, $subscriptionId);
    }

    public function dispatchPaymentReminder(int $tenantUserId, int $subscriptionId): void
    {
        SendNurseryPaymentReminderWhatsAppJob::dispatch($tenantUserId, $subscriptionId);
    }

    public function dispatchRenewalReminder(int $tenantUserId, int $subscriptionId): void
    {
        SendNurseryRenewalReminderWhatsAppJob::dispatch($tenantUserId, $subscriptionId);
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
        if ($subscription === null || $subscription->payment_reminder_sent_at !== null) {
            return false;
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
        if ($subscription === null || $subscription->renewal_reminder_sent_at !== null) {
            return false;
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
