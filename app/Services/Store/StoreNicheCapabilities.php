<?php

declare(strict_types=1);

namespace App\Services\Store;

/**
 * قدرات المتجر / POS حسب نيش المستأجر — مصدر واحد للحقيقة.
 */
final class StoreNicheCapabilities
{
    /**
     * هل يمكن لهذا النيش (نظرياً) تشغيل واجهة المتجر العامة عند تفعيل الميزة؟
     */
    public function supportsOnlineStorePortal(?string $nicheKey): bool
    {
        $nicheKey = $this->normalize($nicheKey);

        return (bool) ($this->nicheConfig($nicheKey)['online_store_portal'] ?? false);
    }

    /**
     * هل يُفعَّل online_store تلقائياً عند إنشاء مستأجر جديد لهذا النيش؟
     */
    public function provisionOnlineStoreByDefault(?string $nicheKey): bool
    {
        $nicheKey = $this->normalize($nicheKey);

        return (bool) ($this->nicheConfig($nicheKey)['provision_online_store'] ?? false);
    }

    /**
     * @return list<string>
     */
    public function whatsappAutomationFeatureKeys(?string $nicheKey): array
    {
        $nicheKey = $this->normalize($nicheKey);
        $keys = $this->nicheConfig($nicheKey)['whatsapp_feature_keys'] ?? ['retail_whatsapp_automation'];

        return array_values(array_filter(array_map(
            static fn ($k): string => strtolower(trim((string) $k)),
            is_array($keys) ? $keys : [],
        )));
    }

    public function storefrontLabel(?string $nicheKey): string
    {
        $nicheKey = $this->normalize($nicheKey);

        return (string) ($this->nicheConfig($nicheKey)['storefront_label_ar'] ?? 'المتجر الإلكتروني');
    }

    public function settingsNavLabel(?string $nicheKey): string
    {
        $nicheKey = $this->normalize($nicheKey);

        return (string) ($this->nicheConfig($nicheKey)['settings_nav_label_ar'] ?? 'المتجر الإلكتروني');
    }

    public function ordersNavLabel(?string $nicheKey): string
    {
        $nicheKey = $this->normalize($nicheKey);

        return (string) ($this->nicheConfig($nicheKey)['orders_nav_label_ar'] ?? 'طلبات المتجر');
    }

    public function posModuleLabelKey(?string $nicheKey): string
    {
        $nicheKey = $this->normalize($nicheKey);

        return (string) ($this->nicheConfig($nicheKey)['pos_lexicon_key'] ?? 'modules.pos');
    }

    /**
     * @return list<string>
     */
    public function nicheKeysWithPortalSupport(): array
    {
        return collect(config('store.niches', []))
            ->filter(static fn (array $cfg): bool => (bool) ($cfg['online_store_portal'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function nicheConfig(string $nicheKey): array
    {
        $niches = config('store.niches', []);

        if ($nicheKey !== '' && isset($niches[$nicheKey])) {
            return $niches[$nicheKey];
        }

        return $niches['_default'] ?? [];
    }

    private function normalize(?string $nicheKey): string
    {
        return strtolower(trim((string) $nicheKey));
    }
}
