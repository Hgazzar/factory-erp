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
        $this->assertDoesNotMatchRegularExpression('/[^A-Za-z0-9]/', (string) $result['temporary_password']);

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
        $this->assertFalse($access->allows(NurseryAccess::CAP_VIEW_ATTENDANCE, $user));
        $this->assertFalse($access->allows(NurseryAccess::CAP_VIEW_CHILDREN, $user));
        $this->assertFalse($access->allows(NurseryAccess::CAP_VIEW_CLASSROOMS, $user));
        $this->assertFalse($access->allows(NurseryAccess::CAP_MANAGE_CHILD_ATTENDANCE, $user));
    }

    #[Test]
    public function two_step_create_then_invite_creates_login_without_500(): void
    {
        $this->post(route('nursery.staff.store'), [
            'first_name' => 'ليان',
            'last_name' => 'عادل',
            'mobile' => '0503333444',
            'permissions' => ['attendance.children'],
        ])->assertRedirect(route('nursery.staff.index'));

        $employee = Employee::query()->where('mobile', '0503333444')->firstOrFail();
        $this->assertNull($employee->linked_user_id);
        $this->assertNull($employee->email);

        $this->from(route('nursery.staff.edit', $employee))
            ->put(route('nursery.staff.update', $employee), [
                'first_name' => 'ليان',
                'last_name' => 'عادل',
                'mobile' => '0503333444',
                'email' => 'layan-invite@example.com',
                'permissions' => ['attendance.children'],
                'submit_action' => 'save_and_invite',
            ])
            ->assertRedirect(route('nursery.staff.index'))
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $employee->refresh();
        $this->assertNotNull($employee->linked_user_id);
        $this->assertContains('login.app', $employee->nursery_permissions ?? []);

        $user = User::query()->find($employee->linked_user_id);
        $this->assertNotNull($user);
        $this->assertSame('layan-invite@example.com', $user->email);
        $this->assertSame('supervisor', $user->role);
    }

    #[Test]
    public function duplicate_email_on_invite_returns_flash_not_500(): void
    {
        User::factory()->create([
            'email' => 'taken-staff@example.com',
            'role' => 'admin',
            'password' => 'password',
        ]);

        $employee = $this->makeEmployee('other-mail@example.com');

        $this->from(route('nursery.staff.edit', $employee))
            ->put(route('nursery.staff.update', $employee), [
                'first_name' => 'موظف',
                'last_name' => 'اختبار',
                'mobile' => '0502222333',
                'email' => 'taken-staff@example.com',
                'permissions' => ['login.app'],
                'submit_action' => 'save_and_invite',
            ])
            ->assertRedirect(route('nursery.staff.edit', $employee))
            ->assertSessionHas('error');
    }

    #[Test]
    public function invite_flashes_persistent_credentials_card_not_only_auto_dismiss_success(): void
    {
        $this->post(route('nursery.staff.store'), [
            'first_name' => 'ريم',
            'last_name' => 'ناصر',
            'email' => 'reem-creds@example.com',
            'mobile' => '0505555666',
            'permissions' => ['login.app'],
            'submit_action' => 'save_and_invite',
        ])
            ->assertRedirect(route('nursery.staff.index'))
            ->assertSessionHas('staff_login_credentials.email', 'reem-creds@example.com')
            ->assertSessionHas('staff_login_credentials.password');

        $password = (string) session('staff_login_credentials.password');
        $this->assertNotSame('', $password);

        $this->get(route('nursery.staff.index'))
            ->assertOk()
            ->assertSee('بيانات دخول مؤقتة')
            ->assertSee('reem-creds@example.com')
            ->assertSee($password)
            ->assertSee('نسخ البريد وكلمة المرور');
    }

    #[Test]
    public function admin_can_reset_staff_login_password_and_see_new_credentials(): void
    {
        $employee = $this->makeEmployee('reset-me@example.com');
        $created = app(NurseryStaffAccountService::class)->ensureLoginUser($employee);
        $oldPassword = (string) $created['temporary_password'];

        $this->get(route('nursery.staff.edit', $employee))
            ->assertOk()
            ->assertSee('حساب دخول النظام')
            ->assertSee('إعادة كلمة مرور الدخول');

        $this->get(route('nursery.staff.index'))
            ->assertOk()
            ->assertSee('إعادة كلمة مرور الدخول');

        $this->post(route('nursery.staff.reset-login-password', $employee))
            ->assertRedirect(route('nursery.staff.edit', $employee))
            ->assertSessionHas('staff_login_credentials.email', 'reset-me@example.com')
            ->assertSessionHas('staff_login_credentials.password');

        $newPassword = (string) session('staff_login_credentials.password');
        $this->assertNotSame('', $newPassword);
        $this->assertNotSame($oldPassword, $newPassword);

        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => 'reset-me@example.com', 'password' => $oldPassword]));
        $this->assertTrue(Auth::attempt(['email' => 'reset-me@example.com', 'password' => $newPassword]));
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
