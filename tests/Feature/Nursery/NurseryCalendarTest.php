<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Unit;
use App\Models\Nursery\UnitLesson;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryCalendarTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_schedule_lesson_and_view_calendar_events(): void
    {
        $unit = Unit::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'الحيوانات',
            'age_groups' => ['2_3y'],
            'goals' => ['التعرف على الحيوانات'],
            'is_active' => true,
        ]);

        $lesson = UnitLesson::query()->create([
            'user_id' => $this->tenant->id,
            'unit_id' => $unit->id,
            'name' => 'أصوات الحيوانات',
            'is_active' => true,
        ]);

        $this->get(route('nursery.calendar.index'))
            ->assertOk()
            ->assertSee('التقويم');

        $this->get(route('nursery.calendar.create', ['type' => 'lesson']))
            ->assertOk()
            ->assertSee('بيانات الدرس');

        $this->post(route('nursery.calendar.store'), [
            'entry_type' => 'lesson',
            'unit_id' => $unit->id,
            'unit_lesson_id' => $lesson->id,
            'event_date' => now()->format('Y-m-d'),
            'starts_at_time' => '09:00',
            'ends_at_time' => '10:00',
            'notes' => 'ملاحظة تجريبية',
        ])->assertRedirect(route('nursery.calendar.index'));

        $entry = CalendarEntry::query()->where('unit_lesson_id', $lesson->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(CalendarEntry::TYPE_LESSON, $entry->entry_type);
        $this->assertSame('أصوات الحيوانات', $entry->title);

        $this->get(route('nursery.calendar.events', [
            'start' => now()->startOfDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonFragment(['title' => 'درس — أصوات الحيوانات']);
    }

    #[Test]
    public function unit_form_syncs_lessons_for_calendar_dropdown(): void
    {
        $this->post(route('nursery.units.store'), [
            'name' => 'الألوان',
            'age_groups' => ['3_4y'],
            'goals' => ['تمييز الألوان'],
            'lessons' => ['الأحمر', 'الأزرق'],
        ])->assertRedirect(route('nursery.units.index'));

        $unit = Unit::query()->where('name', 'الألوان')->first();
        $this->assertNotNull($unit);
        $this->assertCount(2, $unit->activeLessons);

        $this->get(route('nursery.calendar.lessons', ['unit_id' => $unit->id]))
            ->assertOk()
            ->assertJsonFragment(['label' => 'الأحمر']);
    }
}
