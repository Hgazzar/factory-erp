<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Contracts\Core\Metrics\MetricsQueryInterface;
use App\Core\Metrics\MetricsQueryRegistry;
use App\Models\CompanySetting;
use App\Models\TenantProfile;
use App\Services\Tenant\NicheLexiconService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\StoreFeatureKeys;

/**
 * يجهّز بيانات لوحة المتجر الأونلاين للتاجر — KPIs، رسوم، وروابط حسب النيش.
 */
final class StoreOnlineDashboardPresenter
{
    public const VARIANT_FULL = 'full';

    public const VARIANT_COMPACT = 'compact';

    public const VARIANT_EMBEDDED = 'embedded';

    public function __construct(
        private readonly MetricsQueryRegistry $metricsRegistry,
        private readonly StoreNicheCapabilities $niches,
        private readonly NicheLexiconService $lexicon,
        private readonly TenantFeatureRegistry $features,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function present(int $tenantUserId, string $variant = self::VARIANT_FULL): ?array
    {
        if (! $this->isOnlineStoreActive($tenantUserId)) {
            return null;
        }

        $nicheKey = strtolower(trim($this->lexicon->resolveNicheKey($tenantUserId)));
        $metrics = $this->metricsRegistry->get('store_online')->snapshot($tenantUserId);
        $profile = TenantProfile::forTenantUser($tenantUserId);
        $slug = $profile?->slug ?? $profile?->domain;
        $currency = CompanySetting::resolvedCurrencyCode($tenantUserId);

        $base = [
            'variant' => $variant,
            'niche_key' => $nicheKey,
            'storefront_label' => $this->niches->storefrontLabel($nicheKey),
            'orders_label' => $this->niches->ordersNavLabel($nicheKey),
            'metrics_pending_label' => $this->niches->metricsPendingCollectionLabel($nicheKey),
            'currency' => $currency,
            'metrics' => $metrics,
            'links' => [
                'orders' => route('pos.orders.index'),
                'settings' => route('settings.store.edit'),
                'storefront' => ($slug !== null && trim($slug) !== '')
                    ? route('store.portal.home', ['tenant_slug' => $slug])
                    : null,
            ],
        ];

        if ($variant === self::VARIANT_COMPACT) {
            $base['metrics'] = [
                'sales_today' => $metrics['sales_today'],
                'orders_today' => $metrics['orders_today'],
                'revenue_month' => $metrics['revenue_month'],
                'pending_collection' => $metrics['pending_collection'],
            ];
        }

        if ($variant === self::VARIANT_EMBEDDED) {
            $base['metrics'] = [
                'sales_today' => $metrics['sales_today'],
                'orders_today' => $metrics['orders_today'],
                'revenue_month' => $metrics['revenue_month'],
                'pending_collection' => $metrics['pending_collection'],
                'top_products' => $metrics['top_products'],
                'recent_orders' => $metrics['recent_orders'],
            ];
        }

        return $base;
    }

    public function isOnlineStoreActive(int $tenantUserId): bool
    {
        if (! $this->features->isEnabled(StoreFeatureKeys::ONLINE_STORE, $tenantUserId)) {
            return false;
        }

        $nicheKey = $this->lexicon->resolveNicheKey($tenantUserId);

        return $this->niches->supportsOnlineStorePortal($nicheKey);
    }
}
