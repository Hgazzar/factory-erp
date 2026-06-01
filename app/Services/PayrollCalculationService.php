<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollItem;
use App\Models\PaySlip;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * احتساب مكوّنات الراتب لموظف في فترة شهرية: أساسي، بدلات، حضور، إضافي، تأمين/ضريبة.
 */
final class PayrollCalculationService
{
    /**
     * @return array{
     *   slip: array<string, mixed>,
     *   lines: list<array{code:string,kind:string,label:?string,amount:float,sort:int}>
     * }
     */
    public function calculateForEmployee(
        Employee $employee,
        int $tenantUserId,
        int $year,
        int $month
    ): array {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $basic = round((float) ($employee->base_salary ?? 0), 2);
        $housing = round((float) ($employee->housing_allowance ?? 0), 2);
        $transport = round((float) ($employee->transport_allowance ?? 0), 2);
        $otherAllow = round((float) ($employee->other_allowance ?? 0), 2);
        $totalAllowances = round($housing + $transport + $otherAllow, 2);

        $attendanceBlock = $this->aggregateAttendance($tenantUserId, (int) $employee->id, $start, $end);
        $attendanceDeductions = round($attendanceBlock['deduction_amount'], 2);

        $hourly = $this->hourlyRateFromMonthly($employee, $basic);
        $overtimeFromRequests = $this->computeApprovedOvertimeRequests(
            $tenantUserId,
            (int) $employee->id,
            $start,
            $end,
            $hourly
        );
        $overtimeFromAttendance = $this->computeOvertimeFromAttendance(
            $tenantUserId,
            (int) $employee->id,
            $start,
            $end,
            $hourly
        );
        $overtimeBlock = [
            'hours' => round($overtimeFromRequests['hours'] + $overtimeFromAttendance['hours'], 2),
            'amount' => round($overtimeFromRequests['amount'] + $overtimeFromAttendance['amount'], 2),
        ];

        [$insurance, $tax] = $this->computeStatutory($employee, $basic, $totalAllowances, $attendanceDeductions);
        $insurance = round($insurance, 2);
        $tax = round($tax, 2);
        $statutory = round($insurance + $tax, 2);

        $gross = round($basic + $totalAllowances + $overtimeBlock['amount'], 2);
        $totalDeductions = round($attendanceDeductions + $statutory, 2);
        $net = round($gross - $totalDeductions, 2);

        $lines = [];
        $sort = 0;
        if ($basic > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_BASIC_SALARY, PaySlip::ITEM_KIND_EARNING, 'الراتب الأساسي', $basic, $sort++);
        }
        if ($housing > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_HOUSING_ALLOWANCE, PaySlip::ITEM_KIND_EARNING, 'بدل سكن', $housing, $sort++);
        }
        if ($transport > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_TRANSPORT_ALLOWANCE, PaySlip::ITEM_KIND_EARNING, 'بدل مواصلات', $transport, $sort++);
        }
        if ($otherAllow > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_OTHER_ALLOWANCE, PaySlip::ITEM_KIND_EARNING, 'بدلات أخرى', $otherAllow, $sort++);
        }
        if ($overtimeBlock['amount'] > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_OVERTIME, PaySlip::ITEM_KIND_EARNING, 'بدل وقت إضافي', $overtimeBlock['amount'], $sort++);
        }
        if ($attendanceDeductions > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_ATTENDANCE_DEDUCTION, PaySlip::ITEM_KIND_DEDUCTION, 'خصومات حضور وتأخير', $attendanceDeductions, $sort++);
        }
        if ($insurance > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_INSURANCE, PaySlip::ITEM_KIND_DEDUCTION, 'تأمينات', $insurance, $sort++);
        }
        if ($tax > 0) {
            $lines[] = $this->makeLine(PayrollItem::CODE_TAX, PaySlip::ITEM_KIND_DEDUCTION, 'ضريبة', $tax, $sort++);
        }

        $slip = [
            'employee_id' => $employee->id,
            'basic_salary' => $basic,
            'total_allowances' => $totalAllowances,
            'attendance_deductions' => $attendanceDeductions,
            'statutory_deductions' => $statutory,
            'total_deductions' => $totalDeductions,
            'net_salary' => $net,
            'overtime_hours' => $overtimeBlock['hours'],
            'overtime_amount' => $overtimeBlock['amount'],
            'absence_hours' => $attendanceBlock['absence_hours'],
            'late_hours' => $attendanceBlock['late_hours'],
            'early_departure_hours' => $attendanceBlock['early_departure_hours'],
        ];

        return ['slip' => $slip, 'lines' => $lines];
    }

    /**
     * @return array{deduction_amount: float, absence_hours: float, late_hours: float, early_departure_hours: float}
     */
    private function aggregateAttendance(
        int $tenantUserId,
        int $employeeId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $standardDay = (float) config('hr.payroll.standard_daily_hours', 8);

        $rows = Attendance::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('employee_id', $employeeId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get(['status', 'deduction_amount', 'minutes_late', 'minutes_early_departure', 'work_hours']);

        $deduction = 0.0;
        $absenceHours = 0.0;
        $lateHours = 0.0;
        $earlyHours = 0.0;

        foreach ($rows as $row) {
            $deduction += (float) ($row->deduction_amount ?? 0);
            if (($row->status ?? '') === Attendance::STATUS_ABSENT) {
                $absenceHours += $standardDay;
            }
            $lateHours += ((int) ($row->minutes_late ?? 0)) / 60;
            $earlyHours += ((int) ($row->minutes_early_departure ?? 0)) / 60;
        }

        return [
            'deduction_amount' => $deduction,
            'absence_hours' => round($absenceHours, 2),
            'late_hours' => round($lateHours, 2),
            'early_departure_hours' => round($earlyHours, 2),
        ];
    }

    /**
     * طلبات عمل إضافي معتمدة ضمن الفترة: ساعات × سعر الساعة × معامل الطلب.
     *
     * @return array{hours: float, amount: float}
     */
    private function computeApprovedOvertimeRequests(
        int $tenantUserId,
        int $employeeId,
        CarbonInterface $start,
        CarbonInterface $end,
        float $hourlyRate
    ): array {
        $rows = OvertimeRequest::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('employee_id', $employeeId)
            ->where('status', OvertimeRequest::STATUS_APPROVED)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get(['hours', 'rate_multiplier']);

        $hours = 0.0;
        $amount = 0.0;
        foreach ($rows as $row) {
            $h = (float) ($row->hours ?? 0);
            $mult = (float) ($row->rate_multiplier ?? 1.5);
            $hours += $h;
            if ($hourlyRate > 0 && $h > 0) {
                $amount += $h * $hourlyRate * $mult;
            }
        }

        return [
            'hours' => round($hours, 2),
            'amount' => round($amount, 2),
        ];
    }

    /**
     * @return array{hours: float, amount: float}
     */
    private function computeOvertimeFromAttendance(
        int $tenantUserId,
        int $employeeId,
        CarbonInterface $start,
        CarbonInterface $end,
        float $hourlyRate
    ): array {
        if ($hourlyRate <= 0) {
            return ['hours' => 0.0, 'amount' => 0.0];
        }

        $standardDay = (float) config('hr.payroll.standard_daily_hours', 8);
        $mult = (float) config('hr.payroll.overtime_hourly_rate_multiplier', 1.5);

        $rows = Attendance::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('employee_id', $employeeId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('work_hours')
            ->get(['work_hours']);

        $otHours = 0.0;
        foreach ($rows as $row) {
            $wh = (float) ($row->work_hours ?? 0);
            if ($wh > $standardDay) {
                $otHours += $wh - $standardDay;
            }
        }

        $otHours = round($otHours, 2);
        $amount = round($otHours * $hourlyRate * $mult, 2);

        return ['hours' => $otHours, 'amount' => $amount];
    }

    private function hourlyRateFromMonthly(Employee $employee, float $basicMonthly): float
    {
        if (($employee->salary_type ?? Employee::SALARY_MONTHLY) !== Employee::SALARY_MONTHLY) {
            return 0.0;
        }
        $days = (float) config('hr.payroll.standard_monthly_days', 30);
        $hours = (float) config('hr.payroll.standard_daily_hours', 8);
        if ($days <= 0 || $hours <= 0) {
            return 0.0;
        }

        return $basicMonthly / ($days * $hours);
    }

    /**
     * @return array{0: float, 1: float} [insurance, tax]
     */
    private function computeStatutory(Employee $employee, float $basic, float $allowances, float $attendanceDed): array
    {
        $fixedIns = (float) ($employee->fixed_insurance_deduction ?? 0);
        $fixedTax = (float) ($employee->fixed_tax_deduction ?? 0);

        $insMode = (string) config('hr.payroll.insurance_calculation', 'fixed_from_employee');
        $taxMode = (string) config('hr.payroll.tax_calculation', 'fixed_from_employee');

        $grossish = max(0.0, $basic + $allowances - $attendanceDed);

        $insurance = $fixedIns;
        if ($insMode === 'percent_of_gross') {
            $p = (float) config('hr.payroll.insurance_percent', 0);
            $insurance = round($grossish * ($p / 100), 2);
        }

        $tax = $fixedTax;
        if ($taxMode === 'percent_of_gross') {
            $p = (float) config('hr.payroll.tax_percent', 0);
            $tax = round($grossish * ($p / 100), 2);
        }

        return [$insurance, $tax];
    }

    /**
     * @return array{code:string,kind:string,label:?string,amount:float,sort:int}
     */
    private function makeLine(string $code, string $kind, ?string $label, float $amount, int $sort): array
    {
        return [
            'code' => $code,
            'kind' => $kind,
            'label' => $label,
            'amount' => $amount,
            'sort' => $sort,
        ];
    }
}
