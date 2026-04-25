<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PaySlip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class PayrollGenerationService
{
    public function __construct(
        private readonly PayrollCalculationService $calculator
    ) {}

    /**
     * ينشئ دورة رواتب للشهر/السنة ويملأ قسائم الموظفين النشطيين وبنود التفصيل.
     *
     * @param  array{name?: string|null, department_id?: int|null, period_start?: string|null, period_end?: string|null}  $meta
     */
    public function generate(
        int $tenantUserId,
        int $year,
        int $month,
        ?string $notes = null,
        ?string $paymentDateYmd = null,
        array $meta = []
    ): Payroll {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        return DB::transaction(function () use ($tenantUserId, $year, $month, $notes, $paymentDateYmd, $periodEnd, $meta) {
            $payroll = Payroll::query()->create([
                'user_id' => $tenantUserId,
                'name' => $meta['name'] ?? null,
                'department_id' => $meta['department_id'] ?? null,
                'period_start' => $meta['period_start'] ?? null,
                'period_end' => $meta['period_end'] ?? null,
                'month' => $month,
                'year' => $year,
                'payment_date' => $paymentDateYmd ?: $periodEnd->toDateString(),
                'employees_count' => 0,
                'status' => Payroll::STATUS_DRAFT,
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_amount' => 0,
                'notes' => $notes,
            ]);

            $employeesQuery = Employee::query()
                ->where('user_id', $tenantUserId)
                ->where('status', 'active');

            $deptId = isset($meta['department_id']) ? (int) $meta['department_id'] : 0;
            if ($deptId > 0) {
                $employeesQuery->where('department_id', $deptId);
            }

            $employees = $employeesQuery->orderBy('name')->get();

            $sumGross = 0.0;
            $sumDed = 0.0;
            $sumNet = 0.0;

            foreach ($employees as $employee) {
                $block = $this->calculator->calculateForEmployee($employee, $tenantUserId, $year, $month);
                $slipRow = $block['slip'];

                $slip = PaySlip::query()->create(array_merge($slipRow, [
                    'payroll_cycle_id' => $payroll->id,
                ]));

                foreach ($block['lines'] as $line) {
                    PayrollItem::query()->create([
                        'pay_slip_id' => $slip->id,
                        'item_code' => $line['code'],
                        'item_kind' => $line['kind'],
                        'label' => $line['label'],
                        'amount' => $line['amount'],
                        'sort_order' => $line['sort'],
                    ]);
                }

                $gross = (float) $slipRow['basic_salary'] + (float) $slipRow['total_allowances'] + (float) $slipRow['overtime_amount'];
                $sumGross += $gross;
                $sumDed += (float) $slipRow['total_deductions'];
                $sumNet += (float) $slipRow['net_salary'];
            }

            $payroll->update([
                'employees_count' => $employees->count(),
                'total_gross' => round($sumGross, 2),
                'total_deductions' => round($sumDed, 2),
                'total_amount' => round($sumNet, 2),
            ]);

            return $payroll->fresh(['paySlips.items']);
        });
    }
}
