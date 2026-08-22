<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryAttendanceAuthorizationTest extends NurseryTestCase
{
    #[Test]
    public function login_app_cannot_post_child_attendance(): void
    {
        $staff = $this->makeLinkedStaff('login-only@example.com', ['login.app']);
        $child = $this->makeChild();

        $this->actingAs($staff);

        $this->get(route('nursery.attendance.index'))->assertOk();

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertForbidden();
    }

    #[Test]
    public function manage_child_attendance_can_post_child_attendance(): void
    {
        $staff = $this->makeLinkedStaff('child-att@example.com', ['login.app', 'attendance.children']);
        $child = $this->makeChild();

        $this->actingAs($staff);

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
        ]);
    }

    #[Test]
    public function manage_staff_attendance_controls_staff_check_in(): void
    {
        $loginOnly = $this->makeLinkedStaff('staff-login@example.com', ['login.app']);
        $manager = $this->makeLinkedStaff('staff-mgr@example.com', ['login.app', 'attendance.staff'], 'EMP-00022');
        $target = Employee::query()->where('linked_user_id', $loginOnly->id)->firstOrFail();

        $this->actingAs($loginOnly);
        $this->post(route('nursery.attendance.staff.check-in'), ['employee_id' => $target->id])
            ->assertForbidden();

        $this->actingAs($manager);
        $this->post(route('nursery.attendance.staff.check-in'), ['employee_id' => $target->id])
            ->assertRedirect();

        $this->assertDatabaseHas('nursery_staff_attendance_logs', [
            'user_id' => $this->tenant->id,
            'employee_id' => $target->id,
        ]);
    }

    #[Test]
    public function worker_role_cannot_access_nursery_routes(): void
    {
        $worker = $this->makeLinkedStaff('worker@example.com', ['login.app', 'attendance.children']);
        $worker->forceFill(['role' => 'worker'])->save();
        $child = $this->makeChild();

        $this->actingAs($worker);
        $this->get(route('nursery.attendance.index'))->assertForbidden();
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeLinkedStaff(string $email, array $permissions, string $code = 'EMP-00021'): User
    {
        $user = User::factory()->create([
            'role' => 'supervisor',
            'email' => $email,
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $user->id,
            'code' => $code,
            'name' => 'Staff '.$email,
            'email' => $email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $user;
    }

    private function makeChild(): Child
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي حضور',
            'phone' => '0503333444',
        ]);

        return Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل حضور',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
    }
}
