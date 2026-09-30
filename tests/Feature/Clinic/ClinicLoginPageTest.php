<?php

declare(strict_types=1);

namespace Tests\Feature\Clinic;

use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\PreferredLoginShell;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicLoginPageTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $this->tenant->id, [
            'core', 'clinic', 'finance', 'hr', 'services', 'crm',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'medical_clinics',
            'domain' => 'clinic-login-page',
            'slug' => 'clinic-login-page',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        Auth::logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    #[Test]
    public function clinic_login_page_renders_clinic_branding(): void
    {
        $this->get(route('clinic.login'))
            ->assertOk()
            ->assertSee('نظام العيادة', false)
            ->assertSee('دخول إلى العيادة', false)
            ->assertSee(route('clinic.login.store'), false)
            ->assertDontSee('نظام إدارة الأعمال', false)
            ->assertDontSee('نظام الحضانة', false)
            ->assertDontSee('هذه الصفحة مخصّصة لحسابات نظام العيادة فقط.', false);
    }

    #[Test]
    public function clinic_login_accepts_clinic_owner_and_lands_on_dashboard(): void
    {
        $this->tenant->forceFill([
            'password' => Hash::make('password'),
        ])->save();

        $this->post(route('clinic.login.store'), [
            'email' => $this->tenant->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('clinic.dashboard'))
            ->assertCookie(PreferredLoginShell::COOKIE, PreferredLoginShell::CLINIC);

        $this->assertAuthenticatedAs($this->tenant);
    }

    #[Test]
    public function clinic_logout_and_expired_clinic_route_return_to_clinic_login(): void
    {
        $this->actingAs($this->tenant)
            ->post(route('logout'))
            ->assertRedirect(route('clinic.login'));

        $this->assertGuest();

        $this->get(route('clinic.dashboard'))
            ->assertRedirect(route('clinic.login'));
    }

    #[Test]
    public function clinic_login_rejects_non_clinic_tenant(): void
    {
        $retail = User::factory()->create([
            'role' => 'admin',
            'email' => 'retail-clinic-login@example.com',
            'password' => Hash::make('password'),
        ]);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $retail->id, [
            'core', 'finance', 'pos', 'inventory', 'sales',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $retail->id,
            'niche_key' => 'retail',
            'domain' => 'retail-clinic-login',
            'slug' => 'retail-clinic-login',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $this->from(route('clinic.login'))
            ->post(route('clinic.login.store'), [
                'email' => $retail->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('clinic.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
