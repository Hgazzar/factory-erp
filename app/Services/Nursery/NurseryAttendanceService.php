<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceCorrection;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\StaffAttendanceLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class NurseryAttendanceService
{
    public function __construct(
        private readonly NurseryShiftAttendanceService $shiftAttendance,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     active_children: int,
     *     checked_in: Collection,
     *     checked_out: Collection,
     *     not_yet: Collection,
     * }
     */
    public function todayBoard(int $tenantUserId, ?string $date = null, ?int $classroomId = null): array
    {
        $date = $date ?? now()->toDateString();

        $activeChildren = Child::query()
            ->with(['guardian:id,name,phone', 'activeEnrollment.classroom:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->when($classroomId !== null && $classroomId > 0, function ($q) use ($classroomId): void {
                $q->whereHas('activeEnrollment', function ($enrollment) use ($classroomId): void {
                    $enrollment->where('classroom_id', $classroomId)->where('is_active', true);
                });
            })
            ->orderBy('name')
            ->get();

        $logs = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('child_id');

        $checkedIn = collect();
        $checkedOut = collect();
        $notYet = collect();

        foreach ($activeChildren as $child) {
            $log = $logs->get($child->id);

            if ($log === null) {
                $notYet->push($child);

                continue;
            }

            if ($log->checked_out_at !== null) {
                $checkedOut->push(['child' => $child, 'log' => $log]);
            } elseif ($log->checked_in_at !== null) {
                $checkedIn->push(['child' => $child, 'log' => $log]);
            } else {
                $notYet->push($child);
            }
        }

        return [
            'date' => $date,
            'active_children' => $activeChildren->count(),
            'checked_in' => $checkedIn,
            'checked_out' => $checkedOut,
            'not_yet' => $notYet,
        ];
    }

    /**
     * @return array{
     *     date: string,
     *     active_staff: int,
     *     checked_in: Collection,
     *     checked_out: Collection,
     *     not_yet: Collection,
     * }
     */
    public function staffTodayBoard(int $tenantUserId, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $activeStaff = Employee::query()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $logs = StaffAttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('employee_id');

        $checkedIn = collect();
        $checkedOut = collect();
        $notYet = collect();

        foreach ($activeStaff as $employee) {
            $log = $logs->get($employee->id);

            if ($log === null) {
                $notYet->push($employee);

                continue;
            }

            if ($log->checked_out_at !== null) {
                $checkedOut->push(['employee' => $employee, 'log' => $log]);
            } elseif ($log->checked_in_at !== null) {
                $checkedIn->push(['employee' => $employee, 'log' => $log]);
            } else {
                $notYet->push($employee);
            }
        }

        return [
            'date' => $date,
            'active_staff' => $activeStaff->count(),
            'checked_in' => $checkedIn,
            'checked_out' => $checkedOut,
            'not_yet' => $notYet,
        ];
    }

    public function checkIn(int $tenantUserId, int $childId, ?int $recordedBy = null): AttendanceLog
    {
        $child = Child::query()
            ->where('user_id', $tenantUserId)
            ->whereKey($childId)
            ->where('status', Child::STATUS_ACTIVE)
            ->first();

        if ($child === null) {
            throw new RuntimeException('الطفل غير موجود أو غير نشط.');
        }

        return DB::transaction(function () use ($tenantUserId, $child, $recordedBy): AttendanceLog {
            $today = now()->toDateString();

            $log = AttendanceLog::query()
                ->where('user_id', $tenantUserId)
                ->where('child_id', $child->id)
                ->whereDate('attendance_date', $today)
                ->lockForUpdate()
                ->first();

            if ($log === null) {
                $log = new AttendanceLog([
                    'user_id' => $tenantUserId,
                    'child_id' => $child->id,
                    'attendance_date' => $today,
                ]);
            }

            if ($log->checked_in_at !== null && $log->checked_out_at === null) {
                throw new RuntimeException('الطفل مسجّل حضوراً بالفعل اليوم.');
            }

            if ($log->checked_out_at !== null) {
                throw new RuntimeException('تم تسجيل انصراف هذا الطفل اليوم — لا يمكن إعادة الحضور.');
            }

            $log->checked_in_at = now();
            $log->checked_out_at = null;
            $log->recorded_by = $recordedBy;
            $log->status = AttendanceLog::STATUS_PRESENT;

            if ($this->shiftAttendance->isChildLate($tenantUserId, $log)) {
                $log->status = AttendanceLog::STATUS_LATE;
            }

            try {
                $log->save();
            } catch (UniqueConstraintViolationException) {
                throw new RuntimeException('الطفل مسجّل حضوراً بالفعل اليوم.');
            }

            return $log->fresh(['child']);
        });
    }

    public function checkOut(int $tenantUserId, int $childId, ?int $recordedBy = null): AttendanceLog
    {
        return DB::transaction(function () use ($tenantUserId, $childId, $recordedBy): AttendanceLog {
            $today = now()->toDateString();

            $log = AttendanceLog::query()
                ->where('user_id', $tenantUserId)
                ->where('child_id', $childId)
                ->whereDate('attendance_date', $today)
                ->lockForUpdate()
                ->first();

            if ($log === null || $log->checked_in_at === null) {
                throw new RuntimeException('لم يُسجَّل حضور لهذا الطفل اليوم.');
            }

            if ($log->checked_out_at !== null) {
                throw new RuntimeException('تم تسجيل الانصراف مسبقاً.');
            }

            $log->checked_out_at = now();
            $log->recorded_by = $recordedBy ?? $log->recorded_by;
            $log->save();

            return $log->fresh(['child']);
        });
    }

    /**
     * @param  list<int|string>  $childIds
     * @return array{ok: int, skipped: int}
     */
    public function checkInMany(int $tenantUserId, array $childIds, ?int $recordedBy = null, ?int $classroomId = null): array
    {
        $ok = 0;
        $skipped = 0;

        foreach ($this->uniqueChildIds($childIds) as $childId) {
            try {
                $this->assertClassroomEnrollment($tenantUserId, $childId, $classroomId);
                $this->checkIn($tenantUserId, $childId, $recordedBy);
                $ok++;
            } catch (RuntimeException|ModelNotFoundException) {
                $skipped++;
            }
        }

        return ['ok' => $ok, 'skipped' => $skipped];
    }

    /**
     * @param  list<int|string>  $childIds
     * @return array{ok: int, skipped: int}
     */
    public function checkOutMany(int $tenantUserId, array $childIds, ?int $recordedBy = null, ?int $classroomId = null): array
    {
        $ok = 0;
        $skipped = 0;

        foreach ($this->uniqueChildIds($childIds) as $childId) {
            try {
                $this->assertClassroomEnrollment($tenantUserId, $childId, $classroomId);
                $this->checkOut($tenantUserId, $childId, $recordedBy);
                $ok++;
            } catch (RuntimeException|ModelNotFoundException) {
                $skipped++;
            }
        }

        return ['ok' => $ok, 'skipped' => $skipped];
    }

    /**
     * @param  array{checked_in_at?: ?string, checked_out_at?: ?string, status?: string, reason?: ?string}  $data
     */
    public function correct(int $tenantUserId, AttendanceLog $log, array $data, int $correctedBy): AttendanceLog
    {
        abort_unless((int) $log->user_id === $tenantUserId, 404);

        return DB::transaction(function () use ($tenantUserId, $log, $data, $correctedBy): AttendanceLog {
            /** @var AttendanceLog $locked */
            $locked = AttendanceLog::query()
                ->where('user_id', $tenantUserId)
                ->whereKey($log->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->correctionSnapshot($locked);
            $date = $locked->attendance_date instanceof Carbon
                ? $locked->attendance_date->toDateString()
                : Carbon::parse((string) $locked->attendance_date)->toDateString();

            $status = (string) ($data['status'] ?? $locked->status);
            if (! in_array($status, [AttendanceLog::STATUS_PRESENT, AttendanceLog::STATUS_LATE, AttendanceLog::STATUS_ABSENT], true)) {
                throw new RuntimeException('حالة الحضور غير صالحة.');
            }

            $checkInRaw = $this->nullableTimeString($data['checked_in_at'] ?? null);
            $checkOutRaw = $this->nullableTimeString($data['checked_out_at'] ?? null);

            if ($status === AttendanceLog::STATUS_ABSENT) {
                $locked->checked_in_at = null;
                $locked->checked_out_at = null;
                $locked->status = AttendanceLog::STATUS_ABSENT;
            } else {
                if ($checkInRaw === null) {
                    throw new RuntimeException('وقت الحضور مطلوب.');
                }

                $checkedInAt = $this->combineDateAndTime($date, $checkInRaw);
                $checkedOutAt = $checkOutRaw !== null ? $this->combineDateAndTime($date, $checkOutRaw) : null;

                if ($checkedInAt->greaterThan(now()->addMinute())) {
                    throw new RuntimeException('لا يمكن تسجيل وقت حضور في المستقبل.');
                }

                if ($checkedOutAt !== null) {
                    if ($checkedOutAt->lessThan($checkedInAt)) {
                        throw new RuntimeException('وقت الانصراف لا يمكن أن يسبق الحضور.');
                    }
                    if ($checkedOutAt->greaterThan(now()->addMinute())) {
                        throw new RuntimeException('لا يمكن تسجيل وقت انصراف في المستقبل.');
                    }
                }

                $locked->checked_in_at = $checkedInAt;
                $locked->checked_out_at = $checkedOutAt;

                $derivedLate = $this->shiftAttendance->isChildLate($tenantUserId, $locked);
                if ($derivedLate) {
                    $locked->status = AttendanceLog::STATUS_LATE;
                } else {
                    $locked->status = AttendanceLog::STATUS_PRESENT;
                }
            }

            $after = $this->correctionSnapshot($locked);

            if ($before === $after) {
                throw new RuntimeException('لا يوجد تغيير لتصحيحه.');
            }

            $locked->save();

            AttendanceCorrection::query()->create([
                'user_id' => $tenantUserId,
                'attendance_log_id' => $locked->id,
                'corrected_by' => $correctedBy,
                'before_state' => $before,
                'after_state' => $after,
                'reason' => $this->nullableReason($data['reason'] ?? null),
            ]);

            return $locked->fresh(['child', 'corrections']);
        });
    }

    /**
     * @param  list<int|string>  $childIds
     * @return list<int>
     */
    private function uniqueChildIds(array $childIds): array
    {
        $ids = [];
        foreach ($childIds as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $ids[$intId] = $intId;
            }
        }

        return array_values($ids);
    }

    /**
     * @param  list<int|string>  $employeeIds
     * @return list<int>
     */
    private function uniqueEmployeeIds(array $employeeIds): array
    {
        return $this->uniqueChildIds($employeeIds);
    }

    private function assertClassroomEnrollment(int $tenantUserId, int $childId, ?int $classroomId): void
    {
        if ($classroomId === null || $classroomId < 1) {
            return;
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $tenantUserId)
            ->where('child_id', $childId)
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->exists();

        if (! $enrolled) {
            throw new RuntimeException('الطفل غير مسجّل في هذا الفصل.');
        }
    }

    /**
     * @return array{checked_in_at: ?string, checked_out_at: ?string, status: ?string}
     */
    private function correctionSnapshot(AttendanceLog $log): array
    {
        return [
            'checked_in_at' => $log->checked_in_at?->format('Y-m-d H:i:s'),
            'checked_out_at' => $log->checked_out_at?->format('Y-m-d H:i:s'),
            'status' => $log->status,
        ];
    }

    private function nullableTimeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $time = trim((string) $value);

        return $time === '' ? null : $time;
    }

    private function nullableReason(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $reason = trim((string) $value);

        return $reason === '' ? null : $reason;
    }

    private function combineDateAndTime(string $date, string $time): Carbon
    {
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $match) !== 1) {
            throw new RuntimeException('صيغة الوقت غير صالحة.');
        }

        $hour = (int) $match[1];
        $minute = (int) $match[2];
        $second = isset($match[3]) ? (int) $match[3] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            throw new RuntimeException('صيغة الوقت غير صالحة.');
        }

        return Carbon::parse(sprintf('%s %02d:%02d:%02d', $date, $hour, $minute, $second));
    }

    public function staffCheckIn(int $tenantUserId, int $employeeId, ?int $recordedBy = null): StaffAttendanceLog
    {
        Employee::query()
            ->where('user_id', $tenantUserId)
            ->whereKey($employeeId)
            ->where('status', 'active')
            ->firstOrFail();

        $today = now()->toDateString();
        $log = StaffAttendanceLog::query()->firstOrNew([
            'user_id' => $tenantUserId,
            'employee_id' => $employeeId,
            'attendance_date' => $today,
        ]);

        if ($log->checked_in_at !== null && $log->checked_out_at === null) {
            throw new RuntimeException('الموظف مسجّل حضوراً بالفعل اليوم.');
        }

        $log->checked_in_at = now();
        $log->checked_out_at = null;
        $log->status = StaffAttendanceLog::STATUS_PRESENT;
        $log->recorded_by = $recordedBy;
        $log->save();

        return $log->fresh(['employee']);
    }

    public function staffCheckOut(int $tenantUserId, int $employeeId, ?int $recordedBy = null): StaffAttendanceLog
    {
        $today = now()->toDateString();

        $log = StaffAttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $today)
            ->firstOrFail();

        if ($log->checked_in_at === null) {
            throw new RuntimeException('لم يُسجَّل حضور لهذا الموظف اليوم.');
        }

        if ($log->checked_out_at !== null) {
            throw new RuntimeException('تم تسجيل الانصراف مسبقاً.');
        }

        $log->checked_out_at = now();
        $log->recorded_by = $recordedBy ?? $log->recorded_by;
        $log->save();

        return $log->fresh(['employee']);
    }

    /**
     * @param  list<int|string>  $employeeIds
     * @return array{ok: int, skipped: int}
     */
    public function staffCheckInMany(int $tenantUserId, array $employeeIds, ?int $recordedBy = null): array
    {
        $ok = 0;
        $skipped = 0;

        foreach ($this->uniqueEmployeeIds($employeeIds) as $employeeId) {
            try {
                $this->staffCheckIn($tenantUserId, $employeeId, $recordedBy);
                $ok++;
            } catch (RuntimeException|ModelNotFoundException) {
                $skipped++;
            }
        }

        return ['ok' => $ok, 'skipped' => $skipped];
    }

    /**
     * @param  list<int|string>  $employeeIds
     * @return array{ok: int, skipped: int}
     */
    public function staffCheckOutMany(int $tenantUserId, array $employeeIds, ?int $recordedBy = null): array
    {
        $ok = 0;
        $skipped = 0;

        foreach ($this->uniqueEmployeeIds($employeeIds) as $employeeId) {
            try {
                $this->staffCheckOut($tenantUserId, $employeeId, $recordedBy);
                $ok++;
            } catch (RuntimeException|ModelNotFoundException) {
                $skipped++;
            }
        }

        return ['ok' => $ok, 'skipped' => $skipped];
    }

    public function findChildForQuickSearch(int $tenantUserId, string $query): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        return Child::query()
            ->with(['guardian:id,name,phone', 'activeEnrollment.classroom:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('code', 'like', '%'.$query.'%')
                    ->orWhereHas('guardian', fn ($g) => $g->where('phone', 'like', '%'.$query.'%')
                        ->orWhere('name', 'like', '%'.$query.'%'));
            })
            ->orderBy('name')
            ->limit(12)
            ->get();
    }

    public function findStaffForQuickSearch(int $tenantUserId, string $query): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        return Employee::query()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('code', 'like', '%'.$query.'%')
                    ->orWhere('mobile', 'like', '%'.$query.'%');
            })
            ->orderBy('name')
            ->limit(12)
            ->get();
    }
}
