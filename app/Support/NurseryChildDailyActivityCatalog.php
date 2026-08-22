<?php

declare(strict_types=1);

namespace App\Support;

final class NurseryChildDailyActivityCatalog
{
    public const TYPE_MEAL = 'meal';

    public const TYPE_NAP = 'nap';

    public const TYPE_DIAPER = 'diaper';

    public const TYPE_TOILET = 'toilet';

    public const TYPE_MOOD = 'mood';

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_MEDICATION = 'medication';

    public const TYPE_NOTE = 'note';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function types(): array
    {
        /** @var array<string, array<string, mixed>> $types */
        $types = config('nursery.daily_activities.types', []);

        return $types;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::types());
    }

    public static function label(string $type): string
    {
        return (string) (self::types()[$type]['label'] ?? $type);
    }

    public static function defaultParentVisible(string $type): bool
    {
        return (bool) (self::types()[$type]['parent_visible'] ?? false);
    }

    /**
     * @return array<string, string>
     */
    public static function options(string $type, string $field): array
    {
        $options = self::types()[$type]['options'][$field] ?? [];

        return is_array($options) ? $options : [];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function selectOptions(string $type, string $field): array
    {
        $out = [];
        foreach (self::options($type, $field) as $value => $label) {
            $out[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return $out;
    }

    public static function optionLabel(string $type, string $field, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) (self::options($type, $field)[$value] ?? $value);
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::keys(), true);
    }
}
