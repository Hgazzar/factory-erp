<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\CarbonInterface;

final class AttendanceLogIngestService
{
    public const REASON_EMPTY_DEVICE = 'empty_device';

    public const REASON_UNKNOWN_EMPLOYEE_CODE = 'unknown_employee_code';

    public const REASON_INVALID_DIRECTION = 'invalid_direction';

    public function __construct(
        private readonly AttendanceRollupService $rollup,
    ) {}

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array{ok: bool, reason?: string, employee_id?: int, work_date?: string}
     */
    public function ingest(
        int $userId,
        string $employeeDeviceId,
        CarbonInterface $loggedAt,
        string $direction,
        string $source,
        ?array $meta = null,
        bool $deferRollup = false,
    ): array {
        $deviceId = trim($employeeDeviceId);
        if ($deviceId === '') {
            return ['ok' => false, 'reason' => self::REASON_EMPTY_DEVICE];
        }

        if (! in_array($direction, [AttendanceLog::DIRECTION_IN, AttendanceLog::DIRECTION_OUT], true)) {
            return ['ok' => false, 'reason' => self::REASON_INVALID_DIRECTION];
        }

        $employee = Employee::findForAttendanceKey($userId, $deviceId);

        if ($employee === null) {
            return ['ok' => false, 'reason' => self::REASON_UNKNOWN_EMPLOYEE_CODE];
        }

        AttendanceLog::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'attendance_id' => null,
            'employee_id' => $employee->id,
            'employee_device_id' => $deviceId,
            'logged_at' => $loggedAt,
            'direction' => $direction,
            'source' => $source,
            'meta' => $meta,
        ]);

        $workDate = $loggedAt->copy()->timezone(config('app.timezone'))->toDateString();

        if (! $deferRollup) {
            $this->rollup->rollupEmployeeDay($userId, $employee->id, $workDate);
        }

        return [
            'ok' => true,
            'employee_id' => $employee->id,
            'work_date' => $workDate,
        ];
    }
}
