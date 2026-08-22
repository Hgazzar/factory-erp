<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\LeaveRecord;
use App\Models\Nursery\StaffAttendanceLog;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryAttendanceListTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_view_weekly_children_attendance_and_register_leave(): void
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي',
            'phone' => '0500000001',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'فهد',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        AttendanceLog::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'attendance_date' => now()->startOfWeek()->toDateString(),
            'checked_in_at' => now(),
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);

        $this->get(route('nursery.attendance.index', ['tab' => 'children']))
            ->assertOk()
            ->assertSee('فهد')
            ->assertSee('حاضر');

        $this->post(route('nursery.attendance.leaves.store'), [
            'scope' => LeaveRecord::SCOPE_CHILDREN,
            'name' => 'إجازة عائلية',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
            'child_ids' => [$child->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('nursery_leave_records', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'name' => 'إجازة عائلية',
        ]);
    }

    #[Test]
    public function admin_can_view_staff_weekly_attendance(): void
    {
        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'code' => 'EMP-001',
            'name' => 'علي محمد',
            'status' => 'active',
        ]);

        $this->get(route('nursery.attendance.index', ['tab' => 'staff']))
            ->assertOk()
            ->assertSee('علي محمد');
    }

    #[Test]
    public function staff_weekly_grid_shows_check_in_even_on_non_workday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 10:15:00')); // السبت — خارج أيام العمل الافتراضية

        try {
            $employee = Employee::query()->create([
                'user_id' => (int) $this->tenant->id,
                'code' => 'EMP-SAT',
                'name' => 'موظف السبت',
                'status' => 'active',
            ]);

            StaffAttendanceLog::query()->create([
                'user_id' => (int) $this->tenant->id,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-08-22',
                'checked_in_at' => Carbon::parse('2026-08-22 08:45:00'),
                'checked_out_at' => Carbon::parse('2026-08-22 08:59:00'),
                'status' => StaffAttendanceLog::STATUS_PRESENT,
            ]);

            $persisted = StaffAttendanceLog::query()->where('employee_id', $employee->id)->first();
            $this->assertNotNull($persisted);
            $this->assertSame('2026-08-22', $persisted->attendance_date->toDateString());
            $this->assertNotNull($persisted->checked_in_at);

            $grid = app(\App\Services\Nursery\NurseryAttendanceListService::class)
                ->staffWeeklyGrid((int) $this->tenant->id, Carbon::parse('2026-08-16'));

            $row = collect($grid['rows'])->firstWhere('id', $employee->id);
            $this->assertNotNull($row);
            $saturday = collect($row['cells'])->firstWhere('date', '2026-08-22');
            $this->assertSame('present', $saturday['state']);
            $this->assertSame('حاضر', $saturday['label']);
            $this->assertNotNull($saturday['detail']);

            $summary = collect($grid['day_summaries'])->firstWhere('date', '2026-08-22');
            $this->assertGreaterThanOrEqual(1, (int) ($summary['present'] ?? 0));

            $this->get(route('nursery.attendance.index', [
                'tab' => 'staff',
                'week' => '2026-08-16',
            ]))
                ->assertOk()
                ->assertSee('موظف السبت', false)
                ->assertSee('حاضر', false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
