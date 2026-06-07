<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\Unit;
use App\Models\Nursery\UnitLesson;
use App\Services\Nursery\NurseryCalendarService;
use App\Support\NurseryAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurseryCalendarWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request, NurseryCalendarService $calendar): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $classroomId = $request->query('classroom_id');
        $classroomFilter = $classroomId === null || $classroomId === '' ? null : (int) $classroomId;
        $type = trim((string) $request->query('type', ''));
        $view = in_array($request->query('view'), ['dayGridMonth', 'timeGridWeek', 'timeGridDay'], true)
            ? $request->query('view')
            : 'timeGridWeek';

        $from = $request->query('from')
            ? Carbon::parse((string) $request->query('from'))
            : now()->startOfWeek(Carbon::SUNDAY);

        $classroomOptions = $this->classroomOptions($tenantUserId);
        $typeOptions = $this->typeOptions();
        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_CALENDAR);

        $entries = $calendar->entriesForRange(
            $tenantUserId,
            $from->copy()->startOfWeek(Carbon::SUNDAY),
            $from->copy()->endOfWeek(Carbon::SATURDAY)->addDay(),
            $type !== '' ? $type : null,
            $classroomFilter,
        );

        return view('nursery.calendar.index', [
            'classroomOptions' => $classroomOptions,
            'typeOptions' => $typeOptions,
            'classroomFilter' => $classroomFilter,
            'typeFilter' => $type,
            'initialView' => $view,
            'initialDate' => $from->toDateString(),
            'calendarEvents' => $calendar->toFullCalendarEvents($entries),
            'canManage' => $canManage,
        ]);
    }

    public function events(Request $request, NurseryCalendarService $calendar): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $start = Carbon::parse((string) $request->query('start'));
        $end = Carbon::parse((string) $request->query('end'));
        $type = trim((string) $request->query('type', ''));
        $classroomId = $request->query('classroom_id');
        $classroomFilter = $classroomId === null || $classroomId === '' ? null : (int) $classroomId;

        $entries = $calendar->entriesForRange(
            $tenantUserId,
            $start,
            $end,
            $type !== '' ? $type : null,
            $classroomFilter,
        );

        return response()->json($calendar->toFullCalendarEvents($entries));
    }

    public function create(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $entryType = $request->query('type', CalendarEntry::TYPE_LESSON);
        if (! array_key_exists($entryType, CalendarEntry::typeLabels())) {
            $entryType = CalendarEntry::TYPE_LESSON;
        }

        return view('nursery.calendar.create', $this->formViewData($tenantUserId, $entryType));
    }

    public function store(Request $request, NurseryCalendarService $calendar): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $this->validatePayload($request);

        try {
            $calendar->create($tenantUserId, $data, auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.calendar.index')
            ->with('success', 'تمت الإضافة إلى التقويم.');
    }

    public function edit(CalendarEntry $entry): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $entry->user_id === $tenantUserId, 404);

        return view('nursery.calendar.edit', array_merge(
            $this->formViewData($tenantUserId, $entry->entry_type, $entry),
            ['entry' => $entry]
        ));
    }

    public function update(Request $request, CalendarEntry $entry, NurseryCalendarService $calendar): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $entry->user_id === $tenantUserId, 404);

        $data = $this->validatePayload($request);

        try {
            $calendar->update($entry, $tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.calendar.index')
            ->with('success', 'تم تحديث إدخال التقويم.');
    }

    public function destroy(CalendarEntry $entry): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $entry->user_id === $tenantUserId, 404);

        $entry->delete();

        return redirect()
            ->route('nursery.calendar.index')
            ->with('success', 'تم حذف الإدخال من التقويم.');
    }

    public function lessonOptions(Request $request): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $unitId = (int) $request->query('unit_id');

        if ($unitId < 1) {
            return response()->json([]);
        }

        $lessons = UnitLesson::query()
            ->where('user_id', $tenantUserId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($lessons->map(fn (UnitLesson $l) => [
            'value' => (string) $l->id,
            'label' => $l->name,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(int $tenantUserId, string $entryType, ?CalendarEntry $entry = null): array
    {
        $unitOptions = Unit::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Unit $u) => ['value' => (string) $u->id, 'label' => $u->name])
            ->all();

        $lessonOptions = [];
        if ($entry?->unit_id) {
            $lessonOptions = UnitLesson::query()
                ->where('user_id', $tenantUserId)
                ->where('unit_id', $entry->unit_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (UnitLesson $l) => ['value' => (string) $l->id, 'label' => $l->name])
                ->all();
        }

        $classrooms = Classroom::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $children = Child::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'entryType' => $entryType,
            'typeLabels' => CalendarEntry::typeLabels(),
            'unitOptions' => $unitOptions,
            'lessonOptions' => $lessonOptions,
            'classrooms' => $classrooms,
            'children' => $children,
            'selectedClassroomIds' => old('classroom_ids', $entry?->classroom_ids ?? []),
            'selectedChildIds' => old('child_ids', $entry?->child_ids ?? []),
            'mediaLinks' => old('media_links', $entry?->media_links ?? []),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function classroomOptions(int $tenantUserId): array
    {
        $options = [
            ['value' => '', 'label' => 'جميع الفصول'],
            ['value' => '-1', 'label' => 'غير معين لفصل'],
        ];

        foreach (
            Classroom::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']) as $room
        ) {
            $options[] = ['value' => (string) $room->id, 'label' => $room->name];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        $options = [['value' => '', 'label' => 'الجميع']];
        foreach (CalendarEntry::typeLabels() as $key => $label) {
            $options[] = ['value' => $key, 'label' => $label];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $types = array_keys(CalendarEntry::typeLabels());

        return $request->validate([
            'entry_type' => ['required', 'string', Rule::in($types)],
            'title' => ['nullable', 'string', 'max:200'],
            'unit_id' => ['nullable', 'integer'],
            'unit_lesson_id' => ['nullable', 'integer'],
            'event_date' => ['required', 'date'],
            'starts_at_time' => ['required', 'date_format:H:i'],
            'ends_at_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer'],
            'media_links' => ['nullable', 'array'],
            'media_links.*.url' => ['nullable', 'url', 'max:500'],
            'media_links.*.label' => ['nullable', 'string', 'max:120'],
            'is_recurring' => ['nullable'],
        ]);
    }
}
