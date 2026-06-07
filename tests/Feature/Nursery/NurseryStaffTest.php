<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryStaffTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_create_staff_with_permissions_and_attachments_meta(): void
    {
        $this->get(route('nursery.staff.index'))->assertOk()->assertSee('طاقم العمل');
        $this->get(route('nursery.staff.create'))->assertOk()->assertSee('الصلاحيات');

        $this->post(route('nursery.staff.store'), [
            'first_name' => 'سارة',
            'last_name' => 'أحمد',
            'email' => 'sara@example.com',
            'mobile' => '0501234567',
            'nursery_job_role' => 'teacher',
            'nursery_role' => 'teacher',
            'permissions' => ['login.app', 'children.manage', 'attendance.children'],
        ])->assertRedirect(route('nursery.staff.index'));

        $emp = Employee::query()->where('email', 'sara@example.com')->first();
        $this->assertNotNull($emp);
        $this->assertContains('children.manage', $emp->nursery_permissions);
    }
}
