<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\Child;
use App\Models\Nursery\ChildDailyActivity;
use App\Models\Nursery\Guardian;
use App\Models\User;
use App\Support\NurseryChildDailyActivityCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryChildDailyActivityTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_create_multiple_activities_same_day(): void
    {
        $child = $this->makeChild();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MEAL,
            'meal' => 'breakfast',
            'amount' => 'eaten',
        ])->assertRedirect();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MEAL,
            'meal' => 'lunch',
            'amount' => 'partial',
            'note' => 'أكل نصف الوجبة',
        ])->assertRedirect();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
        ])->assertRedirect();

        $this->assertSame(3, ChildDailyActivity::query()->where('child_id', $child->id)->count());

        $this->get(route('nursery.children.show', $child))
            ->assertOk()
            ->assertSee('يوم الطفل')
            ->assertSee('فطور')
            ->assertSee('غداء')
            ->assertSee('سعيد');
    }

    #[Test]
    public function admin_can_update_note_visibility_and_delete(): void
    {
        $child = $this->makeChild();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_NOTE,
            'note' => 'ملاحظة داخلية',
        ])->assertRedirect();

        $activity = ChildDailyActivity::query()->where('child_id', $child->id)->firstOrFail();
        $this->assertFalse((bool) $activity->is_parent_visible);

        $this->patch(route('nursery.children.daily-activities.update', [$child, $activity]), [
            'note' => 'ملاحظة داخلية',
            'is_parent_visible' => '1',
        ])->assertRedirect();

        $this->assertTrue((bool) $activity->fresh()->is_parent_visible);

        $this->delete(route('nursery.children.daily-activities.destroy', [$child, $activity]))
            ->assertRedirect();

        $this->assertDatabaseMissing('nursery_child_daily_activities', [
            'id' => $activity->id,
        ]);
    }

    #[Test]
    public function rejects_invalid_type_future_date_and_archived_child(): void
    {
        $child = $this->makeChild();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => 'storytime',
            'title' => 'قصة',
        ])->assertRedirect()->assertSessionHas('error');

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->addDay()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, ChildDailyActivity::query()->where('child_id', $child->id)->count());

        $child->forceFill(['status' => Child::STATUS_INACTIVE])->save();

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('nursery_child_daily_activities', [
            'child_id' => $child->id,
        ]);
    }

    #[Test]
    public function tenant_isolation_blocks_other_tenants_child(): void
    {
        $otherTenant = User::factory()->create(['role' => 'admin']);
        $otherGuardian = Guardian::query()->create([
            'user_id' => (int) $otherTenant->id,
            'name' => 'ولي آخر',
            'phone' => '0500000999',
        ]);
        $foreignChild = Child::query()->create([
            'user_id' => (int) $otherTenant->id,
            'name' => 'طفل منشأة أخرى',
            'guardian_id' => $otherGuardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->post(route('nursery.children.daily-activities.store', $foreignChild), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
        ])->assertForbidden();

        $this->assertDatabaseMissing('nursery_child_daily_activities', [
            'child_id' => $foreignChild->id,
        ]);
    }

    #[Test]
    public function view_only_staff_can_read_but_cannot_write(): void
    {
        $staff = $this->makeLinkedStaff('daily-view@example.com', ['login.app']);
        $child = $this->makeChild('طفل قراءة');

        ChildDailyActivity::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'payload' => ['mood' => 'calm'],
            'is_parent_visible' => true,
            'recorded_by' => (int) $this->tenant->id,
            'recorded_at' => now(),
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.children.show', $child))
            ->assertOk()
            ->assertSee('يوم الطفل')
            ->assertSee('هادئ')
            ->assertDontSee('حفظ الوجبة');

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MOOD,
            'mood' => 'happy',
        ])->assertForbidden();
    }

    #[Test]
    public function attendance_or_children_manage_staff_can_write(): void
    {
        $teacher = $this->makeLinkedStaff('daily-teacher@example.com', ['login.app', 'attendance.children']);
        $manager = $this->makeLinkedStaff('daily-mgr@example.com', ['login.app', 'children.manage'], 'EMP-DA2');
        $child = $this->makeChild('طفل تسجيل');

        $this->actingAs($teacher);
        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_DIAPER,
            'change' => 'wet',
        ])->assertRedirect();

        $this->actingAs($manager);
        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_ACTIVITY,
            'title' => 'رسم حر',
        ])->assertRedirect();

        $this->assertSame(2, ChildDailyActivity::query()->where('child_id', $child->id)->count());
    }

    #[Test]
    public function admin_can_record_medication_dose_visible_to_parent_summary(): void
    {
        $child = $this->makeChild('طفل دواء');
        $med = \App\Models\Nursery\ChildMedication::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => (int) $child->id,
            'name' => 'شراب حرارة',
            'dosage' => '5 مل',
            'frequency' => \App\Models\Nursery\ChildMedication::FREQ_TWICE_DAILY,
            'sort_order' => 0,
        ]);

        $this->post(route('nursery.children.daily-activities.store', $child), [
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MEDICATION,
            'medication_id' => $med->id,
            'status' => 'given',
            'given_at' => '10:30',
            'note' => 'بعد الفطور',
        ])->assertRedirect();

        $activity = ChildDailyActivity::query()->where('child_id', $child->id)->firstOrFail();
        $this->assertSame('medication', $activity->type);
        $this->assertTrue((bool) $activity->is_parent_visible);
        $this->assertSame('شراب حرارة', $activity->payload['medication_name'] ?? null);
        $this->assertSame('given', $activity->payload['status'] ?? null);
        $this->assertSame('10:30', $activity->payload['given_at'] ?? null);
        $this->assertSame('5 مل', $activity->payload['dosage'] ?? null);

        $this->get(route('nursery.children.show', $child))
            ->assertOk()
            ->assertSee('جرعة دواء')
            ->assertSee('شراب حرارة')
            ->assertSee('أُعطيت')
            ->assertSee('10:30');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeLinkedStaff(string $email, array $permissions, string $code = 'EMP-DA1'): User
    {
        $user = User::factory()->create([
            'role' => 'supervisor',
            'email' => $email,
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $user->id,
            'code' => $code,
            'name' => 'Staff '.$email,
            'email' => $email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $user;
    }

    private function makeChild(string $name = 'طفل اليوم'): Child
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي '.$name,
            'phone' => '05'.substr((string) abs(crc32($name)), 0, 8),
        ]);

        return Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => $name,
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
    }
}
