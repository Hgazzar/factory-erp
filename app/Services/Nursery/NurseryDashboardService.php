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
    public function sharePercent(int $part, int $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }

        return round(min(100, max(0, ($part / $whole) * 100)), 1);
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

        $presentPct = $this->sharePercent((int) $stats['present_today'], $children);
        $waitingPct = $this->sharePercent((int) $stats['waiting_today'], $children);
        $leftPct = $this->sharePercent((int) $stats['left_today'], $children);
        $classroomsPct = $this->sharePercent((int) $overview['classrooms'], $classroomsTotal);
        $unitsPct = $this->sharePercent((int) $overview['units'], $unitsTotal);
        $unpaidPct = $this->sharePercent((int) ($subscriptionKpis['unpaid_active'] ?? 0), $opsTotal);
        $expiringPct = $this->sharePercent((int) ($subscriptionKpis['expiring_soon'] ?? 0), $opsTotal);

        // Count-only cards: full spark when > 0, empty when 0
        $childrenPct = $children > 0 ? 100.0 : 0.0;
        $staffPct = ((int) $overview['staff']) > 0 ? 100.0 : 0.0;
        $activitiesPct = ((int) $overview['activities']) > 0
            ? min(100.0, ((int) $overview['activities']) * 12.5)
            : 0.0;

        return [
            'children' => [
                'percent' => $childrenPct,
                'trend' => $children > 0 ? 'up' : 'flat',
            ],
            'staff' => [
                'percent' => $staffPct,
                'trend' => ((int) $overview['staff']) > 0 ? 'up' : 'flat',
            ],
            'classrooms' => [
                'percent' => $classroomsPct,
                'trend' => $this->trendFromShare($classroomsPct, true),
            ],
            'present_today' => [
                'percent' => $presentPct,
                'trend' => $this->trendFromShare($presentPct, true),
            ],
            'waiting_today' => [
                'percent' => $waitingPct,
                'trend' => $this->trendFromShare($waitingPct, false),
            ],
            'left_today' => [
                'percent' => $leftPct,
                'trend' => $this->trendFromShare($leftPct, true),
            ],
            'unpaid_active' => [
                'percent' => $unpaidPct,
                'trend' => $this->trendFromShare($unpaidPct, false),
            ],
            'expiring_soon' => [
                'percent' => $expiringPct,
                'trend' => $this->trendFromShare($expiringPct, false),
            ],
            'units' => [
                'percent' => $unitsPct,
                'trend' => $this->trendFromShare($unitsPct, true),
            ],
            'activities' => [
                'percent' => $activitiesPct,
                'trend' => ((int) $overview['activities']) > 0 ? 'up' : 'flat',
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
