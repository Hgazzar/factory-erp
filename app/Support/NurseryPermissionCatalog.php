<?php

declare(strict_types=1);

namespace App\Support;

final class NurseryPermissionCatalog
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return config('nursery_permissions.groups', []);
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            foreach ($group['permissions'] ?? [] as $key => $_label) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    public static function normalize(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $allowed = self::allKeys();

        return array_values(array_unique(array_filter(
            array_map('strval', $input),
            fn (string $key): bool => in_array($key, $allowed, true)
        )));
    }

    /**
     * @return list<string>
     */
    public static function templateForRole(?string $nurseryRole): array
    {
        $role = strtolower(trim((string) $nurseryRole));
        $templates = config('nursery_permissions.role_templates', []);

        return self::normalize($templates[$role] ?? []);
    }

    /**
     * @return list<array{key: string, group: string, group_label: string, label: string}>
     */
    public static function flatList(): array
    {
        $rows = [];
        foreach (self::groups() as $groupKey => $group) {
            foreach ($group['permissions'] ?? [] as $permKey => $label) {
                $rows[] = [
                    'key' => (string) $permKey,
                    'group' => (string) $groupKey,
                    'group_label' => (string) ($group['label'] ?? $groupKey),
                    'label' => (string) $label,
                ];
            }
        }

        return $rows;
    }
}
