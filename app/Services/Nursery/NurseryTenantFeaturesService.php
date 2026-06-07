<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\User;
use App\Services\SuperAdmin\SuperAdminTenantService;
use App\Services\Tenant\PremiumFeatureCatalog;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use InvalidArgumentException;

/**
 * مزايا الحضانة (tenant_features) — إدارة من إعدادات الحضانة.
 */
final class NurseryTenantFeaturesService
{
    private const NURSERY_NICHE_KEY = 'nurseries';

    public function __construct(
        private readonly PremiumFeatureCatalog $premiumCatalog,
        private readonly TenantFeatureRegistry $featureRegistry,
        private readonly TenantModuleRegistry $moduleRegistry,
        private readonly SuperAdminTenantService $superAdminTenants,
    ) {}

    public function isPortalEnabled(User $tenant): bool
    {
        return $tenant->hasFeature(\App\Support\PremiumFeatureKeys::NURSERY_PORTAL);
    }

    /**
     * @return array{features: list<array<string, mixed>>, catalog_keys: list<string>}
     */
    public function panelForTenant(int $tenantUserId): array
    {
        $tenant = User::query()->with('tenantProfile')->findOrFail($tenantUserId);
        $definitions = $this->premiumCatalog->definitionsForNiche(self::NURSERY_NICHE_KEY);
        $catalogKeys = $this->premiumCatalog->keysForNiche(self::NURSERY_NICHE_KEY);
        $enabledAll = $this->featureRegistry->enabledKeysForTenant($tenantUserId);
        $enabledModules = $this->moduleRegistry->enabledKeys($tenantUserId);

        $features = array_map(function (array $def) use ($enabledAll, $enabledModules): array {
            $key = strtolower((string) $def['key']);
            $requiresModule = isset($def['requires_module'])
                ? strtolower((string) $def['requires_module'])
                : null;
            $moduleEnabled = $requiresModule === null
                || in_array($requiresModule, $enabledModules, true);

            return [
                'key' => $key,
                'name_ar' => (string) $def['name_ar'],
                'description_ar' => (string) ($def['description_ar'] ?? ''),
                'hint' => (string) ($def['hint'] ?? ''),
                'enabled' => in_array($key, $enabledAll, true),
                'requires_module' => $requiresModule,
                'module_enabled' => $moduleEnabled,
                'locked' => ! $moduleEnabled,
                'locked_reason' => $moduleEnabled || $requiresModule === null
                    ? null
                    : 'فعّل موديول «'.(config("modules.modules.{$requiresModule}.name_ar") ?? $requiresModule).'» أولاً (من السوبر أدمن).',
            ];
        }, $definitions);

        return [
            'features' => $features,
            'catalog_keys' => $catalogKeys,
        ];
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    public function syncForTenant(int $tenantUserId, array $enabledKeys): void
    {
        $tenant = User::query()->with('tenantProfile')->findOrFail($tenantUserId);
        $catalogKeys = $this->premiumCatalog->keysForNiche(self::NURSERY_NICHE_KEY);

        if ($catalogKeys === []) {
            throw new InvalidArgumentException('لا توجد مزايا حضانة معرّفة.');
        }

        $filtered = $this->premiumCatalog->filterKeysForNiche(self::NURSERY_NICHE_KEY, $enabledKeys);

        $this->superAdminTenants->assertPremiumFeaturesAllowed($tenant, $filtered);
        $this->featureRegistry->syncCatalogKeys($tenantUserId, $catalogKeys, $filtered);
    }
}
