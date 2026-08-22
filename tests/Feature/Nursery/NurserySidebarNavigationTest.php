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
    public function login_app_staff_sees_classrooms_in_nursery_nav(): void
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
            ->assertSee(route('nursery.classrooms.index'), false)
            ->assertSee(route('nursery.attendance.index'), false)
            ->assertDontSee(route('nursery.settings.index'), false)
            ->assertDontSee(route('nursery.finance.index'), false);
    }
}
