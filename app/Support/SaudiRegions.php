<?php

declare(strict_types=1);

namespace App\Support;

final class SaudiRegions
{
    /**
     * @return array<string, array{name_ar: string, cities: list<string>}>
     */
    public static function regions(): array
    {
        return config('saudi_regions.regions', []);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function regionSelectOptions(): array
    {
        return collect(self::regions())
            ->map(fn (array $meta, string $key): array => [
                'value' => $key,
                'label' => (string) ($meta['name_ar'] ?? $key),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function citySelectOptions(?string $regionKey): array
    {
        $regionKey = strtolower(trim((string) $regionKey));
        $cities = self::regions()[$regionKey]['cities'] ?? [];

        return collect($cities)
            ->map(fn (string $city): array => ['value' => $city, 'label' => $city])
            ->values()
            ->all();
    }

    public static function regionLabel(?string $regionKey): ?string
    {
        $regionKey = strtolower(trim((string) $regionKey));
        if ($regionKey === '') {
            return null;
        }

        return self::regions()[$regionKey]['name_ar'] ?? null;
    }
}
