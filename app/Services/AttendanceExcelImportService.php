<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;

final class AttendanceExcelImportService
{
    public function __construct(
        private readonly AttendanceRollupService $rollup,
    ) {}

    /**
     * @param  list<list<mixed>>  $rows  الصف 0 = عناوين الأعمدة
     * @return array{success: int, failed: list<array{row: int, reason: string, detail?: string}>}
     */
    public function importMappedRows(
        int $userId,
        array $rows,
        int $deviceColumnIndex,
        int $datetimeColumnIndex,
    ): array {
        $failed = [];
        $pairs = [];
        $success = 0;

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $deviceRaw = $row[$deviceColumnIndex] ?? null;
            $dtRaw = $row[$datetimeColumnIndex] ?? null;
            $deviceId = is_scalar($deviceRaw) ? trim((string) $deviceRaw) : '';

            if ($deviceId === '' && ($dtRaw === null || $dtRaw === '')) {
                continue;
            }

            if ($deviceId === '') {
                $failed[] = ['row' => $i + 1, 'reason' => 'empty_device', 'detail' => 'رقم البصمة فارغ'];

                continue;
            }

            $loggedAt = AttendanceSpreadsheetReader::parseCellToCarbon($dtRaw);
            if ($loggedAt === null) {
                $failed[] = ['row' => $i + 1, 'reason' => 'invalid_datetime', 'detail' => 'تعذّر قراءة التاريخ/الوقت'];

                continue;
            }

            $employee = Employee::findForAttendanceKey($userId, $deviceId);

            if ($employee === null) {
                $failed[] = ['row' => $i + 1, 'reason' => 'unknown_employee_code', 'detail' => $deviceId];

                continue;
            }

            AttendanceLog::withoutGlobalScopes()->create([
                'user_id' => $userId,
                'attendance_id' => null,
                'employee_id' => $employee->id,
                'employee_device_id' => $deviceId,
                'logged_at' => $loggedAt,
                'direction' => AttendanceLog::DIRECTION_IN,
                'source' => AttendanceLog::SOURCE_EXCEL_IMPORT,
                'meta' => [
                    'spreadsheet_row' => $i + 1,
                ],
            ]);

            $pairs[] = [
                'employee_id' => $employee->id,
                'work_date' => $loggedAt->copy()->timezone(config('app.timezone'))->toDateString(),
            ];
            $success++;
        }

        $this->rollup->rollupPairs($userId, $pairs);

        return ['success' => $success, 'failed' => $failed];
    }
}
