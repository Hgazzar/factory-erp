<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\TenantFeature;
use Illuminate\Support\Facades\Cache;

final class TenantFeatureRegistry
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function isEnabled(string $featureKey, ?int $tenantId = null): bool
    {
        $featureKey = strtolower(trim($featureKey));
        if ($featureKey === '') {
            return false;
        }

        $tenantId ??= $this->tenantContext->resolveTenantUserId();
        if ($tenantId === null) {
            return false;
        }

        $enabled = $this->enabledKeysForTenant($tenantId);

        if (in_array($featureKey, $enabled, true)) {
            return true;
        }

        $legacy = $this->legacyFeatureAliases()[$featureKey] ?? null;

        return $legacy !== null && in_array($legacy, $enabled, true);
    }

    /**
     * @return array<string, string>
     */
    private function legacyFeatureAliases(): array
    {
        return [
            'nursery_portal' => 'nursery_parent_portal',
            'nursery_parent_portal' => 'nursery_portal',
        ];
    }

    /**
     * @return list<string>
     */
    public function enabledKeysForTenant(int $tenantId): array
    {
        if ($tenantId < 1) {
            return [];
        }

        return Cache::remember(
            $this->cacheKey($tenantId),
            self::CACHE_TTL_SECONDS,
            function () use ($tenantId): array {
                return TenantFeature::query()
                    ->where('tenant_id', $tenantId)
                    ->orderBy('feature_key')
                    ->pluck('feature_key')
                    ->map(fn (string $k) => strtolower(trim($k)))
                    ->filter(fn (string $k) => $k !== '')
                    ->values()
                    ->all();
            }
        );
    }

    public function forgetCache(int $tenantId): void
    {
        if ($tenantId > 0) {
            Cache::forget($this->cacheKey($tenantId));
        }
    }

    /**
     * مزامنة مفاتيح كتالوج نيش معيّن فقط — لا يمسّ مفاتيح ميزات أخرى.
     *
     * @param  list<string>  $catalogKeys
     * @param  list<string>  $enabledKeys
     */
    public function syncCatalogKeys(int $tenantId, array $catalogKeys, array $enabledKeys): void
    {
        if ($tenantId < 1) {
            return;
        }

        $catalogKeys = array_values(array_unique(array_map(
            static fn (string $k): string => strtolower(trim($k)),
            $catalogKeys
        )));
        $catalogKeys = array_values(array_filter($catalogKeys, static fn (string $k): bool => $k !== ''));

        $enabledKeys = array_values(array_intersect(
            array_map(static fn (string $k): string => strtolower(trim($k)), $enabledKeys),
            $catalogKeys
        ));

        foreach ($catalogKeys as $key) {
            if (in_array($key, $enabledKeys, true)) {
                TenantFeature::query()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'feature_key' => $key],
                    []
                );
            } else {
                TenantFeature::query()
                    ->where('tenant_id', $tenantId)
                    ->where('feature_key', $key)
                    ->delete();
            }
        }

        $this->forgetCache($tenantId);
    }

    private function cacheKey(int $tenantId): string
    {
        return "tenant_features:{$tenantId}";
    }
}
