<?php

declare(strict_types=1);

namespace App\Services\Tenant;

/**
 * قراءة تعريفات النيشات من config/niches.php.
 */
final class NicheCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return config('niches.niches', []);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function exists(string $nicheKey): bool
    {
        return array_key_exists(strtolower(trim($nicheKey)), $this->all());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $nicheKey): ?array
    {
        $nicheKey = strtolower(trim($nicheKey));

        return $this->all()[$nicheKey] ?? null;
    }

    /**
     * @return list<string>
     */
    public function defaultModuleKeys(string $nicheKey): array
    {
        $niche = $this->find($nicheKey);

        if ($niche === null) {
            return [];
        }

        $modules = $niche['modules'] ?? [];

        return array_values(array_unique(array_merge(
            ['core'],
            array_map('strtolower', is_array($modules) ? $modules : [])
        )));
    }

    /**
     * @return list<string>
     */
    public function defaultPremiumFeatureKeys(string $nicheKey): array
    {
        $niche = $this->find($nicheKey);

        if ($niche === null) {
            return [];
        }

        $features = $niche['default_premium_features'] ?? [];

        return array_values(array_filter(array_map(
            static fn (string $k): string => strtolower(trim($k)),
            is_array($features) ? $features : []
        ), static fn (string $k): bool => $k !== ''));
    }

    /**
     * @return list<array{value: string, label: string, description?: string}>
     */
    public function selectOptions(): array
    {
        return collect($this->all())
            ->sortBy(fn (array $niche) => $niche['name_ar'] ?? $niche['key'] ?? '')
            ->map(fn (array $niche): array => [
                'value' => (string) ($niche['key'] ?? ''),
                'label' => (string) ($niche['name_ar'] ?? $niche['key'] ?? ''),
                'description' => (string) ($niche['description_ar'] ?? ''),
            ])
            ->values()
            ->all();
    }
}
