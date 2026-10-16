<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySidebarNavigationTest extends NurseryTestCase
{
    #[Test]
    public function nurseries_sidebar_shows_full_nursery_ops_links_for_owner(): void
    {
        $response = $this->get(route('nursery.dashboard'));

        $response->assertOk();
        $response->assertSee(route('nursery.dashboard'), false);
        $response->assertSee(route('nursery.attendance.index'), false);
        $response->assertSee(route('nursery.children.index'), false);
        $response->assertSee(route('nursery.guardians.index'), false);
        $response->assertSee(route('nursery.staff.index'), false);
        $response->assertSee(route('nursery.classrooms.index'), false);
        $response->assertSee(route('nursery.units.index'), false);
        $response->assertSee(route('nursery.calendar.index'), false);
        $response->assertSee(route('nursery.subscriptions.index'), false);
        $response->assertSee(route('nursery.finance.index'), false);
        $response->assertSee(route('nursery.settings.index'), false);
        $response->assertDontSee(route('clinic.dashboard'), false);
        $response->assertDontSee(route('fleet.dashboard'), false);
        $response->assertSee('id="nurseryMobileSidebar"', false);
        $response->assertSee('data-bs-target="#nurseryMobileSidebar"', false);
    }

    #[Test]
    public function login_app_only_staff_does_not_see_other_modules_in_nav(): void
    {
        $staff = \App\Models\User::factory()->create([
            'role' => 'supervisor',
            'email' => 'nav-view@example.com',
            'password' => 'password',
        ]);

        \App\Models\Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-NAV1',
            'name' => 'Nav Staff',
            'email' => 'nav-view@example.com',
            'status' => 'active',
            'nursery_permissions' => ['login.app'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertDontSee(route('nursery.classrooms.index'), false)
            ->assertDontSee(route('nursery.attendance.index'), false)
            ->assertDontSee(route('nursery.children.index'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('nursery.finance.index'), false);
    }

    #[Test]
    public function attendance_permission_shows_attendance_nav_only(): void
    {
        $staff = \App\Models\User::factory()->create([
            'role' => 'supervisor',
            'email' => 'nav-att@example.com',
            'password' => 'password',
        ]);

        \App\Models\Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-NAV2',
            'name' => 'Att Staff',
            'email' => 'nav-att@example.com',
            'status' => 'active',
            'nursery_permissions' => ['login.app', 'attendance.children'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.attendance.index'), false)
            ->assertSee(route('nursery.classrooms.index'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('nursery.subscriptions.index'), false);
    }
}
