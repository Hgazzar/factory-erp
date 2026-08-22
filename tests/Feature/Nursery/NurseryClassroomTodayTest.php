<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\Guardian;
use App\Models\User;
use App\Support\NurseryChildDailyActivityCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryClassroomTodayTest extends NurseryTestCase
{
    #[Test]
    public function classroom_today_shows_only_enrolled_active_children(): void
    {
        [$roomA, $roomB] = $this->makeTwoClassrooms();
        $inClass = $this->makeEnrolledChild($roomA, 'طفل الفصل', '0501111001');
        $otherClass = $this->makeEnrolledChild($roomB, 'طفل فصل آخر', '0501111002');
        $archived = $this->makeEnrolledChild($roomA, 'طفل مؤرشف', '0501111003');
        $archived->forceFill(['status' => Child::STATUS_INACTIVE])->save();

        $this->get(route('nursery.classrooms.today', $roomA))
            ->assertOk()
            ->assertSee('يوم الفصل')
            ->assertSee('طفل الفصل')
            ->assertDontSee('طفل فصل آخر')
            ->assertDontSee('طفل مؤرشف')
            ->assertSee('حضور')
            ->assertSee('وجبة');

        $this->assertDatabaseHas('nursery_children', ['id' => $inClass->id]);
        $this->assertDatabaseHas('nursery_children', ['id' => $otherClass->id]);
    }

    #[Test]
    public function check_in_and_check_out_from_classroom_today(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'آدم اليوم', '0501111004');

        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('انصراف')
            ->assertSee('حاضر');

        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-out'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('تم الانصراف');
    }

    #[Test]
    public function daily_activity_from_classroom_today_returns_to_today(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'جنى النشاط', '0501111005');

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
            'return_to' => 'classroom_today',
            'classroom_id' => $room->id,
        ])->assertRedirect(route('nursery.classrooms.today', $room));

        $this->assertDatabaseHas('nursery_child_daily_activities', [
            'child_id' => $child->id,
            'type' => 'mood',
        ]);
    }

    #[Test]
    public function tenant_isolation_blocks_foreign_classroom_today(): void
    {
        $otherTenant = User::factory()->create(['role' => 'admin']);
        $foreign = Classroom::query()->create([
            'user_id' => (int) $otherTenant->id,
            'name' => 'فصل خارجي',
            'capacity' => 10,
            'age_groups' => ['3_4y'],
            'is_active' => true,
        ]);

        $this->get(route('nursery.classrooms.today', $foreign))->assertForbidden();
    }

    #[Test]
    public function archived_classroom_cannot_open_today(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $room->forceFill(['is_active' => false])->save();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertRedirect(route('nursery.classrooms.index'));
    }

    #[Test]
    public function view_only_staff_sees_roster_but_cannot_mutate(): void
    {
        $staff = $this->makeLinkedStaff('today-view@example.com', ['login.app']);
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'طفل عرض', '0501111006');

        $this->actingAs($staff);

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('طفل عرض')
            ->assertDontSee(route('nursery.attendance.check-in'), false)
            ->assertDontSee('وجبة');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertForbidden();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
            'return_to' => 'classroom_today',
            'classroom_id' => $room->id,
        ])->assertForbidden();
    }

    #[Test]
    public function teacher_role_without_attendance_children_cannot_post_check_in(): void
    {
        $staff = $this->makeLinkedStaff('today-role-only@example.com', ['login.app']);
        Employee::query()->where('linked_user_id', $staff->id)->update(['nursery_role' => 'teacher']);

        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'طفل دور فقط', '0501111008');

        $this->actingAs($staff);

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('طفل دور فقط')
            ->assertDontSee(route('nursery.attendance.check-in'), false);

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertForbidden();
    }

    #[Test]
    public function attendance_staff_can_check_in_from_today(): void
    {
        $staff = $this->makeLinkedStaff('today-att@example.com', ['login.app', 'attendance.children']);
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'طفل حضور', '0501111007');

        $this->actingAs($staff);

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee(route('nursery.attendance.check-in'), false)
            ->assertSee('وجبة');

        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect();
    }

    /**
     * @return array{0: Classroom, 1: Classroom}
     */
    private function makeTwoClassrooms(): array
    {
        $roomA = Classroom::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'النجوم',
            'capacity' => 12,
            'age_groups' => ['3_4y'],
            'is_active' => true,
        ]);
        $roomB = Classroom::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'البراعم',
            'capacity' => 10,
            'age_groups' => ['2_3y'],
            'is_active' => true,
        ]);

        return [$roomA, $roomB];
    }

    private function makeEnrolledChild(Classroom $room, string $name, string $phone): Child
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي '.$name,
            'phone' => $phone,
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => $name,
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        Enrollment::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'classroom_id' => $room->id,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
        ]);

        return $child;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeLinkedStaff(string $email, array $permissions): User
    {
        $user = User::factory()->create([
            'role' => 'supervisor',
            'email' => $email,
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $user->id,
            'code' => 'EMP-'.substr(md5($email), 0, 6),
            'name' => 'Staff '.$email,
            'email' => $email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $user;
    }
}
