<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Attachment;
use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Unit;
use App\Models\Nursery\UnitLesson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    public function activity_can_upload_images_instead_of_media_links(): void
    {
        Storage::fake('public');

        $this->get(route('nursery.calendar.create', ['type' => 'activity']))
            ->assertOk()
            ->assertSee('رفع صور')
            ->assertDontSee('name="media_links', false)
            ->assertSee('name="images[]"', false);

        $this->post(route('nursery.calendar.store'), [
            'entry_type' => 'activity',
            'title' => 'رسم حر',
            'event_date' => now()->format('Y-m-d'),
            'starts_at_time' => '10:00',
            'ends_at_time' => '11:00',
            'images' => [
                UploadedFile::fake()->image('paint.jpg', 200, 200),
                UploadedFile::fake()->image('group.png', 180, 180),
            ],
        ])->assertRedirect(route('nursery.calendar.index'));

        $entry = CalendarEntry::query()
            ->where('user_id', $this->tenant->id)
            ->where('title', 'رسم حر')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(CalendarEntry::TYPE_ACTIVITY, $entry->entry_type);
        $this->assertCount(2, $entry->attachments);
        $this->assertTrue(
            Attachment::query()
                ->where('attachable_type', CalendarEntry::class)
                ->where('attachable_id', $entry->id)
                ->exists()
        );

        $edit = $this->get(route('nursery.calendar.edit', $entry))
            ->assertOk()
            ->assertSee('الصور الحالية');

        // Delete must live in its own form — nested _method=DELETE inside the save form
        // used to make "حفظ" submit as DELETE and flash «تم حذف الإدخال».
        $html = $edit->getContent();
        $this->assertSame(1, preg_match(
            '/<form[^>]*id="nurseryCalendarForm"[^>]*>(.*?)<\/form>/s',
            $html,
            $saveForm
        ));
        $this->assertStringContainsString('name="_method" value="PUT"', $saveForm[1]);
        $this->assertStringNotContainsString('name="_method" value="DELETE"', $saveForm[1]);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);

        $this->from(route('nursery.calendar.edit', $entry))
            ->put(route('nursery.calendar.update', $entry), [
                'entry_type' => 'activity',
                'title' => 'رسم حر محدّث',
                'event_date' => now()->format('Y-m-d'),
                'starts_at_time' => '10:00',
                'ends_at_time' => '11:00',
            ])
            ->assertRedirect(route('nursery.calendar.index'))
            ->assertSessionHas('success', 'تم تحديث إدخال التقويم.');

        $this->assertDatabaseHas('nursery_calendar_entries', [
            'id' => $entry->id,
            'title' => 'رسم حر محدّث',
        ]);
    }

    #[Test]
    public function storing_activity_flashes_created_not_deleted(): void
    {
        $this->post(route('nursery.calendar.store'), [
            'entry_type' => 'activity',
            'title' => 'نشاط صباحي',
            'event_date' => now()->format('Y-m-d'),
            'starts_at_time' => '08:00',
            'ends_at_time' => '09:00',
        ])
            ->assertRedirect(route('nursery.calendar.index'))
            ->assertSessionHas('success', 'تمت الإضافة إلى التقويم.')
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('nursery_calendar_entries', [
            'user_id' => $this->tenant->id,
            'title' => 'نشاط صباحي',
        ]);
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
