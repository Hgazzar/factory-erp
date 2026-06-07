<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Nursery\AttendanceWeekdaySetting;

final class NurseryWeekdays
{
    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }

    /**
     * @param  mixed  $input
     * @return list<int>
     */
    public static function normalize(mixed $input): array
    {
        if (! is_array($input)) {
            return AttendanceWeekdaySetting::defaultWeekdays();
        }

        $days = [];
        foreach ($input as $day) {
            $d = (int) $day;
            if ($d >= 0 && $d <= 6) {
                $days[] = $d;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days !== [] ? $days : AttendanceWeekdaySetting::defaultWeekdays();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function selectOptions(): array
    {
        return collect(self::labels())
            ->map(fn (string $label, int $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();
    }
}
