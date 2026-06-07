<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Unit;
use App\Models\Nursery\UnitLesson;
use App\Support\NurseryClassroomAgeGroups;
use InvalidArgumentException;

final class NurseryUnitService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): Unit
    {
        $unit = Unit::query()->create($this->payload($tenantUserId, $data));
        $this->syncLessons($unit, $tenantUserId, $data['lessons'] ?? []);

        return $unit->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, int $tenantUserId, array $data): Unit
    {
        abort_unless((int) $unit->user_id === $tenantUserId, 404);

        $unit->fill($this->payload($tenantUserId, $data, false));
        $unit->save();
        $this->syncLessons($unit, $tenantUserId, $data['lessons'] ?? []);

        return $unit->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(int $tenantUserId, array $data, bool $includeTenant = true): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم الوحدة مطلوب.');
        }

        $ageGroups = NurseryClassroomAgeGroups::normalizeSelection($data['age_groups'] ?? []);
        if ($ageGroups === []) {
            throw new InvalidArgumentException('اختر فئة عمرية واحدة على الأقل.');
        }

        $goals = $this->normalizeGoals($data['goals'] ?? []);
        if ($goals === []) {
            throw new InvalidArgumentException('أضف هدفاً واحداً على الأقل للوحدة.');
        }

        $payload = [
            'name' => $name,
            'age_groups' => $ageGroups,
            'goals' => $goals,
            'is_active' => ($data['is_active'] ?? 'active') !== 'inactive',
        ];

        if ($includeTenant) {
            $payload['user_id'] = $tenantUserId;
        }

        return $payload;
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    private function normalizeGoals(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $lines = [];
        foreach ($input as $line) {
            $text = trim((string) $line);
            if ($text !== '') {
                $lines[] = $text;
            }
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param  mixed  $input
     */
    private function syncLessons(Unit $unit, int $tenantUserId, mixed $input): void
    {
        if (! is_array($input)) {
            return;
        }

        $names = [];
        foreach ($input as $line) {
            $text = trim((string) $line);
            if ($text !== '') {
                $names[] = $text;
            }
        }
        $names = array_values(array_unique($names));

        $existing = UnitLesson::query()
            ->where('unit_id', $unit->id)
            ->where('user_id', $tenantUserId)
            ->get()
            ->keyBy(fn (UnitLesson $l) => mb_strtolower($l->name));

        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if ($existing->has($key)) {
                $lesson = $existing->get($key);
                if (! $lesson->is_active) {
                    $lesson->update(['is_active' => true]);
                }
                $existing->forget($key);
            } else {
                UnitLesson::query()->create([
                    'user_id' => $tenantUserId,
                    'unit_id' => $unit->id,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }
        }

        foreach ($existing as $orphan) {
            $orphan->update(['is_active' => false]);
        }
    }
}
