<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Attachment;
use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\Guardian;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use App\Services\Nursery\Portal\NurseryPortalCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalCalendarTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    #[Test]
    public function activity_stays_visible_when_edit_adds_other_child_ids_with_classroom(): void
    {
        [$guardian, $child, $room] = $this->seedGuardianChildInRoom();
        $other = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'OTH-1',
            'name' => 'آخر',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $entry = CalendarEntry::query()->create([
            'user_id' => $this->tenant->id,
            'entry_type' => CalendarEntry::TYPE_ACTIVITY,
            'title' => 'رسم جماعي',
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 0),
            'classroom_ids' => [(int) $room->id],
            'child_ids' => [],
            'media_links' => [],
            'is_recurring' => false,
            'created_by' => $this->tenant->id,
        ]);

        $service = app(NurseryPortalCalendarService::class);
        $before = $service->weekGrid((int) $this->tenant->id, (int) $guardian->id, Carbon::now());
        $this->assertTrue($this->weekContainsTitle($before, 'رسم جماعي'));

        $entry->forceFill([
            'child_ids' => [(int) $other->id],
            'classroom_ids' => [(int) $room->id],
        ])->save();

        $after = $service->weekGrid((int) $this->tenant->id, (int) $guardian->id, Carbon::now());
        $this->assertTrue($this->weekContainsTitle($after, 'رسم جماعي'));
    }

    #[Test]
    public function portal_calendar_shows_activity_images(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$guardian, $child, $room] = $this->seedGuardianChildInRoom();

        $entry = CalendarEntry::query()->create([
            'user_id' => $this->tenant->id,
            'entry_type' => CalendarEntry::TYPE_ACTIVITY,
            'title' => 'نشاط بالصور',
            'starts_at' => now()->setTime(9, 0),
            'ends_at' => now()->setTime(10, 0),
            'classroom_ids' => [(int) $room->id],
            'child_ids' => [],
            'media_links' => [],
            'is_recurring' => false,
            'created_by' => $this->tenant->id,
        ]);

        $path = 'nursery/calendar/'.$entry->id.'/play.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        Attachment::query()->create([
            'user_id' => $this->tenant->id,
            'attachable_type' => CalendarEntry::class,
            'attachable_id' => $entry->id,
            'file_path' => $path,
            'file_name' => 'play.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 12,
        ]);

        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, '0504444555');
        app(NurseryPortalAuthService::class)->verifyOtp((int) $this->tenant->id, '0504444555', '123456');

        $this->get(route('nursery.portal.calendar', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('نشاط بالصور')
            ->assertSee('storage/'.$path, false);
    }

    /**
     * @return array{0: Guardian, 1: Child, 2: Classroom}
     */
    private function seedGuardianChildInRoom(): array
    {
        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'أم التقويم',
            'phone' => '0504444555',
        ]);

        $room = Classroom::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'فصل التقويم',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'CAL-1',
            'name' => 'ليان',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        Enrollment::query()->create([
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'classroom_id' => $room->id,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
        ]);

        return [$guardian, $child, $room];
    }

    /**
     * @param  array<string, mixed>  $grid
     */
    private function weekContainsTitle(array $grid, string $title): bool
    {
        foreach ($grid['days'] as $day) {
            foreach ($day['events'] as $event) {
                if (($event['title'] ?? '') === $title) {
                    return true;
                }
            }
        }

        return false;
    }
}
