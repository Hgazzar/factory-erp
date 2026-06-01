<?php

declare(strict_types=1);

namespace App\Services\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * يمنع تسجيل الإنتاج اللحظي إذا لم يكن للموظف حضور صالح في تاريخ العمل.
 */
final class ProductionEntryAttendanceGuard
{
    public const BLOCK_MESSAGE = 'عذراً، لا يمكنك تسجيل حركة إنتاج للموظف وهو مسجل (غائب) في نظام الحضور والانصراف اليوم';

    public function assertEligible(?User $user, int $tenantUserId, CarbonInterface $workDate): void
    {
        if ($user === null) {
            return;
        }

        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('linked_user_id', $user->id)
            ->first();

        if ($employee === null) {
            if ($user->role === 'worker') {
                throw new RuntimeException('لا يوجد ملف موظف مرتبط بحسابك. اطلب من الإدارة ربط حسابك بسجل موظف قبل تسجيل الإنتاج.');
            }

            return;
        }

        if (($employee->status ?? 'active') !== 'active') {
            throw new RuntimeException(self::BLOCK_MESSAGE);
        }

        $attendance = Attendance::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate->toDateString())
            ->first();

        if ($attendance === null) {
            throw new RuntimeException(self::BLOCK_MESSAGE);
        }

        if (! in_array($attendance->status, [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE], true)) {
            throw new RuntimeException(self::BLOCK_MESSAGE);
        }
    }
}
