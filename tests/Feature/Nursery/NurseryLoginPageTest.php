<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryLoginPageTest extends NurseryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    #[Test]
    public function nursery_login_page_renders_nursery_branding(): void
    {
        $this->get(route('nursery.login'))
            ->assertOk()
            ->assertSee('نظام الحضانة', false)
            ->assertSee('دخول إلى الحضانة', false)
            ->assertSee(route('nursery.login.store'), false)
            ->assertDontSee('نظام إدارة الأعمال', false)
            ->assertDontSee('حضانة فقط', false)
            ->assertDontSee('هذه الصفحة مخصّصة لحسابات نظام الحضانة فقط.', false);
    }

    #[Test]
    public function nursery_login_accepts_nursery_owner_and_lands_on_dashboard(): void
    {
        $this->tenant->forceFill([
            'password' => Hash::make('password'),
        ])->save();

        $this->post(route('nursery.login.store'), [
            'email' => $this->tenant->email,
            'password' => 'password',
        ])->assertRedirect(route('nursery.dashboard'));

        $this->assertAuthenticatedAs($this->tenant);
    }

    #[Test]
    public function nursery_login_rejects_non_nursery_tenant(): void
    {
        $retail = User::factory()->create([
            'role' => 'admin',
            'email' => 'retail-login@example.com',
            'password' => Hash::make('password'),
        ]);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $retail->id, [
            'core', 'finance', 'pos', 'inventory', 'sales',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => (int) $retail->id,
            'niche_key' => 'retail',
            'domain' => 'retail-login-page',
            'slug' => 'retail-login-page',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $this->from(route('nursery.login'))
            ->post(route('nursery.login.store'), [
                'email' => $retail->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('nursery.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
