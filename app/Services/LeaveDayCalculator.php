<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class LeaveDayCalculator
{
    /**
     * عدد أيام العمل (شمولي) من البداية إلى النهاية مع استبعاد أيام نهاية الأسبوع المعرّفة في الإعدادات.
     */
    public static function countWorkingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        $excluded = config('hr.leave_excluded_iso_weekdays', [5, 6]);
        if (! is_array($excluded)) {
            $excluded = [5, 6];
        }

        $c = 0;
        for ($d = $start->copy()->startOfDay(); $d->lte($end->copy()->startOfDay()); $d->addDay()) {
            if (! in_array((int) $d->dayOfWeekIso, $excluded, true)) {
                $c++;
            }
        }

        return $c;
    }

    /**
     * تكرار التاريخ (فقط أيام العمل) لإثبات سجلات الحضور.
     *
     * @return list<string> تواريخ work_date (Y-m-d)
     */
    public static function workingDatesBetween(CarbonInterface $start, CarbonInterface $end): array
    {
        if ($end->lt($start)) {
            return [];
        }

        $excluded = config('hr.leave_excluded_iso_weekdays', [5, 6]);
        if (! is_array($excluded)) {
            $excluded = [5, 6];
        }

        $out = [];
        for ($d = $start->copy()->startOfDay(); $d->lte($end->copy()->startOfDay()); $d->addDay()) {
            if (! in_array((int) $d->dayOfWeekIso, $excluded, true)) {
                $out[] = $d->toDateString();
            }
        }

        return $out;
    }

    public static function parseAndCount(string|Carbon $start, string|Carbon $end): int
    {
        $a = $start instanceof Carbon ? $start->copy()->startOfDay() : Carbon::parse($start)->startOfDay();
        $b = $end instanceof Carbon ? $end->copy()->startOfDay() : Carbon::parse($end)->startOfDay();

        return self::countWorkingDays($a, $b);
    }
}
