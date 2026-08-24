<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

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
     * @param  list<int>  $classroomIds
     * @param  list<int>  $childIds
     */
    private function isVisibleToGuardian(CalendarEntry $entry, array $classroomIds, array $childIds): bool
    {
        $entryChildIds = array_map('intval', (array) ($entry->child_ids ?? []));
        if ($entryChildIds !== []) {
            return array_intersect($entryChildIds, $childIds) !== [];
        }

        $entryClassroomIds = array_map('intval', (array) ($entry->classroom_ids ?? []));
        if ($entryClassroomIds === []) {
            return true;
        }

        if ($classroomIds === []) {
            return false;
        }

        return array_intersect($entryClassroomIds, $classroomIds) !== [];
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
                ];
            })
            ->values()
            ->all();
    }
}
