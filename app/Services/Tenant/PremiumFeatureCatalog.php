<?php

declare(strict_types=1);

namespace App\Services\Tenant;

/**
 * كتالوج المزايا البريميوم المرتبطة بالنيش.
 */
final class PremiumFeatureCatalog
{
    /**
     * @return list<string>
     */
    public function keysForNiche(?string $nicheKey): array
    {
        $definitions = $this->definitionsForNiche($nicheKey);

        return array_values(array_map(
            static fn (array $def): string => (string) $def['key'],
            $definitions
        ));
    }

    /**
     * @return list<array{key: string, name_ar: string, description_ar: string, hint: string, group?: string}>
     */
    public function definitionsForNiche(?string $nicheKey): array
    {
        $nicheKey = $nicheKey !== null ? strtolower(trim($nicheKey)) : '';

        if ($nicheKey === 'full_erp') {
            return $this->definitionsForFullErp();
        }

        $byNiche = config('premium_features.niches', []);

        if ($nicheKey === '' || ! isset($byNiche[$nicheKey])) {
            return [];
        }

        return array_values($byNiche[$nicheKey]);
    }

    public function nicheHasPremiumCatalog(?string $nicheKey): bool
    {
        return $this->definitionsForNiche($nicheKey) !== [];
    }

    /**
     * @param  list<string>  $enabledKeys
     * @return list<string>
     */
    public function filterKeysForNiche(?string $nicheKey, array $enabledKeys): array
    {
        $allowed = $this->keysForNiche($nicheKey);
        $enabledKeys = array_map(static fn (string $k): string => strtolower(trim($k)), $enabledKeys);

        return array_values(array_intersect($allowed, $enabledKeys));
    }

    /**
     * @return list<array{key: string, name_ar: string, description_ar: string, hint: string, group?: string}>
     */
    private function definitionsForFullErp(): array
    {
        $nicheKeys = config('premium_features.full_erp_niche_keys', []);
        $nichesConfig = config('niches.niches', []);
        $byNiche = config('premium_features.niches', []);
        $out = [];

        foreach ($nicheKeys as $groupKey) {
            if (! isset($byNiche[$groupKey])) {
                continue;
            }

            $groupLabel = (string) ($nichesConfig[$groupKey]['name_ar'] ?? $groupKey);

            foreach ($byNiche[$groupKey] as $def) {
                $out[] = array_merge($def, ['group' => $groupLabel]);
            }
        }

        return $out;
    }
}
