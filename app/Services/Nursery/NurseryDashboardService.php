<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Unit;

final class NurseryDashboardService
{
    public const PERIOD_TODAY = 'today';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_YEAR = 'year';

    public const PERIOD_ALL = 'all';

    public function __construct(
        private readonly NurseryAttendanceService $attendance,
    ) {}

    /**
     * @return array{
     *     children: int,
     *     staff: int,
     *     classrooms: int,
     *     classrooms_total: int,
     *     units: int,
     *     units_total: int,
     *     activities: int
     * }
     */
    public function overviewStats(int $tenantUserId): array
    {
        return [
            'children' => Child::query()
                ->where('user_id', $tenantUserId)
                ->where('status', Child::STATUS_ACTIVE)
                ->count(),
            'staff' => Employee::query()
                ->where('user_id', $tenantUserId)
                ->where('status', 'active')
                ->count(),
            'classrooms' => Classroom::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->count(),
            'classrooms_total' => Classroom::query()
                ->where('user_id', $tenantUserId)
                ->count(),
            'units' => Unit::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->count(),
            'units_total' => Unit::query()
                ->where('user_id', $tenantUserId)
                ->count(),
            'activities' => CalendarEntry::query()
                ->where('user_id', $tenantUserId)
                ->where('starts_at', '>=', now()->startOfWeek())
                ->where('starts_at', '<=', now()->endOfWeek())
                ->count(),
        ];
    }

    /**
     * Share of whole as 0–100 for spark visuals.
     */
    public function sharePercent(int|float $part, int|float $whole): float
    {
        $whole = (float) $whole;
        if ($whole <= 0.0) {
            return 0.0;
        }

        return round(min(100, max(0, ((float) $part / $whole) * 100)), 1);
    }

    /**
     * Arrow direction from a share. When $higherIsGood is false (e.g. unpaid),
     * a high share trends "down" (attention).
     *
     * @return 'up'|'down'|'flat'
     */
    public function trendFromShare(float $percent, bool $higherIsGood = true): string
    {
        if ($percent <= 0.0) {
            return 'flat';
        }

        if ($percent < 33.0) {
            return $higherIsGood ? 'down' : 'up';
        }

        if ($percent < 67.0) {
            return 'flat';
        }

        return $higherIsGood ? 'up' : 'down';
    }

    /**
     * @return array{percent: float, trend: string}
     */
    public function sparkMeta(int|float $part, int|float $whole, bool $higherIsGood = true): array
    {
        $percent = $this->sharePercent($part, $whole);

        return [
            'percent' => $percent,
            'trend' => $this->trendFromShare($percent, $higherIsGood),
        ];
    }

    /**
     * Count/amount cards with no natural denominator.
     *
     * @return array{percent: float, trend: string}
     */
    public function existenceSparkMeta(int|float $value, bool $higherIsGood = true): array
    {
        $value = (float) $value;
        if ($value == 0.0) {
            return ['percent' => 0.0, 'trend' => 'flat'];
        }

        return [
            'percent' => 100.0,
            'trend' => $higherIsGood ? 'up' : 'down',
        ];
    }

    /**
     * Signed totals (net / profit): fill when non-zero; arrow follows sign.
     *
     * @return array{percent: float, trend: string}
     */
    public function signedSparkMeta(float $value, ?float $reference = null): array
    {
        if ($value == 0.0) {
            return ['percent' => 0.0, 'trend' => 'flat'];
        }

        $ref = $reference !== null && abs($reference) > 0.0
            ? abs($reference)
            : abs($value);

        $percent = $this->sharePercent(abs($value), $ref);

        return [
            'percent' => max(8.0, $percent),
            'trend' => $value > 0 ? 'up' : 'down',
        ];
    }

    /**
     * Standard list KPIs: total / active / archived.
     *
     * @param  array{total?: int, active?: int, archived?: int}  $listStats
     * @return array{total: array{percent: float, trend: string}, active: array{percent: float, trend: string}, archived: array{percent: float, trend: string}}
     */
    public function listSparkMeta(array $listStats): array
    {
        $total = (int) ($listStats['total'] ?? 0);

        return [
            'total' => $this->existenceSparkMeta($total),
            'active' => $this->sparkMeta((int) ($listStats['active'] ?? 0), $total, true),
            'archived' => $this->sparkMeta((int) ($listStats['archived'] ?? 0), $total, false),
        ];
    }

