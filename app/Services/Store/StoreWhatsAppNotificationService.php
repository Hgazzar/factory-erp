<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Core\Messaging\WhatsAppChannelFactory;
use App\Core\Messaging\WhatsAppConfigResolver;
use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantFeatureRegistry;

/**
 * إشعارات واتساب لطلبات المتجر — قوالب الرسائل هنا؛ النقل عبر Core Messaging.
 */
final class StoreWhatsAppNotificationService
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
        $registry = app(TenantFeatureRegistry::class);
        $nicheKey = app(\App\Services\Tenant\NicheLexiconService::class)->resolveNicheKey($tenantUserId);

        foreach (app(StoreNicheCapabilities::class)->whatsappAutomationFeatureKeys($nicheKey) as $key) {
            if ($registry->isEnabled($key, $tenantUserId)) {
                return true;
            }
        }

        return false;
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
        return $this->channel()->sendText($toPhone, $message);
    }

    public function sendDocumentMessage(string $toPhone, string $absolutePath, string $filename, ?string $caption = null): bool
    {
        return $this->channel()->sendDocument($toPhone, $absolutePath, $filename, $caption);
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

    private function channel(): \App\Contracts\Core\Messaging\WhatsAppChannelInterface
    {
        return $this->channels->forProfile(WhatsAppConfigResolver::PROFILE_STORE);
    }
}
