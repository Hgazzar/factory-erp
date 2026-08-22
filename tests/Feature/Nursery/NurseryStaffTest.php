<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\User;
use App\Support\NurseryPermissionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryStaffTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_create_staff_with_permissions_and_attachments_meta(): void
    {
        $this->get(route('nursery.staff.index'))->assertOk()->assertSee('طاقم العمل');
        $this->get(route('nursery.staff.create'))->assertOk()->assertSee('الصلاحيات');

        $response = $this->post(route('nursery.staff.store'), [
            'first_name' => 'سارة',
            'last_name' => 'أحمد',
            'email' => 'sara@example.com',
            'mobile' => '0501234567',
            'nursery_job_role' => 'teacher',
            'nursery_role' => 'teacher',
            'permissions' => ['login.app', 'children.manage', 'attendance.children'],
        ]);

        $emp = Employee::query()->where('email', 'sara@example.com')->first();
        $this->assertNotNull($emp);
        $response->assertRedirect(route('nursery.staff.index'));
        $this->assertContains('children.manage', $emp->nursery_permissions);
        $this->assertNotNull($emp->linked_user_id);

        $user = User::query()->find($emp->linked_user_id);
        $this->assertNotNull($user);
        $this->assertSame('supervisor', $user->role);
        $this->assertSame('sara@example.com', $user->email);
    }

    #[Test]
    public function staff_form_exposes_child_and_staff_attendance_permissions(): void
    {
        $this->get(route('nursery.staff.create'))
            ->assertOk()
            ->assertSee('إدارة حضور الأطفال')
            ->assertSee('إدارة حضور طاقم العمل')
            ->assertSee('value="attendance.children"', false)
            ->assertSee('value="attendance.staff"', false)
            ->assertSee('attendance.children', false);
    }

    #[Test]
    public function owner_can_grant_child_and_staff_attendance_from_staff_form(): void
    {
        $response = $this->post(route('nursery.staff.store'), [
            'first_name' => 'منى',
            'last_name' => 'خالد',
            'email' => 'mona-att@example.com',
            'mobile' => '0501234001',
            'permissions' => ['login.app', 'attendance.children', 'attendance.staff'],
        ]);

        $emp = Employee::query()->where('email', 'mona-att@example.com')->first();
        $this->assertNotNull($emp);
        $response->assertRedirect(route('nursery.staff.index'));
        $this->assertContains('attendance.children', $emp->nursery_permissions);
        $this->assertContains('attendance.staff', $emp->nursery_permissions);
    }

    #[Test]
    public function create_with_teacher_role_and_empty_permissions_uses_template(): void
    {
        $response = $this->post(route('nursery.staff.store'), [
            'first_name' => 'لينا',
            'last_name' => 'معلم',
            'email' => 'lina-teacher@example.com',
            'mobile' => '0501234002',
            'nursery_role' => 'teacher',
        ]);

        $emp = Employee::query()->where('email', 'lina-teacher@example.com')->first();
        $this->assertNotNull($emp);
        $response->assertRedirect(route('nursery.staff.index'));
        $this->assertSame(NurseryPermissionCatalog::templateForRole('teacher'), $emp->nursery_permissions);
        $this->assertContains('attendance.children', $emp->nursery_permissions);
        $this->assertNotContains('attendance.staff', $emp->nursery_permissions);
    }

    #[Test]
    public function update_does_not_restore_teacher_template_when_attendance_is_unchecked(): void
    {
        $create = $this->post(route('nursery.staff.store'), [
            'first_name' => 'هند',
            'last_name' => 'سعد',
            'email' => 'hind-upd@example.com',
            'mobile' => '0501234003',
            'nursery_role' => 'teacher',
            'permissions' => ['login.app', 'children.manage', 'attendance.children'],
        ]);

        $emp = Employee::query()->where('email', 'hind-upd@example.com')->firstOrFail();
        $create->assertRedirect(route('nursery.staff.index'));

        $this->put(route('nursery.staff.update', $emp), [
            'first_name' => 'هند',
            'last_name' => 'سعد',
            'email' => 'hind-upd@example.com',
            'mobile' => '0501234003',
            'nursery_role' => 'teacher',
            'permissions' => ['login.app', 'children.manage'],
            'status' => 'active',
        ])->assertRedirect(route('nursery.staff.index'));

        $emp->refresh();
        $this->assertContains('login.app', $emp->nursery_permissions);
        $this->assertContains('children.manage', $emp->nursery_permissions);
        $this->assertNotContains('attendance.children', $emp->nursery_permissions);
    }

    #[Test]
    public function update_can_add_and_remove_attendance_children(): void
    {
        $create = $this->post(route('nursery.staff.store'), [
            'first_name' => 'ريم',
            'last_name' => 'علي',
            'email' => 'reem-cap@example.com',
            'mobile' => '0501234004',
            'permissions' => ['login.app'],
        ]);

        $emp = Employee::query()->where('email', 'reem-cap@example.com')->firstOrFail();
        $create->assertRedirect(route('nursery.staff.index'));

        $this->put(route('nursery.staff.update', $emp), [
            'first_name' => 'ريم',
            'last_name' => 'علي',
            'email' => 'reem-cap@example.com',
            'mobile' => '0501234004',
            'permissions' => ['login.app', 'attendance.children'],
            'status' => 'active',
        ])->assertRedirect(route('nursery.staff.index'));

        $this->assertContains('attendance.children', $emp->fresh()->nursery_permissions);

        $this->put(route('nursery.staff.update', $emp), [
            'first_name' => 'ريم',
            'last_name' => 'علي',
            'email' => 'reem-cap@example.com',
            'mobile' => '0501234004',
            'permissions' => ['login.app'],
            'status' => 'active',
        ])->assertRedirect(route('nursery.staff.index'));

        $this->assertNotContains('attendance.children', $emp->fresh()->nursery_permissions);
    }

    #[Test]
    public function update_preserves_permissions_the_editor_cannot_grant(): void
    {
        $create = $this->post(route('nursery.staff.store'), [
            'first_name' => 'هدف',
            'last_name' => 'حفظ',
            'email' => 'target-keep@example.com',
            'mobile' => '0501234005',
            'permissions' => ['login.app', 'settings.manage'],
        ]);

        $target = Employee::query()->where('email', 'target-keep@example.com')->firstOrFail();
        $create->assertRedirect(route('nursery.staff.index'));

        $editor = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'editor-staff@example.com',
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $editor->id,
            'code' => 'EMP-EDT01',
            'name' => 'محرر صلاحيات',
            'first_name' => 'محرر',
            'last_name' => 'صلاحيات',
            'email' => 'editor-staff@example.com',
            'mobile' => '0501234006',
            'status' => 'active',
            'nursery_permissions' => ['login.app', 'employees.manage'],
        ]);

        $this->actingAs($editor);

        $this->put(route('nursery.staff.update', $target), [
            'first_name' => 'هدف',
            'last_name' => 'حفظ',
            'email' => 'target-keep@example.com',
            'mobile' => '0501234005',
            'permissions' => ['login.app', 'employees.manage'],
            'status' => 'active',
        ])->assertRedirect(route('nursery.staff.index'));

        $kept = $target->fresh()->nursery_permissions;
        $this->assertContains('settings.manage', $kept);
        $this->assertContains('login.app', $kept);
        $this->assertContains('employees.manage', $kept);
    }
}
