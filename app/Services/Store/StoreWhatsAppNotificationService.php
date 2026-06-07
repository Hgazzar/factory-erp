<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PremiumFeatureKeys;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * إشعارات واتساب لطلبات المتجر — يعيد استخدام إعدادات Meta Cloud API (مثل العيادة).
 */
final class StoreWhatsAppNotificationService
{
    public function isEnabled(): bool
    {
        return (bool) config('store.whatsapp.enabled', false)
            && trim((string) config('store.whatsapp.access_token', '')) !== ''
            && trim((string) config('store.whatsapp.phone_number_id', '')) !== '';
    }

    public function featureEnabled(int $tenantUserId): bool
    {
        return app(TenantFeatureRegistry::class)->isEnabled(
            PremiumFeatureKeys::RETAIL_WHATSAPP_AUTOMATION,
            $tenantUserId,
        );
    }

    public function sendOrderDelivered(int $tenantUserId, PosSale $sale): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $phone = trim((string) $sale->customer_phone);
        if ($phone === '') {
            return false;
        }

        $storeName = CompanySetting::forTenant($tenantUserId)?->name ?? config('app.name');
        $customerName = trim((string) ($sale->customer_name ?? 'عميلنا'));
        $invoice = (string) $sale->invoice_number;
        $trackUrl = $this->trackOrderUrl($tenantUserId);

        $lines = [
            "مرحباً {$customerName} 👋",
            '',
            "تم تسليم طلبك من {$storeName}. 📦",
            "🔖 رقم الطلب: {$invoice}",
        ];

        if ($trackUrl !== null) {
            $lines[] = "🔗 تتبع الطلب: {$trackUrl}";
        }

        $lines[] = '';
        $lines[] = 'نتمنى أن تستمتع بمشترياتك!';

        return $this->sendTextMessage($phone, implode("\n", $lines));
    }

    public function sendOrderInvoice(int $tenantUserId, PosSale $sale, string $pdfAbsolutePath, ?string $invoiceUrl = null): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $phone = trim((string) $sale->customer_phone);
        if ($phone === '') {
            return false;
        }

        $storeName = CompanySetting::forTenant($tenantUserId)?->name ?? config('app.name');
        $customerName = trim((string) ($sale->customer_name ?? 'عميلنا'));
        $invoice = (string) $sale->invoice_number;
        $total = number_format((float) $sale->total_amount, 2);
        $currency = CompanySetting::resolvedCurrencyCode($tenantUserId);

        $caption = implode("\n", [
            "شكراً {$customerName}! ✅",
            "تم تأكيد طلبك ودفعك في {$storeName}.",
            "🔖 {$invoice}",
            "💰 الإجمالي: {$total} {$currency}",
            $invoiceUrl ? "📄 الفاتورة: {$invoiceUrl}" : '',
        ]);

        $filename = 'invoice-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice).'.pdf';

        if ($this->sendDocumentMessage($phone, $pdfAbsolutePath, $filename, trim($caption))) {
            return true;
        }

        return $this->sendTextMessage($phone, $caption);
    }

    public function sendOrderReceived(int $tenantUserId, PosSale $sale): bool
    {
        if (! $this->featureEnabled($tenantUserId)) {
            return false;
        }

        $phone = trim((string) $sale->customer_phone);
        if ($phone === '') {
            return false;
        }

        $storeName = CompanySetting::forTenant($tenantUserId)?->name ?? config('app.name');
        $customerName = trim((string) ($sale->customer_name ?? 'عميلنا'));
        $invoice = (string) $sale->invoice_number;
        $total = number_format((float) $sale->total_amount, 2);
        $currency = CompanySetting::resolvedCurrencyCode($tenantUserId);
        $trackUrl = $this->trackOrderUrl($tenantUserId);

        $statusNote = match ($sale->status) {
            PosSale::STATUS_COMPLETED => 'تم استلام طلبك ودفعك بنجاح.',
            PosSale::STATUS_PENDING_VERIFICATION => 'تم استلام طلبك — سيتم مراجعة إيصال التحويل قريباً.',
            default => 'تم استلام طلبك بنجاح.',
        };

        $lines = [
            "مرحباً {$customerName}! 🛍️",
            '',
            "{$statusNote}",
            "🏪 {$storeName}",
            "🔖 {$invoice}",
            "💰 {$total} {$currency}",
        ];

        if ($trackUrl !== null) {
            $lines[] = "🔗 تتبع: {$trackUrl}";
        }

        return $this->sendTextMessage($phone, implode("\n", $lines));
    }

    public function sendTextMessage(string $toPhone, string $message): bool
    {
        $to = $this->normalizePhone($toPhone);

        if ($to === '') {
            Log::warning('Store WhatsApp: empty recipient phone.');

            return false;
        }

        if (! $this->isEnabled()) {
            Log::info('Store WhatsApp (dry-run): would send message', [
                'to' => $to,
                'message' => $message,
            ]);

            return true;
        }

        return $this->postMessage($to, [
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ]);
    }

    public function sendDocumentMessage(string $toPhone, string $absolutePath, string $filename, ?string $caption = null): bool
    {
        $to = $this->normalizePhone($toPhone);

        if ($to === '' || ! is_file($absolutePath)) {
            return false;
        }

        if (! $this->isEnabled()) {
            Log::info('Store WhatsApp (dry-run): would send document', [
                'to' => $to,
                'filename' => $filename,
                'caption' => $caption,
                'path' => $absolutePath,
            ]);

            return true;
        }

        $mediaId = $this->uploadMedia($absolutePath, 'application/pdf');
        if ($mediaId === null) {
            return false;
        }

        $payload = [
            'type' => 'document',
            'document' => array_filter([
                'id' => $mediaId,
                'filename' => $filename,
                'caption' => $caption,
            ]),
        ];

        return $this->postMessage($to, $payload);
    }

    public function trackOrderUrl(int $tenantUserId): ?string
    {
        $profile = TenantProfile::forTenantUser($tenantUserId);
        $slug = $profile?->slug ?? $profile?->domain;

        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return route('store.portal.track', ['tenant_slug' => $slug]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postMessage(string $to, array $payload): bool
    {
        $token = (string) config('store.whatsapp.access_token');
        $phoneNumberId = (string) config('store.whatsapp.phone_number_id');
        $apiVersion = (string) config('store.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post($url, array_merge([
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                ], $payload));

            if ($response->failed()) {
                Log::error('Store WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Store WhatsApp send failed', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }

    private function uploadMedia(string $absolutePath, string $mime): ?string
    {
        $token = (string) config('store.whatsapp.access_token');
        $phoneNumberId = (string) config('store.whatsapp.phone_number_id');
        $apiVersion = (string) config('store.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/media";

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->attach('file', file_get_contents($absolutePath) ?: '', basename($absolutePath))
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);

            if ($response->failed()) {
                Log::error('Store WhatsApp media upload failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $id = $response->json('id');

            return is_string($id) && $id !== '' ? $id : null;
        } catch (\Throwable $e) {
            Log::error('Store WhatsApp media upload exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        $defaultCountry = (string) config('store.whatsapp.default_country_code', '966');

        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountry.ltrim($digits, '0');
        }

        if (! str_starts_with($digits, $defaultCountry) && strlen($digits) <= 10) {
            $digits = $defaultCountry.ltrim($digits, '0');
        }

        return $digits;
    }
}
