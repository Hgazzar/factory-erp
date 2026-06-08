<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Core\Messaging\WhatsAppChannelFactory;
use App\Core\Messaging\WhatsAppConfigResolver;
use App\Models\Nursery\NurserySetting;
use App\Models\Nursery\Subscription;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PremiumFeatureKeys;

final class NurseryWhatsAppNotificationService
{
    public function __construct(
        private readonly WhatsAppChannelFactory $channels,
    ) {}

    public function isEnabled(): bool
    {
        return $this->channel()->isEnabled();
    }

    public function featureEnabled(int $tenantUserId): bool
    {
        return app(TenantFeatureRegistry::class)->isEnabled(
            PremiumFeatureKeys::NURSERY_WHATSAPP_AUTOMATION,
            $tenantUserId,
        );
    }

    public function sendPaymentReminder(int $tenantUserId, Subscription $subscription): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $subscription->loadMissing(['child.guardian', 'plan']);
        $phone = $subscription->child?->guardian?->phone;

        if ($phone === null || trim($phone) === '') {
            return false;
        }

        $nurseryName = NurserySetting::forTenant($tenantUserId)->nursery_name;
        $childName = (string) ($subscription->child?->name ?? 'الطفل');
        $planName = (string) ($subscription->plan?->name ?? 'الاشتراك');
        $amount = number_format($subscription->finalAmount(), 2);
        $endsOn = $subscription->ends_on?->format('Y-m-d') ?? '';

        $message = implode("\n", [
            "تذكير بالدفع — {$nurseryName}",
            "مرحباً،",
            '',
            "اشتراك {$childName} ({$planName}) لم يُسدَّد بعد.",
            "المبلغ المستحق: {$amount} ر.س",
            $endsOn !== '' ? "ينتهي الاشتراك: {$endsOn}" : '',
            '',
            'يرجى التواصل مع إدارة الحضانة لإتمام السداد.',
        ]);

        return $this->sendTextMessage($phone, $message);
    }

    public function sendRenewalReminder(int $tenantUserId, Subscription $subscription): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $subscription->loadMissing(['child.guardian', 'plan']);
        $phone = $subscription->child?->guardian?->phone;

        if ($phone === null || trim($phone) === '') {
            return false;
        }

        $nurseryName = NurserySetting::forTenant($tenantUserId)->nursery_name;
        $childName = (string) ($subscription->child?->name ?? 'الطفل');
        $planName = (string) ($subscription->plan?->name ?? 'الاشتراك');
        $endsOn = $subscription->ends_on?->format('Y-m-d') ?? '';

        $message = implode("\n", [
            "تذكير بتجديد الاشتراك — {$nurseryName}",
            "مرحباً،",
            '',
            "اشتراك {$childName} ({$planName}) يقترب من الانتهاء.",
            $endsOn !== '' ? "تاريخ الانتهاء: {$endsOn}" : '',
            '',
            'للتجديد يرجى التواصل مع إدارة الحضانة.',
        ]);

        return $this->sendTextMessage($phone, $message);
    }

    public function sendSubscriptionPaidConfirmation(int $tenantUserId, Subscription $subscription): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $subscription->loadMissing(['child.guardian', 'plan']);
        $phone = $subscription->child?->guardian?->phone;

        if ($phone === null || trim($phone) === '') {
            return false;
        }

        $nurseryName = NurserySetting::forTenant($tenantUserId)->nursery_name;
        $childName = (string) ($subscription->child?->name ?? 'الطفل');
        $amount = number_format($subscription->finalAmount(), 2);

        $message = implode("\n", [
            "تأكيد سداد اشتراك — {$nurseryName} ✓",
            "تم استلام مبلغ {$amount} ر.س لاشتراك {$childName}.",
            'شكراً لكم.',
        ]);

        return $this->sendTextMessage($phone, $message);
    }

    public function sendTextMessage(string $toPhone, string $message): bool
    {
        return $this->channel()->sendText($toPhone, $message, previewUrl: false);
    }

    private function channel(): \App\Contracts\Core\Messaging\WhatsAppChannelInterface
    {
        return $this->channels->forProfile(WhatsAppConfigResolver::PROFILE_NURSERY);
    }
}
