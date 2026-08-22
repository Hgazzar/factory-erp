<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\AttendanceWeekdaySetting;
use App\Models\Nursery\Child;
use App\Models\Nursery\LeaveRecord;
use App\Models\Nursery\StaffAttendanceLog;
use App\Support\NurseryWeekdays;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class NurseryAttendanceListService
{
    /**
     * @return list<int>
     */
    public function weekdaysFor(int $tenantUserId, string $scope): array
    {
        $setting = AttendanceWeekdaySetting::query()
            ->where('user_id', $tenantUserId)
            ->where('scope', $scope)
            ->first();

        return NurseryWeekdays::normalize($setting?->weekdays);
    }

    /**
     * @param  list<int|string>  $weekdays
     */
    public function saveWeekdays(int $tenantUserId, string $scope, array $weekdays): AttendanceWeekdaySetting
    {
        if (! in_array($scope, [AttendanceWeekdaySetting::SCOPE_CHILDREN, AttendanceWeekdaySetting::SCOPE_STAFF], true)) {
            throw new InvalidArgumentException('نطاق أيام الحضور غير صالح.');
        }

        $normalized = NurseryWeekdays::normalize($weekdays);
        if ($normalized === []) {
            throw new InvalidArgumentException('اختر يوماً واحداً على الأقل.');
        }

        return AttendanceWeekdaySetting::query()->updateOrCreate(
            ['user_id' => $tenantUserId, 'scope' => $scope],
            ['weekdays' => $normalized]
        );
    }

    /**
     * @return array{
     *     week_start: string,
     *     week_end: string,
     *     days: list<array{date: string, label: string, weekday: int, is_expected: bool}>,
     *     rows: list<array{
     *         id: int,
     *         name: string,
     *         subtitle: string|null,
     *         present_count: int,
     *         expected_count: int,
     *         cells: list<array{date: string, state: string, label: string, detail: string|null}>
     *     }>,
     *     day_summaries: list<array{date: string, present: int, total: int}>
     * }
     */
    public function childrenWeeklyGrid(
        int $tenantUserId,
        Carbon $weekStart,
        ?int $classroomId = null,
        string $search = '',
    ): array {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);
        $weekdays = $this->weekdaysFor($tenantUserId, AttendanceWeekdaySetting::SCOPE_CHILDREN);
        $days = $this->buildDayHeaders($weekStart, $weekdays);

        $childrenQuery = Child::query()
            ->with(['activeEnrollment.classroom:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->when($classroomId > 0, fn ($q) => $q->whereHas(
                'activeEnrollment',
                fn ($e) => $e->where('classroom_id', $classroomId)->where('is_active', true)
            ))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name');

        $children = $childrenQuery->get();
        $childIds = $children->pluck('id')->all();

        $logs = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', '>=', $weekStart->toDateString())
            ->whereDate('attendance_date', '<=', $weekEnd->toDateString())
            ->when($childIds !== [], fn ($q) => $q->whereIn('child_id', $childIds))
            ->get()
            ->keyBy(fn (AttendanceLog $log) => $log->child_id.'|'.$log->attendance_date->toDateString());

        $leaves = LeaveRecord::query()
            ->where('user_id', $tenantUserId)
            ->where('scope', LeaveRecord::SCOPE_CHILDREN)
            ->where('starts_on', '<=', $weekEnd->toDateString())
            ->where('ends_on', '>=', $weekStart->toDateString())
            ->when($childIds !== [], fn ($q) => $q->whereIn('child_id', $childIds))
            ->get()
            ->groupBy('child_id');

        $rows = [];
        $dayPresentCounts = array_fill_keys(array_column($days, 'date'), 0);

        foreach ($children as $child) {
            $cells = [];
            $presentCount = 0;
            $expectedCount = 0;

            foreach ($days as $day) {
                $state = $this->resolveChildDayState(
                    $child->id,
                    $day,
                    $logs,
                    $leaves->get($child->id, collect())
                );

                if ($state['state'] === 'present') {
                    $dayPresentCounts[$day['date']]++;
                }

                if ($day['is_expected']) {
                    $expectedCount++;
                    if ($state['state'] === 'present') {
                        $presentCount++;
                    }
                }

                $cells[] = $state;
            }

            $rows[] = [
                'id' => (int) $child->id,
                'name' => $child->name,
                'subtitle' => $child->activeEnrollment?->classroom?->name,
                'present_count' => $presentCount,
                'expected_count' => $expectedCount,
                'cells' => $cells,
            ];
        }

        $daySummaries = [];
        foreach ($days as $day) {
            $daySummaries[] = [
                'date' => $day['date'],
                'present' => $dayPresentCounts[$day['date']] ?? 0,
                'total' => $children->count(),
            ];
        }

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'days' => $days,
            'rows' => $rows,
            'day_summaries' => $daySummaries,
        ];
    }

    /**
     * @return array{week_start: string, week_end: string, days: list<array<string, mixed>>, rows: list<array<string, mixed>>, day_summaries: list<array<string, mixed>>}
     */
    public function staffWeeklyGrid(int $tenantUserId, Carbon $weekStart, string $search = ''): array
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);
        $weekdays = $this->weekdaysFor($tenantUserId, AttendanceWeekdaySetting::SCOPE_STAFF);
        $days = $this->buildDayHeaders($weekStart, $weekdays);

        $shiftAttendance = app(NurseryShiftAttendanceService::class);

        $staff = Employee::query()
            ->with('nurseryShift:id,name,start_time')
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();

        $employeeIds = $staff->pluck('id')->all();

        $logs = StaffAttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', '>=', $weekStart->toDateString())
            ->whereDate('attendance_date', '<=', $weekEnd->toDateString())
            ->when($employeeIds !== [], fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->keyBy(fn (StaffAttendanceLog $log) => $log->employee_id.'|'.$log->attendance_date->toDateString());

        $leaves = LeaveRecord::query()
            ->where('user_id', $tenantUserId)
            ->where('scope', LeaveRecord::SCOPE_STAFF)
            ->where('starts_on', '<=', $weekEnd->toDateString())
            ->where('ends_on', '>=', $weekStart->toDateString())
            ->when($employeeIds !== [], fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->groupBy('employee_id');

        $rows = [];
        $dayPresentCounts = array_fill_keys(array_column($days, 'date'), 0);

        foreach ($staff as $employee) {
            $cells = [];
            $presentCount = 0;
            $expectedCount = 0;

            foreach ($days as $day) {
                $state = $this->resolveStaffDayState(
                    $employee,
                    $day,
                    $logs,
                    $leaves->get($employee->id, collect()),
                    $shiftAttendance,
                );

                if ($state['state'] === 'present') {
                    $dayPresentCounts[$day['date']]++;
                }

                if ($day['is_expected']) {
                    $expectedCount++;
                    if ($state['state'] === 'present') {
                        $presentCount++;
                    }
                }

                $cells[] = $state;
            }

            $rows[] = [
                'id' => (int) $employee->id,
                'name' => $employee->name,
                'subtitle' => $employee->nurseryShift?->name
                    ?? $employee->nursery_job_role
                    ?? $employee->job_title,
                'present_count' => $presentCount,
                'expected_count' => $expectedCount,
                'cells' => $cells,
            ];
        }

        $daySummaries = [];
        foreach ($days as $day) {
            $daySummaries[] = [
                'date' => $day['date'],
                'present' => $dayPresentCounts[$day['date']] ?? 0,
                'total' => $staff->count(),
            ];
        }

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'days' => $days,
            'rows' => $rows,
            'day_summaries' => $daySummaries,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeLeave(int $tenantUserId, array $data, ?int $createdBy = null): LeaveRecord
    {
        $scope = (string) ($data['scope'] ?? '');
        $name = trim((string) ($data['name'] ?? ''));
        $startsOn = (string) ($data['starts_on'] ?? '');
        $endsOn = (string) ($data['ends_on'] ?? '');

        if ($name === '' || $startsOn === '' || $endsOn === '') {
            throw new InvalidArgumentException('اسم الإجازة وتواريخها مطلوبة.');
        }

        if ($endsOn < $startsOn) {
            throw new InvalidArgumentException('تاريخ الانتهاء يجب أن يكون بعد البداية.');
        }

        $payload = [
            'user_id' => $tenantUserId,
            'scope' => $scope,
            'name' => $name,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'created_by' => $createdBy,
            'child_id' => null,
            'employee_id' => null,
        ];

        if ($scope === LeaveRecord::SCOPE_CHILDREN) {
            $childIds = array_values(array_filter(array_map('intval', (array) ($data['child_ids'] ?? []))));
            if ($childIds === []) {
                throw new InvalidArgumentException('اختر طفلاً واحداً على الأقل.');
            }

            $records = collect();
            foreach ($childIds as $childId) {
                Child::query()->where('user_id', $tenantUserId)->whereKey($childId)->firstOrFail();
                $records->push(LeaveRecord::query()->create([...$payload, 'child_id' => $childId]));
            }

            return $records->first();
        }

        if ($scope === LeaveRecord::SCOPE_STAFF) {
            $employeeIds = array_values(array_filter(array_map('intval', (array) ($data['employee_ids'] ?? []))));
            if ($employeeIds === []) {
                throw new InvalidArgumentException('اختر موظفاً واحداً على الأقل.');
            }

            $records = collect();
            foreach ($employeeIds as $employeeId) {
                Employee::query()->where('user_id', $tenantUserId)->whereKey($employeeId)->firstOrFail();
                $records->push(LeaveRecord::query()->create([...$payload, 'employee_id' => $employeeId]));
            }

            return $records->first();
        }

        throw new InvalidArgumentException('نطاق الإجازة غير صالح.');
    }

    /**
     * @return Collection<int, LeaveRecord>
     */
    public function futureLeavesForChild(int $tenantUserId, int $childId): Collection
    {
        return LeaveRecord::query()
            ->where('user_id', $tenantUserId)
            ->where('scope', LeaveRecord::SCOPE_CHILDREN)
            ->where('child_id', $childId)
            ->where('ends_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->get();
    }

    /**
     * @return Collection<int, LeaveRecord>
     */
    public function futureLeavesForEmployee(int $tenantUserId, int $employeeId): Collection
    {
        return LeaveRecord::query()
            ->where('user_id', $tenantUserId)
            ->where('scope', LeaveRecord::SCOPE_STAFF)
            ->where('employee_id', $employeeId)
            ->where('ends_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->get();
    }

    public function deleteLeave(LeaveRecord $leave, int $tenantUserId): void
    {
        abort_unless((int) $leave->user_id === $tenantUserId, 404);
        $leave->delete();
    }

    /**
     * @return array{from: string, to: string, scope: string, include_absence_reason: bool, rows: list<array<string, mixed>>}
     */
    public function buildReport(
        int $tenantUserId,
        string $scope,
        string $from,
        string $to,
        array $subjectIds,
        bool $includeAbsenceReason,
    ): array {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        if ($toDate->lessThan($fromDate)) {
            throw new InvalidArgumentException('تاريخ النهاية يجب أن يكون بعد البداية.');
        }

        $weekdays = $this->weekdaysFor(
            $tenantUserId,
            $scope === LeaveRecord::SCOPE_STAFF ? AttendanceWeekdaySetting::SCOPE_STAFF : AttendanceWeekdaySetting::SCOPE_CHILDREN
        );

        $period = CarbonPeriod::create($fromDate, $toDate);
        $rows = [];

        if ($scope === LeaveRecord::SCOPE_CHILDREN) {
            $subjects = Child::query()
                ->where('user_id', $tenantUserId)
                ->where('status', Child::STATUS_ACTIVE)
                ->when($subjectIds !== [], fn ($q) => $q->whereIn('id', $subjectIds))
                ->orderBy('name')
                ->get();

            $logs = AttendanceLog::query()
                ->where('user_id', $tenantUserId)
                ->whereDate('attendance_date', '>=', $fromDate->toDateString())
                ->whereDate('attendance_date', '<=', $toDate->toDateString())
                ->whereIn('child_id', $subjects->pluck('id'))
                ->get()
                ->keyBy(fn (AttendanceLog $l) => $l->child_id.'|'.$l->attendance_date->toDateString());

            $leaves = LeaveRecord::query()
                ->where('user_id', $tenantUserId)
                ->where('scope', LeaveRecord::SCOPE_CHILDREN)
                ->whereIn('child_id', $subjects->pluck('id'))
                ->where('starts_on', '<=', $toDate->toDateString())
                ->where('ends_on', '>=', $fromDate->toDateString())
                ->get()
                ->groupBy('child_id');

            foreach ($subjects as $child) {
                $days = [];
                foreach ($period as $date) {
                    $dateStr = $date->toDateString();
                    if (! in_array($date->dayOfWeek, $weekdays, true)) {
                        continue;
                    }
                    $key = $child->id.'|'.$dateStr;
                    $log = $logs->get($key);
                    $leave = $leaves->get($child->id, collect())->first(fn (LeaveRecord $r) => $r->coversDate($dateStr));

                    if ($log?->checked_in_at !== null) {
                        $days[] = ['date' => $dateStr, 'status' => 'present', 'reason' => null];
                    } elseif ($leave !== null) {
                        $days[] = ['date' => $dateStr, 'status' => 'leave', 'reason' => $includeAbsenceReason ? $leave->name : null];
                    } else {
                        $days[] = ['date' => $dateStr, 'status' => 'absent', 'reason' => $includeAbsenceReason ? 'غائب — لم يُسجَّل حضور' : null];
                    }
                }

                $rows[] = ['id' => $child->id, 'name' => $child->name, 'days' => $days];
            }
        } else {
            $subjects = Employee::query()
                ->where('user_id', $tenantUserId)
                ->where('status', 'active')
                ->when($subjectIds !== [], fn ($q) => $q->whereIn('id', $subjectIds))
                ->orderBy('name')
                ->get();

            $logs = StaffAttendanceLog::query()
                ->where('user_id', $tenantUserId)
                ->whereDate('attendance_date', '>=', $fromDate->toDateString())
                ->whereDate('attendance_date', '<=', $toDate->toDateString())
                ->whereIn('employee_id', $subjects->pluck('id'))
                ->get()
                ->keyBy(fn (StaffAttendanceLog $l) => $l->employee_id.'|'.$l->attendance_date->toDateString());

            $leaves = LeaveRecord::query()
                ->where('user_id', $tenantUserId)
                ->where('scope', LeaveRecord::SCOPE_STAFF)
                ->whereIn('employee_id', $subjects->pluck('id'))
                ->where('starts_on', '<=', $toDate->toDateString())
                ->where('ends_on', '>=', $fromDate->toDateString())
                ->get()
                ->groupBy('employee_id');

            foreach ($subjects as $employee) {
                $days = [];
                foreach ($period as $date) {
                    $dateStr = $date->toDateString();
                    if (! in_array($date->dayOfWeek, $weekdays, true)) {
                        continue;
                    }
                    $key = $employee->id.'|'.$dateStr;
                    $log = $logs->get($key);
                    $leave = $leaves->get($employee->id, collect())->first(fn (LeaveRecord $r) => $r->coversDate($dateStr));

                    if ($log?->checked_in_at !== null) {
                        $days[] = ['date' => $dateStr, 'status' => 'present', 'reason' => null];
                    } elseif ($leave !== null) {
                        $days[] = ['date' => $dateStr, 'status' => 'leave', 'reason' => $includeAbsenceReason ? $leave->name : null];
                    } else {
                        $days[] = ['date' => $dateStr, 'status' => 'absent', 'reason' => $includeAbsenceReason ? 'غائب — لم يُسجَّل حضور' : null];
                    }
                }

                $rows[] = ['id' => $employee->id, 'name' => $employee->name, 'days' => $days];
            }
        }

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'scope' => $scope,
            'include_absence_reason' => $includeAbsenceReason,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<int>  $weekdays
     * @return list<array{date: string, label: string, weekday: int, is_expected: bool}>
     */
    private function buildDayHeaders(Carbon $weekStart, array $weekdays): array
    {
        $days = [];
        $labels = NurseryWeekdays::labels();

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $weekday = $date->dayOfWeek;
            $days[] = [
                'date' => $date->toDateString(),
                'label' => ($labels[$weekday] ?? '').'، '.$date->format('n/j'),
                'weekday' => $weekday,
                'is_expected' => in_array($weekday, $weekdays, true),
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<string, Collection<int, AttendanceLog>>  $logs
     * @param  Collection<int, LeaveRecord>  $leaves
     * @param  array{date: string, is_expected: bool}  $day
     * @return array{date: string, state: string, label: string, detail: string|null}
     */
    private function resolveChildDayState(int $childId, array $day, Collection $logs, Collection $leaves): array
    {
        $leave = $leaves->first(fn (LeaveRecord $r) => $r->coversDate($day['date']));
        if ($leave !== null) {
            return ['date' => $day['date'], 'state' => 'leave', 'label' => 'إجازة', 'detail' => $leave->name];
        }

        $log = $logs->get($childId.'|'.$day['date']);
        if ($log?->checked_in_at !== null) {
            $time = $log->checked_in_at->format('H:i');

            return ['date' => $day['date'], 'state' => 'present', 'label' => 'حاضر', 'detail' => $time];
        }

        if (! $day['is_expected']) {
            return ['date' => $day['date'], 'state' => 'off', 'label' => '—', 'detail' => null];
        }

        if ($day['date'] > now()->toDateString()) {
            return ['date' => $day['date'], 'state' => 'future', 'label' => '—', 'detail' => null];
        }

        return ['date' => $day['date'], 'state' => 'absent', 'label' => 'غائب', 'detail' => null];
    }

    /**
     * @param  Collection<string, Collection<int, StaffAttendanceLog>>  $logs
     * @param  Collection<int, LeaveRecord>  $leaves
     * @param  array{date: string, is_expected: bool}  $day
     * @return array{date: string, state: string, label: string, detail: string|null}
     */
    private function resolveStaffDayState(
        Employee $employee,
        array $day,
        Collection $logs,
        Collection $leaves,
        NurseryShiftAttendanceService $shiftAttendance,
    ): array {
        $leave = $leaves->first(fn (LeaveRecord $r) => $r->coversDate($day['date']));
        if ($leave !== null) {
            return ['date' => $day['date'], 'state' => 'leave', 'label' => 'إجازة', 'detail' => $leave->name];
        }

        $log = $logs->get($employee->id.'|'.$day['date']);
        if ($log?->checked_in_at !== null) {
            $detail = $log->checked_in_at->format('H:i');
            $late = $shiftAttendance->lateDetail($employee, $log);
            if ($late !== null) {
                $detail .= ' — '.$late;
            }

            return ['date' => $day['date'], 'state' => 'present', 'label' => 'حاضر', 'detail' => $detail];
        }

        if (! $day['is_expected']) {
            return ['date' => $day['date'], 'state' => 'off', 'label' => '—', 'detail' => null];
        }

        if ($day['date'] > now()->toDateString()) {
            return ['date' => $day['date'], 'state' => 'future', 'label' => '—', 'detail' => null];
        }

        return ['date' => $day['date'], 'state' => 'absent', 'label' => 'غائب', 'detail' => null];
    }
}
