<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\User;
use App\Support\ErpRoles;

/**
 * سياق لوحة التحكم الرئيسية: النيش، الموديولات، المزايا البريميوم، والروابط السريعة.
 */
final class TenantDashboardPackageService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantModuleRegistry $moduleRegistry,
        private readonly TenantFeatureRegistry $featureRegistry,
        private readonly NicheLexiconService $lexiconService,
        private readonly NicheCatalog $nicheCatalog,
        private readonly PremiumFeatureCatalog $premiumCatalog,
        private readonly TenantNavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     niche_key: ?string,
     *     niche_name: ?string,
     *     modules: list<array{key: string, label: string}>,
     *     premium_features: list<array{key: string, name_ar: string}>,
     *     quick_links: list<array{label: string, route: string, hint: string}>,
     * }
     */
    public function buildForViewer(?User $viewer = null): array
    {
        $viewer ??= auth()->user();
        if (! $viewer instanceof User || ErpRoles::isSuperAdmin($viewer)) {
            return [
                'niche_key' => null,
                'niche_name' => null,
                'modules' => [],
                'premium_features' => [],
                'quick_links' => [],
            ];
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($viewer);
        if ($tenantUserId === null) {
            return [
                'niche_key' => null,
                'niche_name' => null,
                'modules' => [],
                'premium_features' => [],
                'quick_links' => [],
            ];
        }

        $nicheKey = $this->lexiconService->resolveNicheKey($tenantUserId);
        $niche = $nicheKey !== null ? $this->nicheCatalog->find($nicheKey) : null;
        $nicheName = $niche['name_ar'] ?? $niche['name_en'] ?? null;

        $moduleKeys = array_values(array_filter(
            $this->moduleRegistry->enabledKeys($tenantUserId),
            static fn (string $k): bool => $k !== 'core'
        ));

        $modules = [];
        foreach ($moduleKeys as $key) {
            $modules[] = [
                'key' => $key,
                'label' => $this->lexiconService->moduleLabel($key, $tenantUserId),
            ];
        }

        $enabledFeatureKeys = $this->featureRegistry->enabledKeysForTenant($tenantUserId);
        $premiumDefs = $this->premiumCatalog->definitionsForNiche($nicheKey);
        $premiumFeatures = [];
        foreach ($premiumDefs as $def) {
            $key = strtolower((string) ($def['key'] ?? ''));
            if ($key !== '' && in_array($key, $enabledFeatureKeys, true)) {
                $premiumFeatures[] = [
                    'key' => $key,
                    'name_ar' => (string) ($def['name_ar'] ?? $key),
                ];
            }
        }

        return [
            'niche_key' => $nicheKey,
            'niche_name' => $nicheName,
            'modules' => $modules,
            'premium_features' => $premiumFeatures,
            'quick_links' => $this->navigation->quickLinks($tenantUserId),
        ];
    }
}
