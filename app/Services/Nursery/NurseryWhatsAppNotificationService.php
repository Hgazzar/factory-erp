<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\NurserySetting;
use App\Models\Nursery\Subscription;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PremiumFeatureKeys;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NurseryWhatsAppNotificationService
{
    public function isEnabled(): bool
    {
        return (bool) config('nursery.whatsapp.enabled', false)
            && trim((string) config('nursery.whatsapp.access_token', '')) !== ''
            && trim((string) config('nursery.whatsapp.phone_number_id', '')) !== '';
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
        $to = $this->normalizePhone($toPhone);

        if ($to === '') {
            Log::warning('Nursery WhatsApp: empty recipient phone.');

            return false;
        }

        if (! $this->isEnabled()) {
            Log::info('Nursery WhatsApp (dry-run): would send message', [
                'to' => $to,
                'message' => $message,
            ]);

            return true;
        }

        $token = (string) config('nursery.whatsapp.access_token');
        $phoneNumberId = (string) config('nursery.whatsapp.phone_number_id');
        $apiVersion = (string) config('nursery.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Nursery WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Nursery WhatsApp send failed', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        $defaultCountry = (string) config('nursery.whatsapp.default_country_code', '966');

        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountry.ltrim($digits, '0');
        }

        if (! str_starts_with($digits, $defaultCountry) && strlen($digits) === 9) {
            $digits = $defaultCountry.$digits;
        }

        return $digits;
    }
}
