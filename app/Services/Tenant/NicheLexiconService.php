<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\TenantProfile;
use Illuminate\Support\Facades\Cache;

/**
 * يدمج القاموس الافتراضي + overrides النيش + overrides المستأجر.
 */
final class NicheLexiconService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function resolveNicheKey(?int $tenantUserId = null): ?string
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId === null) {
            return null;
        }

        $profile = $this->profileForTenant($tenantUserId);

        return $profile?->niche_key;
    }

    /**
     * @return array<string, string>
     */
    public function lexiconForTenant(?int $tenantUserId = null): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId === null) {
            return $this->defaults();
        }

        return Cache::remember(
            $this->cacheKey($tenantUserId),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildLexicon($tenantUserId)
        );
    }

    public function label(string $termKey, ?int $tenantUserId = null, ?string $fallback = null): string
    {
        $termKey = strtolower(trim($termKey));
        $lexicon = $this->lexiconForTenant($tenantUserId);

        if (isset($lexicon[$termKey]) && $lexicon[$termKey] !== '') {
            return $lexicon[$termKey];
        }

        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        return $this->defaults()[$termKey] ?? $termKey;
    }

    public function moduleLabel(string $moduleKey, ?int $tenantUserId = null): string
    {
        $moduleKey = strtolower(trim($moduleKey));
        $configLabel = (string) (config("modules.modules.{$moduleKey}.name_ar") ?? $moduleKey);

        return $this->label("modules.{$moduleKey}", $tenantUserId, $configLabel);
    }

    public function forgetCache(?int $tenantUserId = null): void
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId !== null) {
            Cache::forget($this->cacheKey($tenantUserId));
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildLexicon(int $tenantUserId): array
    {
        $merged = $this->defaults();

        $profile = $this->profileForTenant($tenantUserId);

        if ($profile === null) {
            return $merged;
        }

        $nicheKey = strtolower((string) $profile->niche_key);
        $nicheOverrides = config("lexicon.niche_overrides.{$nicheKey}", []);

        if (is_array($nicheOverrides)) {
            foreach ($nicheOverrides as $key => $value) {
                if (is_string($key) && is_string($value) && $value !== '') {
                    $merged[strtolower($key)] = $value;
                }
            }
        }

        $tenantOverrides = $profile->lexicon_overrides ?? [];

        if (is_array($tenantOverrides)) {
            foreach ($tenantOverrides as $key => $value) {
                if (is_string($key) && is_string($value) && $value !== '') {
                    $merged[strtolower($key)] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * @return array<string, string>
     */
    private function defaults(): array
    {
        $defaults = config('lexicon.defaults', []);

        return is_array($defaults) ? $defaults : [];
    }

    private function profileForTenant(int $tenantUserId): ?TenantProfile
    {
        return TenantProfile::forTenantUser($tenantUserId);
    }

    private function cacheKey(int $tenantUserId): string
    {
        return "akwad.tenant_lexicon.{$tenantUserId}";
    }
}
