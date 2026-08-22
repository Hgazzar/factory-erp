<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\NurseryShift;
use App\Models\Nursery\StaffAttendanceLog;
use Carbon\Carbon;

final class NurseryShiftAttendanceService
{
    /** @var array<string, array{start: ?Carbon, end: ?Carbon}|null> */
    private array $childWindowCache = [];

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

    /**
     * Nursery-day window for children: earliest active shift start, latest active shift end.
     * Returns null when the tenant has no active shift times — do not invent late/early.
     *
     * @return array{start: ?Carbon, end: ?Carbon}|null
     */
    public function childOperatingWindow(int $tenantUserId, Carbon|string $date): ?array
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        $cacheKey = $tenantUserId.'|'.$dateString;

        if (array_key_exists($cacheKey, $this->childWindowCache)) {
            return $this->childWindowCache[$cacheKey];
        }

        $shifts = NurseryShift::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->get();

        $starts = [];
        $ends = [];

        foreach ($shifts as $shift) {
            if ($shift->start_time !== null) {
                $starts[] = Carbon::parse($dateString.' '.$shift->start_time->format('H:i:s'));
            }
            if ($shift->end_time !== null) {
                $ends[] = Carbon::parse($dateString.' '.$shift->end_time->format('H:i:s'));
            }
        }

        if ($starts === [] && $ends === []) {
            return $this->childWindowCache[$cacheKey] = null;
        }

        usort($starts, fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);
        usort($ends, fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);

        return $this->childWindowCache[$cacheKey] = [
            'start' => $starts[0] ?? null,
            'end' => $ends !== [] ? $ends[array_key_last($ends)] : null,
        ];
    }

    public function isChildLate(int $tenantUserId, AttendanceLog $log): bool
    {
        if ($log->checked_in_at === null) {
            return false;
        }

        $date = $log->attendance_date instanceof Carbon
            ? $log->attendance_date
            : Carbon::parse((string) $log->attendance_date);

        $window = $this->childOperatingWindow($tenantUserId, $date);

        if ($window === null || $window['start'] === null) {
            return false;
        }

        $checkIn = Carbon::parse($log->checked_in_at);

        return $checkIn->greaterThan($window['start']->copy()->addMinutes($this->graceMinutes()));
    }

    public function isChildEarlyDeparture(int $tenantUserId, AttendanceLog $log): bool
    {
        if ($log->checked_out_at === null) {
            return false;
        }

        $date = $log->attendance_date instanceof Carbon
            ? $log->attendance_date
            : Carbon::parse((string) $log->attendance_date);

        $window = $this->childOperatingWindow($tenantUserId, $date);

        if ($window === null || $window['end'] === null) {
            return false;
        }

        return Carbon::parse($log->checked_out_at)->lt($window['end']);
    }
}
