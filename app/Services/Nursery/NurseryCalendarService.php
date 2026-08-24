<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Unit;
use App\Models\Nursery\UnitLesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class NurseryCalendarService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data, ?int $createdBy = null): CalendarEntry
    {
        return CalendarEntry::query()->create(
            $this->payload($tenantUserId, $data, true, $createdBy)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CalendarEntry $entry, int $tenantUserId, array $data): CalendarEntry
    {
        abort_unless((int) $entry->user_id === $tenantUserId, 404);

        $entry->fill($this->payload($tenantUserId, $data, false));
        $entry->save();

        return $entry->fresh();
    }

    /**
     * @return Collection<int, CalendarEntry>
     */
    public function entriesForRange(
        int $tenantUserId,
        Carbon $from,
        Carbon $to,
        ?string $type = null,
        ?int $classroomId = null,
    ): Collection {
        $query = CalendarEntry::query()
            ->where('user_id', $tenantUserId)
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->with(['unit', 'unitLesson']);

        if ($type !== null && $type !== '' && array_key_exists($type, CalendarEntry::typeLabels())) {
            $query->where('entry_type', $type);
        }

        if ($classroomId !== null) {
            if ($classroomId === -1) {
                $query->where(function ($q): void {
                    $q->whereNull('classroom_ids')
                        ->orWhereJsonLength('classroom_ids', 0);
                });
            } elseif ($classroomId > 0) {
                $query->whereJsonContains('classroom_ids', $classroomId);
            }
        }

        return $query->orderBy('starts_at')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toFullCalendarEvents(Collection $entries): array
    {
        return $entries->map(function (CalendarEntry $entry): array {
            $color = CalendarEntry::typeColors()[$entry->entry_type] ?? '#069494';

            return [
                'id' => (string) $entry->id,
                'title' => $entry->displayTitle(),
                'start' => $entry->starts_at?->toIso8601String(),
                'end' => $entry->ends_at?->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'url' => route('nursery.calendar.edit', $entry),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(int $tenantUserId, array $data, bool $includeTenant, ?int $createdBy = null): array
    {
        $type = (string) ($data['entry_type'] ?? '');
        if (! array_key_exists($type, CalendarEntry::typeLabels())) {
            throw new InvalidArgumentException('نوع الإدخال غير صالح.');
        }

        $startsAt = $this->parseDateTime($data['event_date'] ?? null, $data['starts_at_time'] ?? null);
        $endsAt = $this->parseDateTime($data['event_date'] ?? null, $data['ends_at_time'] ?? null);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('وقت النهاية يجب أن يكون بعد وقت البداية.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        $unitId = isset($data['unit_id']) && $data['unit_id'] !== '' ? (int) $data['unit_id'] : null;
        $lessonId = isset($data['unit_lesson_id']) && $data['unit_lesson_id'] !== '' ? (int) $data['unit_lesson_id'] : null;

        if ($type === CalendarEntry::TYPE_LESSON) {
            if ($unitId === null || $unitId < 1) {
                throw new InvalidArgumentException('اختر الوحدة للدرس.');
            }
            $this->assertUnitBelongsToTenant($unitId, $tenantUserId);

            if ($lessonId !== null && $lessonId > 0) {
                $this->assertLessonBelongsToUnit($lessonId, $unitId, $tenantUserId);
                $lesson = UnitLesson::query()->find($lessonId);
                $title = $lesson?->name ?? $title;
            }

            if ($title === '') {
                throw new InvalidArgumentException('اسم الدرس مطلوب.');
            }
        } elseif ($title === '') {
            throw new InvalidArgumentException('العنوان مطلوب.');
        }

        if ($type !== CalendarEntry::TYPE_LESSON) {
            $unitId = null;
            $lessonId = null;
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if (strlen($notes) > 5000) {
            throw new InvalidArgumentException('الملاحظات طويلة جداً (5000 حرف كحد أقصى).');
        }

        $payload = [
            'entry_type' => $type,
            'title' => $title,
            'unit_id' => $unitId,
            'unit_lesson_id' => $lessonId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $notes !== '' ? $notes : null,
            'classroom_ids' => $this->normalizeIds($data['classroom_ids'] ?? []),
            'child_ids' => $this->normalizeIds($data['child_ids'] ?? []),
            'media_links' => $this->normalizeMediaLinks($data['media_links'] ?? []),
            'is_recurring' => filter_var($data['is_recurring'] ?? false, FILTER_VALIDATE_BOOL),
        ];

        if ($includeTenant) {
            $payload['user_id'] = $tenantUserId;
            $payload['created_by'] = $createdBy;
        }

        return $payload;
    }

    private function parseDateTime(mixed $date, mixed $time): Carbon
    {
        $dateStr = trim((string) $date);
        $timeStr = trim((string) $time);

        if ($dateStr === '' || $timeStr === '') {
            throw new InvalidArgumentException('التاريخ والوقت مطلوبان.');
        }

        return Carbon::parse($dateStr.' '.$timeStr);
    }

    /**
     * @param  mixed  $input
     * @return list<int>
     */
    private function normalizeIds(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($id) => (int) $id, $input),
            fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @param  mixed  $input
     * @return list<array{type: string, url: string, label: string|null}>
     */
    private function normalizeMediaLinks(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $links = [];
        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $url = trim((string) ($row['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $links[] = [
                'type' => 'link',
                'url' => $url,
                'label' => trim((string) ($row['label'] ?? '')) ?: null,
            ];
        }

        return $links;
    }

    private function assertUnitBelongsToTenant(int $unitId, int $tenantUserId): void
    {
        $exists = Unit::query()
            ->where('id', $unitId)
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('الوحدة غير موجودة أو غير نشطة.');
        }
    }

    private function assertLessonBelongsToUnit(int $lessonId, int $unitId, int $tenantUserId): void
    {
        $exists = UnitLesson::query()
            ->where('id', $lessonId)
            ->where('unit_id', $unitId)
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('الدرس غير موجود ضمن هذه الوحدة.');
        }
    }
}
