<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Tenant\TenantNavigationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySaaSIsolationTest extends NurseryTestCase
{
    #[Test]
    public function nursery_owner_login_lands_on_nursery_dashboard(): void
    {
        Auth::logout();

        $this->post('/login', [
            'email' => $this->tenant->email,
            'password' => 'password',
        ])->assertRedirect(route('nursery.dashboard'));
    }

    #[Test]
    public function nursery_owner_dashboard_route_redirects_to_nursery_entry(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('nursery.dashboard'));
    }

    #[Test]
    public function nursery_shell_hides_global_saas_launcher_and_back_to_units(): void
    {
        $html = $this->get(route('nursery.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="erpModuleLauncherModal"', $html);
        $this->assertStringNotContainsString('erp-module-launcher-trigger', $html);
        $this->assertStringNotContainsString('العودة للوحدات', $html);
        $this->assertStringNotContainsString('ابحث عن عميل، فاتورة، صنف', $html);
        $this->assertStringContainsString(route('nursery.dashboard'), $html);
    }

    #[Test]
    public function teacher_sidebar_shows_ops_without_finance_or_settings(): void
    {
        $staff = $this->makeStaff([
            'login.app',
            'children.manage',
            'classrooms.assign_child',
            'attendance.children',
            'activities.manage',
        ], 'teacher');

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.children.index'), false)
            ->assertSee(route('nursery.classrooms.index'), false)
            ->assertSee(route('nursery.attendance.index'), false)
            ->assertDontSee(route('nursery.finance.index'), false)
            ->assertDontSee(route('finance.dashboard'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('fleet.dashboard'), false)
            ->assertDontSee(route('clinic.dashboard'), false)
            ->assertDontSee(route('pos.dashboard'), false);
    }

    #[Test]
    public function reception_sidebar_shows_subscriptions_without_finance_ledger(): void
    {
        $staff = $this->makeStaff([
            'login.app',
            'children.manage',
            'children.parents_page',
            'attendance.children',
            'subscriptions.manage',
            'classrooms.assign_child',
        ], 'reception');

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.subscriptions.index'), false)
            ->assertSee(route('nursery.guardians.index'), false)
            ->assertDontSee(route('finance.accounts.index'), false)
            ->assertDontSee(route('nursery.finance.index'), false)
            ->assertDontSee(route('fleet.dashboard'), false);
    }

    #[Test]
    public function accountant_sidebar_shows_finance_without_nursery_settings(): void
    {
        $staff = $this->makeStaff([
            'login.app',
            'finance.view',
            'finance.view_reports',
            'finance.manage_expenses',
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.finance.index'), false)
            ->assertSee(route('finance.dashboard'), false)
            ->assertSee(route('finance.expenses.index'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('finance.accounts.index'), false)
            ->assertDontSee(route('clinic.dashboard'), false);
    }

    #[Test]
    public function view_only_sidebar_hides_management_and_finance_links(): void
    {
        $staff = $this->makeStaff(['login.app']);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.classrooms.index'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('nursery.finance.index'), false)
            ->assertDontSee(route('finance.dashboard'), false)
            ->assertDontSee(route('nursery.staff.index'), false);
    }

    #[Test]
    public function nursery_owner_sidebar_hides_non_nursery_module_links(): void
    {
        $response = $this->get(route('nursery.dashboard'))->assertOk();

        $response->assertDontSee(route('fleet.dashboard'), false);
        $response->assertDontSee(route('clinic.dashboard'), false);
        if (Route::has('pos.dashboard')) {
            $response->assertDontSee(route('pos.dashboard'), false);
        }
        if (Route::has('manufacturing.dashboard')) {
            $response->assertDontSee(route('manufacturing.dashboard'), false);
        }
        $response->assertDontSee(route('settings.store.edit'), false);
    }

    #[Test]
    public function direct_urls_to_foreign_modules_are_denied_for_nursery_tenant(): void
    {
        $foreign = [
            '/fleet/dashboard',
            '/clinic/dashboard',
            '/pos/dashboard',
            '/manufacturing',
        ];

        foreach ($foreign as $path) {
            $response = $this->get($path);
            $this->assertTrue(
                in_array($response->status(), [302, 403], true),
                "Expected deny for {$path}, got {$response->status()}"
            );
            if ($response->isRedirect()) {
                $this->assertSame(
                    route('nursery.dashboard'),
                    $response->headers->get('Location'),
                    "Foreign module {$path} should redirect to nursery home"
                );
            }
        }
    }

    #[Test]
    public function cross_tenant_child_access_is_denied(): void
    {
        $other = User::factory()->create(['role' => 'admin']);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $other->id,
            ['core', 'nursery', 'finance']
        );
        TenantProfile::query()->create([
            'tenant_user_id' => (int) $other->id,
            'niche_key' => 'nurseries',
            'domain' => 'other-nursery-iso',
            'slug' => 'other-nursery-iso',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $guardian = Guardian::withoutGlobalScopes()->create([
            'user_id' => (int) $other->id,
            'name' => 'ولي آخر',
            'phone' => '0501111222',
        ]);
        $child = Child::withoutGlobalScopes()->create([
            'user_id' => (int) $other->id,
            'guardian_id' => $guardian->id,
            'name' => 'طفل مستأجر آخر',
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->get(route('nursery.children.show', $child))->assertForbidden();
        $this->assertFalse(Child::query()->whereKey($child->id)->exists());
    }

    #[Test]
    public function finance_permissions_remain_valid_for_accountant(): void
    {
        $staff = $this->makeStaff([
            'login.app',
            'finance.view',
            'finance.manage_expenses',
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.finance.index'))->assertOk();
        $expenses = $this->get(route('finance.expenses.index'));
        $this->assertNotSame(403, $expenses->status());
        $this->get(route('finance.accounts.index'))->assertForbidden();
    }

    #[Test]
    public function other_niche_still_lands_on_erp_dashboard(): void
    {
        $retail = User::factory()->create(['role' => 'admin', 'password' => 'password']);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $retail->id,
            ['core', 'finance', 'pos', 'inventory', 'sales']
        );
        TenantProfile::query()->create([
            'tenant_user_id' => (int) $retail->id,
            'niche_key' => 'retail',
            'domain' => 'retail-iso',
            'slug' => 'retail-iso',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $this->actingAs($retail);

        $this->assertSame(
            route('dashboard', absolute: false),
            app(TenantNavigationService::class)->defaultHomeRoute($retail)
        );

        $this->get(route('dashboard'))->assertOk();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaff(array $permissions, ?string $role = null): User
    {
        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'iso-'.uniqid().'@example.com',
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-ISO-'.strtoupper(substr(uniqid(), -4)),
            'name' => 'Isolation Staff',
            'email' => $staff->email,
            'status' => 'active',
            'nursery_role' => $role,
            'nursery_permissions' => $permissions,
        ]);

        return $staff;
    }
}
