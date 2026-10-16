<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Attachment;
use App\Models\Nursery\CalendarEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * تقويم أسبوعي mobile-first لأولياء الأمور — أحداث فصول أطفالهم.
 */
final class NurseryPortalCalendarService
{
    public function __construct(
        private readonly NurseryPortalAccessService $access,
    ) {}

    /**
     * @return array{
     *     weekStart: Carbon,
     *     weekEnd: Carbon,
     *     days: list<array{date: Carbon, label: string, events: list<array<string, mixed>>}>
     * }
     */
    public function weekGrid(int $tenantUserId, int $guardianId, ?Carbon $anchor = null): array
    {
        $anchor ??= now();
        $weekStart = $anchor->copy()->startOfWeek(Carbon::SATURDAY);
        $weekEnd = $anchor->copy()->endOfWeek(Carbon::FRIDAY);

        $children = $this->access->activeChildrenForGuardian($tenantUserId, $guardianId);
        $classroomIds = $children
            ->map(fn ($c) => (int) ($c->activeEnrollment?->classroom_id ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $childIds = $children->pluck('id')->map(fn ($id) => (int) $id)->all();

        $entries = CalendarEntry::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('starts_at', '<', $weekEnd->copy()->endOfDay())
            ->where('ends_at', '>', $weekStart->copy()->startOfDay())
            ->with('attachments')
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (CalendarEntry $entry) => $this->isVisibleToGuardian($entry, $classroomIds, $childIds));

        $days = [];
        $cursor = $weekStart->copy();
        while ($cursor->lte($weekEnd)) {
            $dayEvents = $this->eventsForDay($entries, $cursor);
            $days[] = [
                'date' => $cursor->copy(),
                'label' => $cursor->translatedFormat('l j M'),
                'is_today' => $cursor->isToday(),
                'events' => $dayEvents,
            ];
            $cursor->addDay();
        }

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => $days,
        ];
    }

    /**
     * Visible when:
     * - no classroom/child filters → everyone
     * - classroom filter only → child's classroom matches
     * - child filter only → child is listed
     * - both filters → classroom OR child matches (so editing "حدد الحضور" لا يخفي النشاط عن فصل الأم)
     *
     * @param  list<int>  $classroomIds
     * @param  list<int>  $childIds
     */
    private function isVisibleToGuardian(CalendarEntry $entry, array $classroomIds, array $childIds): bool
    {
        $entryChildIds = array_values(array_filter(array_map('intval', (array) ($entry->child_ids ?? []))));
        $entryClassroomIds = array_values(array_filter(array_map('intval', (array) ($entry->classroom_ids ?? []))));

        $hasChildFilter = $entryChildIds !== [];
        $hasClassroomFilter = $entryClassroomIds !== [];

        if (! $hasChildFilter && ! $hasClassroomFilter) {
            return true;
        }

        $childMatch = $hasChildFilter && array_intersect($entryChildIds, $childIds) !== [];
        $classroomMatch = $hasClassroomFilter
            && $classroomIds !== []
            && array_intersect($entryClassroomIds, $classroomIds) !== [];

        if ($hasChildFilter && $hasClassroomFilter) {
            return $childMatch || $classroomMatch;
        }

        return $hasChildFilter ? $childMatch : $classroomMatch;
    }

    /**
     * @param  Collection<int, CalendarEntry>  $entries
     * @return list<array<string, mixed>>
     */
    private function eventsForDay(Collection $entries, Carbon $day): array
    {
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        return $entries
            ->filter(fn (CalendarEntry $entry) => $entry->starts_at < $dayEnd && $entry->ends_at > $dayStart)
            ->map(function (CalendarEntry $entry): array {
                $typeLabels = CalendarEntry::typeLabels();
                $colors = CalendarEntry::typeColors();

                return [
                    'title' => $entry->title,
                    'type' => $entry->entry_type,
                    'type_label' => $typeLabels[$entry->entry_type] ?? $entry->entry_type,
                    'color' => $colors[$entry->entry_type] ?? '#069494',
                    'time' => $entry->starts_at?->format('H:i').' — '.$entry->ends_at?->format('H:i'),
                    'notes' => $entry->notes,
                    'images' => $this->imageUrlsForEntry($entry),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function imageUrlsForEntry(CalendarEntry $entry): array
    {
        $attachments = $entry->relationLoaded('attachments')
            ? $entry->attachments
            : $entry->attachments()->orderBy('id')->get();

        $urls = [];
        foreach ($attachments as $att) {
            if (! $att instanceof Attachment || blank($att->file_path)) {
                continue;
            }

            $mime = strtolower((string) ($att->file_type ?? ''));
            $name = strtolower((string) ($att->file_name ?? ''));
            $isImage = str_starts_with($mime, 'image/')
                || (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp)$/', $name);

            if ($isImage) {
                $urls[] = asset('storage/'.ltrim((string) $att->file_path, '/'));
            }
        }

        return $urls;
    }
}
