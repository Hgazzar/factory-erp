<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Hr;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\AttendanceLogIngestService;
use App\Services\AttendanceRollupService;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HrTestCase;

final class AttendanceRollupServiceTest extends HrTestCase
{
    private AttendanceRollupService $rollup;

    private AttendanceLogIngestService $ingest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rollup = app(AttendanceRollupService::class);
        $this->ingest = app(AttendanceLogIngestService::class);
    }

    #[Test]
    public function it_marks_present_when_check_in_within_shift_grace(): void
    {
        $shift = $this->createShift(['grace_minutes' => 15]);
        $employee = $this->createEmployee($shift);
        $workDate = '2026-05-01';

        $this->ingestLogs($employee, $workDate, '08:10:00', '17:05:00');

        $attendance = $this->attendanceFor($employee, $workDate);

        $this->assertSame(Attendance::STATUS_PRESENT, $attendance->status);
        $this->assertSame(0, $attendance->minutes_late);
        $this->assertSame(0, $attendance->minutes_early_departure);
        $this->assertSame($shift->id, $attendance->shift_id);
    }

    #[Test]
    public function it_calculates_late_minutes_after_shift_grace(): void
    {
        $shift = $this->createShift(['start_time' => '08:00:00', 'grace_minutes' => 10]);
        $employee = $this->createEmployee($shift);
        $workDate = '2026-05-02';

        $this->ingestLogs($employee, $workDate, '08:25:00', '17:00:00');

        $attendance = $this->attendanceFor($employee, $workDate);

        $this->assertSame(Attendance::STATUS_LATE, $attendance->status);
        $this->assertSame(25, $attendance->minutes_late);
    }

    #[Test]
    public function it_calculates_early_departure_minutes_before_shift_end_grace(): void
    {
        $shift = $this->createShift(['end_time' => '17:00:00', 'grace_minutes' => 10]);
        $employee = $this->createEmployee($shift);
        $workDate = '2026-05-03';

        $this->ingestLogs($employee, $workDate, '08:00:00', '16:30:00');

        $attendance = $this->attendanceFor($employee, $workDate);

        $this->assertSame(30, $attendance->minutes_early_departure);
        $this->assertSame(Attendance::STATUS_PRESENT, $attendance->status);
    }

    #[Test]
    public function it_falls_back_to_config_when_employee_has_no_shift(): void
    {
        config(['attendance.scheduled_start' => '09:00', 'attendance.grace_minutes' => 0]);
        $employee = $this->createEmployee(null);
        $workDate = '2026-05-04';

        $this->ingestLogs($employee, $workDate, '09:20:00', '17:00:00');

        $attendance = $this->attendanceFor($employee, $workDate);

        $this->assertNull($attendance->shift_id);
        $this->assertSame(Attendance::STATUS_LATE, $attendance->status);
        $this->assertSame(20, $attendance->minutes_late);
    }

    #[Test]
    public function payroll_calculation_aggregates_early_departure_hours_for_payroll(): void
    {
        $shift = $this->createShift(['end_time' => '17:00:00', 'grace_minutes' => 10]);
        $employee = $this->createEmployee($shift);
        $workDate = '2026-05-05';

        $this->ingestLogs($employee, $workDate, '08:00:00', '16:00:00');

        $result = app(PayrollCalculationService::class)->calculateForEmployee(
            $employee->fresh(),
            $this->tenant->id,
            2026,
            5
        );

        $this->assertEqualsWithDelta(1.0, (float) $result['slip']['early_departure_hours'], 0.01);
        $this->assertGreaterThan(0, (float) $result['slip']['attendance_deductions']);
    }

    private function ingestLogs(Employee $employee, string $workDate, string $checkIn, string $checkOut): void
    {
        $tz = config('app.timezone');

        $this->ingest->ingest(
            $this->tenant->id,
            (string) $employee->attendance_device_id,
            Carbon::parse($workDate.' '.$checkIn, $tz),
            AttendanceLog::DIRECTION_IN,
            AttendanceLog::SOURCE_API_SYNC,
            deferRollup: true,
        );

        $this->ingest->ingest(
            $this->tenant->id,
            (string) $employee->attendance_device_id,
            Carbon::parse($workDate.' '.$checkOut, $tz),
            AttendanceLog::DIRECTION_OUT,
            AttendanceLog::SOURCE_API_SYNC,
            deferRollup: true,
        );

        $this->rollup->rollupEmployeeDay($this->tenant->id, $employee->id, $workDate);
    }

    private function attendanceFor(Employee $employee, string $workDate): Attendance
    {
        $attendance = Attendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->first();

        $this->assertNotNull($attendance);

        return $attendance;
    }
}
