<?php

declare(strict_types=1);

namespace App\Support;

final class NurseryClassroomAgeGroups
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return config('nursery.age_groups', []);
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @param  list<string>|null  $keys
     */
    public static function labelsFor(?array $keys): string
    {
        if ($keys === null || $keys === []) {
            return '—';
        }

        $labels = self::labels();

        return collect($keys)
            ->map(fn (string $key): string => $labels[$key] ?? $key)
            ->implode('، ');
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    public static function normalizeSelection(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $allowed = self::keys();

        return array_values(array_unique(array_filter(
            array_map('strval', $input),
            fn (string $key): bool => in_array($key, $allowed, true)
        )));
    }
}
