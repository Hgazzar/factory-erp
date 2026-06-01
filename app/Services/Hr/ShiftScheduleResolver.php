<?php

declare(strict_types=1);

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * يحوّل وردية الموظف (أو الإعدادات الافتراضية) إلى مواعيد فعلية ليوم عمل محدد.
 */
final class ShiftScheduleResolver
{
    public function forEmployee(Employee $employee, string $workDate): ShiftScheduleContext
    {
        if ($employee->shift_id) {
            $shift = Shift::withoutGlobalScopes()
                ->where('user_id', $employee->user_id)
                ->whereKey($employee->shift_id)
                ->first();

            if ($shift !== null) {
                return $this->fromShift($shift, $workDate);
            }
        }

        return $this->fromConfig($workDate);
    }

    public function fromShift(Shift $shift, string $workDate): ShiftScheduleContext
    {
        $tz = config('app.timezone');
        $start = $this->timeOnDate($shift->start_time, $workDate, $tz);
        $end = $this->timeOnDate($shift->end_time, $workDate, $tz);

        if ($shift->is_night || $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $grace = (int) ($shift->grace_minutes ?? 0);
        if ($grace <= 0) {
            $grace = (int) config('attendance.grace_minutes', 0);
        }

        return new ShiftScheduleContext((int) $shift->id, $start, $end, $grace);
    }

    private function fromConfig(string $workDate): ShiftScheduleContext
    {
        $tz = config('app.timezone');
        $start = Carbon::parse($workDate.' '.config('attendance.scheduled_start'), $tz);
        $hours = max(1, (int) config('attendance.hours_per_work_day', 8));
        $end = $start->copy()->addHours($hours);
        $grace = (int) config('attendance.grace_minutes', 0);

        return new ShiftScheduleContext(null, $start, $end, $grace);
    }

    private function timeOnDate(mixed $time, string $workDate, string $tz): Carbon
    {
        if ($time instanceof DateTimeInterface) {
            $timeStr = $time->format('H:i:s');
        } else {
            $timeStr = (string) $time;
            if (strlen($timeStr) === 5) {
                $timeStr .= ':00';
            }
        }

        return Carbon::parse($workDate.' '.$timeStr, $tz);
    }
}
