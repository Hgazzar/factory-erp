<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\StaffAttendanceLog;
use Illuminate\Support\Collection;
use RuntimeException;

final class NurseryAttendanceService
{
    /**
     * @return array{
     *     date: string,
     *     active_children: int,
     *     checked_in: Collection,
     *     checked_out: Collection,
     *     not_yet: Collection,
     * }
     */
    public function todayBoard(int $tenantUserId, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $activeChildren = Child::query()
            ->with(['guardian:id,name,phone', 'activeEnrollment.classroom:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
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
            ->firstOrFail();

        $today = now()->toDateString();
        $log = AttendanceLog::query()->firstOrNew([
            'user_id' => $tenantUserId,
            'child_id' => $child->id,
            'attendance_date' => $today,
        ]);

        if ($log->checked_in_at !== null && $log->checked_out_at === null) {
            throw new RuntimeException('الطفل مسجّل حضوراً بالفعل اليوم.');
        }

        if ($log->checked_out_at !== null) {
            throw new RuntimeException('تم تسجيل انصراف هذا الطفل اليوم — لا يمكن إعادة الحضور.');
        }

        $log->checked_in_at = now();
        $log->checked_out_at = null;
        $log->status = AttendanceLog::STATUS_PRESENT;
        $log->recorded_by = $recordedBy;
        $log->save();

        return $log->fresh(['child']);
    }

    public function checkOut(int $tenantUserId, int $childId, ?int $recordedBy = null): AttendanceLog
    {
        $today = now()->toDateString();

        $log = AttendanceLog::query()
            ->where('user_id', $tenantUserId)
            ->where('child_id', $childId)
            ->whereDate('attendance_date', $today)
            ->firstOrFail();

        if ($log->checked_in_at === null) {
            throw new RuntimeException('لم يُسجَّل حضور لهذا الطفل اليوم.');
        }

        if ($log->checked_out_at !== null) {
            throw new RuntimeException('تم تسجيل الانصراف مسبقاً.');
        }

        $log->checked_out_at = now();
        $log->recorded_by = $recordedBy ?? $log->recorded_by;
        $log->save();

        return $log->fresh(['child']);
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