    /**
     * @param  array{total?: int, portal_active?: int, logged_in?: int}  $listStats
     * @return array{total: array{percent: float, trend: string}, portal_active: array{percent: float, trend: string}, logged_in: array{percent: float, trend: string}}
     */
    public function guardiansSparkMeta(array $listStats): array
    {
        $total = (int) ($listStats['total'] ?? 0);

        return [
            'total' => $this->existenceSparkMeta($total),
            'portal_active' => $this->sparkMeta((int) ($listStats['portal_active'] ?? 0), $total, true),
            'logged_in' => $this->sparkMeta((int) ($listStats['logged_in'] ?? 0), $total, true),
        ];
    }

    /**
     * @param  array{total?: int, paid?: int, unpaid?: int, expired?: int, cancelled?: int}  $stats
     * @return array<string, array{percent: float, trend: string}>
     */
    public function subscriptionsSparkMeta(array $stats): array
    {
        $total = (int) ($stats['total'] ?? 0);

        return [
            'total' => $this->existenceSparkMeta($total),
            'paid' => $this->sparkMeta((int) ($stats['paid'] ?? 0), $total, true),
            'unpaid' => $this->sparkMeta((int) ($stats['unpaid'] ?? 0), $total, false),
            'expired' => $this->sparkMeta((int) ($stats['expired'] ?? 0), $total, false),
            'cancelled' => $this->sparkMeta((int) ($stats['cancelled'] ?? 0), $total, false),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, array{percent: float, trend: string}>
     */
    public function financeSparkMeta(array $summary): array
    {
        $collected = (float) ($summary['collected_amount'] ?? 0);
        $outstanding = (float) ($summary['outstanding_amount'] ?? 0);
        $expenses = (float) ($summary['expense_amount'] ?? 0);
        $net = (float) ($summary['net_period'] ?? 0);
        $overdue = (float) ($summary['overdue_amount'] ?? 0);
        $expiredUnpaid = (float) ($summary['expired_unpaid_amount'] ?? 0);
        $ledger = isset($summary['ledger_net_profit']) && $summary['ledger_net_profit'] !== null
            ? (float) $summary['ledger_net_profit']
            : null;

        $receivablesPool = $collected + $outstanding;
        $cashflowPool = $collected + $expenses;
        $riskPool = $outstanding + $overdue + $expiredUnpaid;

        return [
            'collected' => $this->sparkMeta($collected, $receivablesPool, true),
            'outstanding' => $this->sparkMeta($outstanding, $receivablesPool, false),
            'expenses' => $this->sparkMeta($expenses, $cashflowPool, false),
            'net_period' => $this->signedSparkMeta($net, $cashflowPool > 0 ? $cashflowPool : null),
            'overdue' => $this->sparkMeta($overdue, $riskPool > 0 ? $riskPool : $receivablesPool, false),
            'expired_unpaid' => $this->sparkMeta($expiredUnpaid, $riskPool > 0 ? $riskPool : $receivablesPool, false),
            'ledger_net_profit' => $ledger === null
                ? ['percent' => 0.0, 'trend' => 'flat']
                : $this->signedSparkMeta($ledger, abs($collected) > 0 ? abs($collected) : null),
        ];
    }

    /**
     * @param  array{children: int, staff: int, classrooms: int, classrooms_total: int, units: int, units_total: int, activities: int}  $overview
     * @param  array{present_today: int, left_today: int, waiting_today: int}  $stats
     * @param  array{unpaid_active: int, expiring_soon: int, operational_total?: int}  $subscriptionKpis
     * @return array<string, array{percent: float, trend: string}>
     */
    public function dashboardSparkMeta(array $overview, array $stats, array $subscriptionKpis): array
    {
        $children = (int) $overview['children'];
        $classroomsTotal = max(0, (int) ($overview['classrooms_total'] ?? $overview['classrooms']));
        $unitsTotal = max(0, (int) ($overview['units_total'] ?? $overview['units']));
        $opsTotal = max(0, (int) ($subscriptionKpis['operational_total'] ?? 0));

        $activities = (int) $overview['activities'];

        return [
            'children' => $this->existenceSparkMeta($children),
            'staff' => $this->existenceSparkMeta((int) $overview['staff']),
            'classrooms' => $this->sparkMeta((int) $overview['classrooms'], $classroomsTotal, true),
            'present_today' => $this->sparkMeta((int) $stats['present_today'], $children, true),
            'waiting_today' => $this->sparkMeta((int) $stats['waiting_today'], $children, false),
            'left_today' => $this->sparkMeta((int) $stats['left_today'], $children, true),
            'unpaid_active' => $this->sparkMeta((int) ($subscriptionKpis['unpaid_active'] ?? 0), $opsTotal, false),
            'expiring_soon' => $this->sparkMeta((int) ($subscriptionKpis['expiring_soon'] ?? 0), $opsTotal, false),
            'units' => $this->sparkMeta((int) $overview['units'], $unitsTotal, true),
            'activities' => [
                'percent' => $activities > 0 ? min(100.0, $activities * 12.5) : 0.0,
                'trend' => $activities > 0 ? 'up' : 'flat',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function periodOptions(): array
    {
        return [
            self::PERIOD_TODAY,
            self::PERIOD_WEEK,
            self::PERIOD_MONTH,
            self::PERIOD_YEAR,
            self::PERIOD_ALL,
        ];
    }

    public function normalizePeriod(?string $period): string
    {
        $period = strtolower(trim((string) $period));

        return in_array($period, $this->periodOptions(), true) ? $period : self::PERIOD_TODAY;
    }

    /**
     * @return array{
     *     total: int,
     *     present: int,
     *     departed: int,
     *     leave: int,
     *     absent: int,
     *     no_record: int,
     *     segments: list<array{key: string, label: string, count: int, color: string, pct: float}>,
     * }
     */
    public function attendanceBreakdown(int $tenantUserId, ?string $date = null, ?int $classroomId = null): array
    {
        $board = $this->attendance->todayBoard($tenantUserId, $date);

        $filterChild = static function ($child) use ($classroomId): bool {
            if ($classroomId === null || $classroomId < 1) {
                return true;
            }

            return (int) ($child->activeEnrollment?->classroom_id ?? 0) === $classroomId;
        };

        $notYet = $board['not_yet']->filter($filterChild);
        $checkedIn = $board['checked_in']->filter(fn ($row) => $filterChild($row['child']));
        $checkedOut = $board['checked_out']->filter(fn ($row) => $filterChild($row['child']));

        $dateStr = $board['date'];

        $absentQuery = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', $dateStr)
            ->where('status', AttendanceLog::STATUS_ABSENT);

        $leaveQuery = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', $dateStr)
            ->where('status', 'leave');

        if ($classroomId > 0) {
            $classScope = function ($q) use ($classroomId): void {
                $q->whereHas('child', function ($c) use ($classroomId): void {
                    $c->whereHas('activeEnrollment', fn ($e) => $e->where('classroom_id', $classroomId)->where('is_active', true));
                });
            };
            $absentQuery->where($classScope);
            $leaveQuery->where($classScope);
        }

        $present = $checkedIn->count();
        $departed = $checkedOut->count();
        $leave = $leaveQuery->count();
        $absent = $absentQuery->count();
        $noRecord = $notYet->count();
        $total = max(1, $present + $departed + $leave + $absent + $noRecord);

        $segments = [];
        foreach (
            [
                ['key' => 'present', 'label' => 'حضور', 'count' => $present, 'color' => '#34d399'],
                ['key' => 'departed', 'label' => 'انصراف', 'count' => $departed, 'color' => '#059669'],
                ['key' => 'leave', 'label' => 'إجازة', 'count' => $leave, 'color' => '#fb923c'],
                ['key' => 'absent', 'label' => 'غائب', 'count' => $absent, 'color' => '#f87171'],
                ['key' => 'no_record', 'label' => 'لا يوجد سجل', 'count' => $noRecord, 'color' => '#d1d5db'],
            ] as $row
        ) {
            $segments[] = $row + ['pct' => round(($row['count'] / $total) * 100, 1)];
        }

        return [
            'total' => $present + $departed + $leave + $absent + $noRecord,
            'present' => $present,
            'departed' => $departed,
            'leave' => $leave,
            'absent' => $absent,
            'no_record' => $noRecord,
            'segments' => $segments,
        ];
    }

    public function conicGradient(array $segments): string
    {
        $parts = [];
        $cursor = 0.0;

        foreach ($segments as $seg) {
            if ($seg['count'] < 1) {
                continue;
            }
            $end = $cursor + (float) $seg['pct'];
            $parts[] = sprintf('%s %.2f%% %.2f%%', $seg['color'], $cursor, $end);
            $cursor = $end;
        }

        if ($parts === []) {
            return 'conic-gradient(#f3f4f6 0 100%)';
        }

        return 'conic-gradient('.implode(', ', $parts).')';
    }
}
