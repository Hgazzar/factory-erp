<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\NurseryShift;
use App\Models\Nursery\StaffAttendanceLog;
use Carbon\Carbon;

final class NurseryShiftAttendanceService
{
    public function graceMinutes(): int
    {
        return max(0, (int) config('nursery.shift_late_grace_minutes', 15));
    }

    public function isLateCheckIn(Employee $employee, StaffAttendanceLog $log): bool
    {
        $shiftId = (int) ($employee->nursery_shift_id ?? 0);

        if ($shiftId < 1 || $log->checked_in_at === null) {
            return false;
        }

        $shift = NurseryShift::query()
            ->where('user_id', $employee->user_id)
            ->whereKey($shiftId)
            ->where('is_active', true)
            ->first();

        if ($shift === null || $shift->start_time === null) {
            return false;
        }

        $start = Carbon::parse($log->attendance_date->format('Y-m-d').' '.$shift->start_time->format('H:i:s'));
        $checkIn = Carbon::parse($log->checked_in_at);

        return $checkIn->greaterThan($start->copy()->addMinutes($this->graceMinutes()));
    }

    public function lateDetail(Employee $employee, StaffAttendanceLog $log): ?string
    {
        if (! $this->isLateCheckIn($employee, $log)) {
            return null;
        }

        $shift = NurseryShift::query()->find($employee->nursery_shift_id);

        if ($shift === null) {
            return 'تأخير';
        }

        return 'تأخير (بعد '.$shift->start_time?->format('H:i').')';
    }
}
