<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Classroom;
use App\Support\NurseryClassroomAgeGroups;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryClassroomTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_create_and_edit_classroom_with_age_groups(): void
    {
        $this->get(route('nursery.classrooms.index'))
            ->assertOk()
            ->assertSee('إجمالي الفصول');

        $this->get(route('nursery.classrooms.create'))
            ->assertOk()
            ->assertSee('بيانات الفصل')
            ->assertSee('الفئة العمرية');

        $this->post(route('nursery.classrooms.store'), [
            'name' => 'النجوم',
            'capacity' => 15,
            'age_groups' => ['2_3y', '3_4y'],
        ])->assertRedirect(route('nursery.classrooms.index'));

        $room = Classroom::query()->where('name', 'النجوم')->first();
        $this->assertNotNull($room);
        $this->assertSame([ '2_3y', '3_4y' ], $room->age_groups);
        $this->assertSame(15, (int) $room->capacity);

        $this->get(route('nursery.classrooms.edit', $room))
            ->assertOk()
            ->assertSee('حفظ التعديلات');

        $this->put(route('nursery.classrooms.update', $room), [
            'name' => 'النجوم أ',
            'capacity' => 20,
            'age_groups' => ['4_5y'],
            'is_active' => 'active',
        ])->assertRedirect(route('nursery.classrooms.index'));

        $room->refresh();
        $this->assertSame('النجوم أ', $room->name);
        $this->assertSame(['4_5y'], $room->age_groups);
    }

    #[Test]
    public function classroom_age_group_catalog_rejects_unknown_keys(): void
    {
        $this->assertContains('2_3y', NurseryClassroomAgeGroups::keys());
        $this->assertContains('3_4y', NurseryClassroomAgeGroups::keys());
        $this->assertContains('4_5y', NurseryClassroomAgeGroups::keys());

        $this->post(route('nursery.classrooms.store'), [
            'name' => 'فصل خاطئ',
            'capacity' => 10,
            'age_groups' => ['not_a_real_group'],
        ])->assertSessionHasErrors('age_groups.0');

        $this->assertDatabaseMissing('nursery_classrooms', ['name' => 'فصل خاطئ']);
    }
}
