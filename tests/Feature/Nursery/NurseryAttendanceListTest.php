<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\LeaveRecord;
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
}
