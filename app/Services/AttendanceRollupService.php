<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\Carbon;

final class AttendanceRollupService
{
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

        $tz = config('app.timezone');
        $scheduled = Carbon::parse($workDate.' '.config('attendance.scheduled_start'), $tz);
        $deadline = $scheduled->copy()->addMinutes((int) config('attendance.grace_minutes'));

        if ($checkIn->lessThanOrEqualTo($deadline)) {
            $status = Attendance::STATUS_PRESENT;
            $minutesLate = 0;
        } else {
            $status = Attendance::STATUS_LATE;
            $minutesLate = max(0, (int) round($scheduled->diffInMinutes($checkIn)));
        }

        $workHours = round(max(0, $checkIn->floatDiffInHours($checkOut)), 2);

        $employee = Employee::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->find($employeeId);

        $deductionAmount = $this->computeLateDeductionAmount($employee, $status, $minutesLate);

        Attendance::withoutGlobalScopes()->updateOrCreate(
            [
                'user_id' => $userId,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
            ],
            [
                'check_in_at' => $checkIn,
                'check_out_at' => $checkOut,
                'status' => $status,
                'minutes_late' => $minutesLate,
                'work_hours' => $workHours,
                'deduction_amount' => $deductionAmount,
            ]
        );
    }

    /**
     * قيمة الساعة = الراتب الأساسي ÷ 30 ÷ 8 | الخصم = (دقائق التأخير / 60) × قيمة الساعة.
     */
    private function computeLateDeductionAmount(?Employee $employee, string $status, int $minutesLate): ?string
    {
        if ($employee === null || $status !== Attendance::STATUS_LATE || $minutesLate <= 0) {
            return $this->decimalString(0.0);
        }

        if ($employee->base_salary === null || (float) $employee->base_salary <= 0) {
            return null;
        }

        $dMonth = max(1, (int) config('attendance.days_per_month_for_payroll', 30));
        $hDay = max(1, (int) config('attendance.hours_per_work_day', 8));
        $base = (float) $employee->base_salary;
        $hourly = $base / $dMonth / $hDay;
        $lateHours = $minutesLate / 60.0;
        $amount = round($hourly * $lateHours, 2);

        return $this->decimalString($amount);
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
