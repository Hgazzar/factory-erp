<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Core\Messaging\WhatsAppConfigResolver;
use App\Services\Store\Payment\StorePaymentMethodResolver;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Services\Tenant\NicheLexiconService;
use App\Services\Tenant\TenantFeatureRegistry;

/**
 * عرض إعدادات المتجر للتاجر — تسميات حسب النيش + حالة واتساب من Core.
 */
final class StoreMerchantSettingsPresenter
{
    public function __construct(
        private readonly StoreNicheCapabilities $niches,
        private readonly WhatsAppConfigResolver $whatsappConfig,
        private readonly NicheLexiconService $lexicon,
        private readonly TenantFeatureRegistry $features,
        private readonly StorePaymentMethodResolver $paymentMethods,
    ) {}

    /**
     * @return array{
     *   niche_key: string,
     *   page_title: string,
     *   storefront_label: string,
     *   orders_label: string,
     *   enable_storefront_label: string,
     *   payment_methods_heading: string,
     *   metrics_pending_label: string,
     *   paymob_webhook_url: string,
     *   payment_toggles: list<array{field:string, hint:string, label:string, default_checked:bool}>,
     *   whatsapp: array{
     *     profile: string,
     *     api_enabled: bool,
     *     automation_feature_keys: list<string>,
     *     automation_enabled: bool
     *   }
     * }
     */
    public function present(int $tenantUserId, TenantStoreSetting $settings, ?TenantProfile $profile): array
    {
        $nicheKey = strtolower(trim((string) ($profile?->niche_key ?? $this->lexicon->resolveNicheKey($tenantUserId))));
        $automationKeys = $this->niches->whatsappAutomationFeatureKeys($nicheKey);
        $whatsappProfile = WhatsAppConfigResolver::PROFILE_STORE;

        return [
            'niche_key' => $nicheKey,
            'page_title' => $this->niches->settingsNavLabel($nicheKey),
            'storefront_label' => $this->niches->storefrontLabel($nicheKey),
            'orders_label' => $this->niches->ordersNavLabel($nicheKey),
            'enable_storefront_label' => $this->niches->enablePublicStorefrontLabel($nicheKey),
            'payment_methods_heading' => $this->niches->paymentMethodsHeading($nicheKey),
            'metrics_pending_label' => $this->niches->metricsPendingCollectionLabel($nicheKey),
            'paymob_webhook_url' => store_paymob_webhook_url(),
            'payment_toggles' => $this->paymentToggles($tenantUserId, $settings, $nicheKey),
            'whatsapp' => [
                'profile' => $whatsappProfile,
                'api_enabled' => $this->whatsappConfig->isEnabled($whatsappProfile),
                'automation_feature_keys' => $automationKeys,
                'automation_enabled' => $this->automationEnabledForTenant($tenantUserId, $automationKeys),
            ],
        ];
    }

    /**
     * @return list<array{field:string, hint:string, label:string, default_checked:bool}>
     */
    private function paymentToggles(int $tenantUserId, TenantStoreSetting $settings, string $nicheKey): array
    {
        $providerLabel = $settings->paymentProviderLabel();
        $allowedMethods = array_column(
            $this->paymentMethods->availableMethods($tenantUserId, $settings),
            'key',
        );

        $definitions = [
            [
                'field' => 'cod_enabled',
                'method' => 'cod',
                'hint' => 'store.payment_cod',
                'label' => $this->codLabel($nicheKey),
                'default_checked' => (bool) ($settings->cod_enabled ?? true),
            ],
            [
                'field' => 'manual_transfer_enabled',
                'method' => 'manual_transfer',
                'hint' => 'store.payment_manual_transfer',
                'label' => 'تحويل بنكي + إيصال',
                'default_checked' => (bool) $settings->manual_transfer_enabled,
            ],
            [
                'field' => 'online_payment_enabled',
                'method' => 'card',
                'hint' => 'pos.online_payment_enabled',
                'label' => $providerLabel.' (بطاقة/محفظة)',
                'default_checked' => (bool) $settings->online_payment_enabled,
            ],
            [
                'field' => 'tamara_enabled',
                'method' => 'tamara',
                'hint' => 'store.payment_tamara',
                'label' => 'Tamara (السعودية)',
                'default_checked' => (bool) $settings->tamara_enabled,
            ],
            [
                'field' => 'tabby_enabled',
                'method' => 'tabby',
                'hint' => 'store.payment_tabby',
                'label' => 'Tabby',
                'default_checked' => (bool) $settings->tabby_enabled,
            ],
        ];

        return array_values(array_filter(
            $definitions,
            static fn (array $row): bool => in_array($row['method'], $allowedMethods, true),
        ));
    }

    private function codLabel(string $nicheKey): string
    {
        return match (strtolower(trim($nicheKey))) {
            'fleet_agents' => 'الدفع عند التحصيل الميداني (COD)',
            'manufacturing' => 'الدفع عند استلام الطلب (COD)',
            default => 'الدفع عند الاستلام (COD)',
        };
    }

    /**
     * @param  list<string>  $featureKeys
     */
    private function automationEnabledForTenant(int $tenantUserId, array $featureKeys): bool
    {
        foreach ($featureKeys as $key) {
            if ($this->features->isEnabled($key, $tenantUserId)) {
                return true;
            }
        }

        return false;
    }

}
