<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildDailyActivity;
use App\Models\Nursery\ChildMedication;
use App\Support\NurseryChildDailyActivityCatalog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class NurseryChildDailyActivityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, Child $child, array $data, int $recordedBy): ChildDailyActivity
    {
        $this->assertWritableChild($tenantUserId, $child);

        $type = (string) ($data['type'] ?? '');
        if (! NurseryChildDailyActivityCatalog::isValidType($type)) {
            throw new InvalidArgumentException('نوع النشاط غير مدعوم.');
        }

        $date = $this->resolveDate($data['activity_date'] ?? null);
        $payload = $this->validatedPayload($type, array_merge($data, ['_child_id' => (int) $child->id]));
        $note = $this->normalizedNote($data['note'] ?? null);

        if ($type === NurseryChildDailyActivityCatalog::TYPE_NOTE && $note === '') {
            throw new InvalidArgumentException('الملاحظة مطلوبة.');
        }

        $visible = array_key_exists('is_parent_visible', $data)
            ? filter_var($data['is_parent_visible'], FILTER_VALIDATE_BOOL)
            : NurseryChildDailyActivityCatalog::defaultParentVisible($type);

        return ChildDailyActivity::query()->create([
            'user_id' => $tenantUserId,
            'child_id' => (int) $child->id,
            'activity_date' => $date,
            'type' => $type,
            'payload' => $payload,
            'note' => $note !== '' ? $note : null,
            'is_parent_visible' => $visible,
            'recorded_by' => $recordedBy,
            'recorded_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $tenantUserId, ChildDailyActivity $activity, array $data): ChildDailyActivity
    {
        abort_unless((int) $activity->user_id === $tenantUserId, 404);

        $type = (string) $activity->type;
        $merged = array_merge(is_array($activity->payload) ? $activity->payload : [], $data, [
            '_child_id' => (int) $activity->child_id,
        ]);
        $payload = $this->validatedPayload($type, $merged);

        $note = array_key_exists('note', $data)
            ? $this->normalizedNote($data['note'])
            : (string) ($activity->note ?? '');

        if ($type === NurseryChildDailyActivityCatalog::TYPE_NOTE && $note === '') {
            throw new InvalidArgumentException('الملاحظة مطلوبة.');
        }

        $visible = array_key_exists('is_parent_visible', $data)
            ? filter_var($data['is_parent_visible'], FILTER_VALIDATE_BOOL)
            : (bool) $activity->is_parent_visible;

        $activity->forceFill([
            'payload' => $payload,
            'note' => $note !== '' ? $note : null,
            'is_parent_visible' => $visible,
        ])->save();

        return $activity->fresh();
    }

    public function delete(int $tenantUserId, ChildDailyActivity $activity): void
    {
        abort_unless((int) $activity->user_id === $tenantUserId, 404);
        $activity->delete();
    }

    /**
     * @return Collection<int, ChildDailyActivity>
     */
    public function forChildOnDate(int $tenantUserId, int $childId, string $date, bool $parentVisibleOnly = false): Collection
    {
        return ChildDailyActivity::query()
            ->withoutGlobalScopes()
            ->with('recorder:id,name')
            ->where('user_id', $tenantUserId)
            ->where('child_id', $childId)
            ->whereDate('activity_date', $date)
            ->when($parentVisibleOnly, fn ($q) => $q->where('is_parent_visible', true))
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, ChildDailyActivity>  $activities
     * @return list<array{type: string, label: string, lines: list<string>}>
     */
    public function summary(Collection $activities): array
    {
        $grouped = [];
        foreach (NurseryChildDailyActivityCatalog::keys() as $type) {
            $items = $activities->where('type', $type)->values();
            if ($items->isEmpty()) {
                continue;
            }

            $lines = [];
            foreach ($items as $item) {
                $lines[] = $item->summaryLine();
            }

            $grouped[] = [
                'type' => $type,
                'label' => NurseryChildDailyActivityCatalog::label($type),
                'lines' => $lines,
            ];
        }

        return $grouped;
    }

    private function assertWritableChild(int $tenantUserId, Child $child): void
    {
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        if (! $child->isActive()) {
            throw new InvalidArgumentException('لا يمكن تسجيل يوم لطفل مؤرشف.');
        }
    }

    private function resolveDate(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        $date = $raw !== '' ? Carbon::parse($raw)->startOfDay() : now()->startOfDay();

        if ($date->gt(now()->startOfDay())) {
            throw new InvalidArgumentException('لا يمكن تسجيل نشاط في تاريخ مستقبلي.');
        }

        return $date->toDateString();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatedPayload(string $type, array $data): array
    {
        $source = is_array($data['payload'] ?? null) ? $data['payload'] : $data;

        return match ($type) {
            NurseryChildDailyActivityCatalog::TYPE_MEAL => [
                'meal' => $this->requiredOption($type, 'meal', $source['meal'] ?? null),
                'amount' => $this->requiredOption($type, 'amount', $source['amount'] ?? null),
            ],
            NurseryChildDailyActivityCatalog::TYPE_NAP => $this->napPayload($source),
            NurseryChildDailyActivityCatalog::TYPE_DIAPER => [
                'change' => $this->requiredOption($type, 'change', $source['change'] ?? null),
            ],
            NurseryChildDailyActivityCatalog::TYPE_TOILET => [
                'result' => $this->requiredOption($type, 'result', $source['result'] ?? null),
            ],
            NurseryChildDailyActivityCatalog::TYPE_MOOD => [
                'mood' => $this->requiredOption($type, 'mood', $source['mood'] ?? null),
            ],
            NurseryChildDailyActivityCatalog::TYPE_ACTIVITY => [
                'title' => $this->requiredTitle($source['title'] ?? null),
            ],
            NurseryChildDailyActivityCatalog::TYPE_MEDICATION => $this->medicationPayload($source),
            NurseryChildDailyActivityCatalog::TYPE_NOTE => [],
            default => throw new InvalidArgumentException('نوع النشاط غير مدعوم.'),
        };
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{medication_id: ?int, medication_name: string, dosage: ?string, status: string, given_at: string}
     */
    private function medicationPayload(array $source): array
    {
        $status = $this->requiredOption(
            NurseryChildDailyActivityCatalog::TYPE_MEDICATION,
            'status',
            $source['status'] ?? null,
        );
        $givenAt = $this->requiredTime($source['given_at'] ?? null, 'وقت الجرعة مطلوب.');

        $medicationId = isset($source['medication_id']) && $source['medication_id'] !== '' && $source['medication_id'] !== null
            ? (int) $source['medication_id']
            : null;

        $name = trim((string) ($source['medication_name'] ?? ''));
        $dosage = trim((string) ($source['dosage'] ?? ''));

        if ($medicationId !== null && $medicationId > 0) {
            $med = ChildMedication::query()
                ->whereKey($medicationId)
                ->first();

            if ($med === null) {
                throw new InvalidArgumentException('الدواء المحدد غير موجود.');
            }

            if (isset($source['_child_id']) && (int) $med->child_id !== (int) $source['_child_id']) {
                throw new InvalidArgumentException('الدواء لا يخص هذا الطفل.');
            }

            $name = trim((string) $med->name);
            if ($dosage === '') {
                $dosage = trim((string) ($med->dosage ?? ''));
            }
        }

        if ($name === '') {
            throw new InvalidArgumentException('اسم الدواء مطلوب.');
        }
        if (mb_strlen($name) > 120) {
            throw new InvalidArgumentException('اسم الدواء طويل جداً.');
        }
        if (mb_strlen($dosage) > 64) {
            throw new InvalidArgumentException('نص الجرعة طويل جداً.');
        }

        return [
            'medication_id' => $medicationId !== null && $medicationId > 0 ? $medicationId : null,
            'medication_name' => $name,
            'dosage' => $dosage !== '' ? $dosage : null,
            'status' => $status,
            'given_at' => $givenAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{started_at: string, ended_at: ?string}
     */
    private function napPayload(array $source): array
    {
        $start = $this->requiredTime($source['started_at'] ?? null, 'وقت بداية القيلولة مطلوب.');
        $endRaw = trim((string) ($source['ended_at'] ?? ''));
        $end = $endRaw !== '' ? $this->requiredTime($endRaw, 'وقت نهاية القيلولة غير صالح.') : null;

        if ($end !== null && $end < $start) {
            throw new InvalidArgumentException('وقت نهاية القيلولة يجب أن يكون بعد البداية.');
        }

        return ['started_at' => $start, 'ended_at' => $end];
    }

    private function requiredOption(string $type, string $field, mixed $value): string
    {
        $key = trim((string) $value);
        $allowed = array_keys(NurseryChildDailyActivityCatalog::options($type, $field));
        if ($key === '' || ! in_array($key, $allowed, true)) {
            throw new InvalidArgumentException('قيمة غير صالحة لحقل '.$field.'.');
        }

        return $key;
    }

    private function requiredTitle(mixed $value): string
    {
        $title = trim((string) $value);
        if ($title === '') {
            throw new InvalidArgumentException('عنوان النشاط مطلوب.');
        }
        if (mb_strlen($title) > 80) {
            throw new InvalidArgumentException('عنوان النشاط طويل جداً.');
        }

        return $title;
    }

    private function requiredTime(mixed $value, string $message): string
    {
        $time = trim((string) $value);
        if ($time === '' || preg_match('/^\d{2}:\d{2}$/', $time) !== 1) {
            throw new InvalidArgumentException($message);
        }

        [$h, $m] = array_map('intval', explode(':', $time));
        if ($h > 23 || $m > 59) {
            throw new InvalidArgumentException($message);
        }

        return sprintf('%02d:%02d', $h, $m);
    }

    private function normalizedNote(mixed $value): string
    {
        $note = trim((string) $value);
        if (mb_strlen($note) > 500) {
            throw new InvalidArgumentException('الملاحظة طويلة جداً (500 حرف).');
        }

        return $note;
    }
}
