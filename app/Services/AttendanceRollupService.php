<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\Hr\ShiftScheduleResolver;
use Carbon\Carbon;

final class AttendanceRollupService
{
    public function __construct(
        private readonly ShiftScheduleResolver $shiftSchedule,
    ) {}

    /**
     * يجمع ختمات attendance_logs ليوم وموظف محددين إلى صف واحد في attendances (أقدم = حضور، أحدث = انصراف).
     */
    public function rollupEmployeeDay(int $userId, int $employeeId, string $workDate): void
    {
        $logs = AttendanceLog::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('employee_id', $employeeId)
            ->whereDate('logged_at', $workDate)
            ->orderBy('logged_at')
            ->orderBy('id')
            ->get();

        if ($logs->isEmpty()) {
            Attendance::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereDate('work_date', $workDate)
                ->delete();

            return;
        }

        $checkIn = $logs->first()->logged_at;
        $checkOut = $logs->last()->logged_at;

        $employee = Employee::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->find($employeeId);

        $schedule = $this->shiftSchedule->forEmployee(
            $employee ?? new Employee(['user_id' => $userId]),
            $workDate
        );

        $checkInDeadline = $schedule->scheduledStart->copy()->addMinutes($schedule->graceMinutes);

        if ($checkIn->lessThanOrEqualTo($checkInDeadline)) {
            $status = Attendance::STATUS_PRESENT;
            $minutesLate = 0;
        } else {
            $status = Attendance::STATUS_LATE;
            $minutesLate = max(0, (int) round($schedule->scheduledStart->diffInMinutes($checkIn)));
        }

        $earliestAllowedOut = $schedule->scheduledEnd->copy()->subMinutes($schedule->graceMinutes);
        $minutesEarly = 0;
        if ($checkOut->lessThan($earliestAllowedOut)) {
            $minutesEarly = max(0, (int) round($checkOut->diffInMinutes($schedule->scheduledEnd)));
        }

        $workHours = round(max(0, $checkIn->floatDiffInHours($checkOut)), 2);

        $deductionAmount = $this->computeAttendanceDeduction($employee, $status, $minutesLate, $minutesEarly);

        Attendance::withoutGlobalScopes()->updateOrCreate(
            [
                'user_id' => $userId,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
            ],
            [
                'shift_id' => $schedule->shiftId,
                'check_in_at' => $checkIn,
                'check_out_at' => $checkOut,
                'status' => $status,
                'minutes_late' => $minutesLate,
                'minutes_early_departure' => $minutesEarly,
                'work_hours' => $workHours,
                'deduction_amount' => $deductionAmount,
            ]
        );
    }

    private function computeAttendanceDeduction(
        ?Employee $employee,
        string $status,
        int $minutesLate,
        int $minutesEarly,
    ): ?string {
        if ($employee === null || ($minutesLate <= 0 && $minutesEarly <= 0)) {
            return $this->decimalString(0.0);
        }

        if ($employee->base_salary === null || (float) $employee->base_salary <= 0) {
            return null;
        }

        $policy = $employee->attendance_policy ?? Employee::ATTENDANCE_POLICY_NONE;
        $base = (float) $employee->base_salary;
        $dMonth = max(1, (int) config('attendance.days_per_month_for_payroll', 30));
        $hDay = max(1, (int) config('attendance.hours_per_work_day', 8));
        $hourly = $base / $dMonth / $hDay;

        if ($policy === Employee::ATTENDANCE_POLICY_DAY_FOR_DAY) {
            return $this->decimalString(round($base / $dMonth, 2));
        }

        if ($policy === Employee::ATTENDANCE_POLICY_HOUR_FOR_HOUR) {
            $hours = ($minutesLate + $minutesEarly) / 60.0;

            return $this->decimalString(round($hourly * $hours, 2));
        }

        if ($status === Attendance::STATUS_LATE && $minutesLate > 0) {
            return $this->decimalString(round($hourly * ($minutesLate / 60.0), 2));
        }

        return $this->decimalString(0.0);
    }

    private function decimalString(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * @param  iterable<int, array{employee_id: int, work_date: string}>  $pairs
     */
    public function rollupPairs(int $userId, iterable $pairs): void
    {
        $seen = [];
        foreach ($pairs as $pair) {
            $key = $pair['employee_id'].'|'.$pair['work_date'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $this->rollupEmployeeDay($userId, (int) $pair['employee_id'], (string) $pair['work_date']);
        }
    }

    /**
     * إعادة تجميع كل الأيام التي لها سجلات ختمات للمستأجر (مثلاً بعد استيراد كبير).
     */
    public function rollupAllDistinctDaysForUser(int $userId): void
    {
        $pairs = AttendanceLog::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNotNull('employee_id')
            ->get()
            ->map(fn (AttendanceLog $l) => [
                'employee_id' => (int) $l->employee_id,
                'work_date' => $l->logged_at->copy()->timezone(config('app.timezone'))->toDateString(),
            ])
            ->unique(fn (array $p) => $p['employee_id'].'|'.$p['work_date'])
            ->values();

        $this->rollupPairs($userId, $pairs);
    }
}
