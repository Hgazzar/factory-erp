<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\User;
use App\Services\Nursery\NurseryStaffAccountService;
use App\Support\NurseryAccess;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryStaffAccountTest extends NurseryTestCase
{
    #[Test]
    public function staff_creation_creates_and_links_user(): void
    {
        $this->post(route('nursery.staff.store'), [
            'first_name' => 'هدى',
            'last_name' => 'سالم',
            'email' => 'huda@example.com',
            'mobile' => '0501111000',
            'permissions' => ['login.app'],
        ])->assertRedirect(route('nursery.staff.index'));

        $employee = Employee::query()->where('email', 'huda@example.com')->first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->linked_user_id);

        $user = User::query()->find($employee->linked_user_id);
        $this->assertNotNull($user);
        $this->assertSame('supervisor', $user->role);
        $this->assertSame(1, User::query()->where('email', 'huda@example.com')->count());
    }

    #[Test]
    public function existing_linked_user_is_idempotent(): void
    {
        $employee = $this->makeEmployee('idempotent@example.com');
        $service = app(NurseryStaffAccountService::class);

        $first = $service->ensureLoginUser($employee);
        $second = $service->ensureLoginUser($employee->fresh());

        $this->assertSame($first['user']->id, $second['user']->id);
        $this->assertFalse($second['created']);
        $this->assertNull($second['temporary_password']);
        $this->assertSame(1, User::query()->where('email', 'idempotent@example.com')->count());
    }

    #[Test]
    public function duplicate_email_cannot_steal_another_employee_account(): void
    {
        $first = $this->makeEmployee('shared@example.com');
        app(NurseryStaffAccountService::class)->ensureLoginUser($first);

        $second = $this->makeEmployee('shared@example.com', 'EMP-00099');

        $this->expectException(InvalidArgumentException::class);
        app(NurseryStaffAccountService::class)->ensureLoginUser($second);
    }

    #[Test]
    public function existing_unrelated_admin_is_not_stolen(): void
    {
        $employee = $this->makeEmployee((string) $this->tenant->email);

        $this->expectException(InvalidArgumentException::class);
        app(NurseryStaffAccountService::class)->ensureLoginUser($employee);
    }

    #[Test]
    public function created_staff_user_can_authenticate_and_use_nursery_access(): void
    {
        $employee = $this->makeEmployee('login-staff@example.com');
        $employee->forceFill(['nursery_permissions' => ['login.app']])->save();

        $result = app(NurseryStaffAccountService::class)->ensureLoginUser($employee);
        $this->assertTrue($result['created']);
        $this->assertNotEmpty($result['temporary_password']);

        Auth::logout();
        $this->assertTrue(Auth::attempt([
            'email' => 'login-staff@example.com',
            'password' => $result['temporary_password'],
        ]));

        $user = Auth::user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('supervisor', $user->role);

        $access = app(NurseryAccess::class);
        $this->assertTrue($access->allows(NurseryAccess::CAP_VIEW_DAILY, $user));
        $this->assertFalse($access->allows(NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE, $user));
    }

    private function makeEmployee(string $email, string $code = 'EMP-00010'): Employee
    {
        return Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'code' => $code,
            'name' => 'موظف اختبار',
            'first_name' => 'موظف',
            'last_name' => 'اختبار',
            'email' => $email,
            'mobile' => '0502222333',
            'status' => 'active',
            'nursery_permissions' => ['login.app'],
        ]);
    }
}
