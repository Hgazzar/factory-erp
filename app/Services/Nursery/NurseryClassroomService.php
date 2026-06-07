<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Classroom;
use App\Models\Nursery\Enrollment;
use App\Support\NurseryClassroomAgeGroups;
use InvalidArgumentException;

final class NurseryClassroomService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): Classroom
    {
        $name = trim((string) ($data['name'] ?? ''));
        $capacity = (int) ($data['capacity'] ?? 0);
        $ageGroups = NurseryClassroomAgeGroups::normalizeSelection($data['age_groups'] ?? []);

        if ($name === '') {
            throw new InvalidArgumentException('اسم الفصل مطلوب.');
        }

        if ($capacity < 1) {
            throw new InvalidArgumentException('سعة الفصل مطلوبة (طفل واحد على الأقل).');
        }

        if ($ageGroups === []) {
            throw new InvalidArgumentException('اختر فئة عمرية واحدة على الأقل.');
        }

        return Classroom::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'capacity' => $capacity,
            'age_groups' => $ageGroups,
            'teacher_employee_id' => ! empty($data['teacher_employee_id']) ? (int) $data['teacher_employee_id'] : null,
            'accent_color' => $data['accent_color'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Classroom $classroom, int $tenantUserId, array $data): Classroom
    {
        abort_unless((int) $classroom->user_id === $tenantUserId, 404);

        $name = trim((string) ($data['name'] ?? ''));
        $capacity = (int) ($data['capacity'] ?? 0);
        $ageGroups = NurseryClassroomAgeGroups::normalizeSelection($data['age_groups'] ?? []);

        if ($name === '') {
            throw new InvalidArgumentException('اسم الفصل مطلوب.');
        }

        if ($capacity < 1) {
            throw new InvalidArgumentException('سعة الفصل مطلوبة (طفل واحد على الأقل).');
        }

        if ($ageGroups === []) {
            throw new InvalidArgumentException('اختر فئة عمرية واحدة على الأقل.');
        }

        $classroom->fill([
            'name' => $name,
            'capacity' => $capacity,
            'age_groups' => $ageGroups,
            'is_active' => ($data['is_active'] ?? $classroom->is_active) !== 'inactive',
        ]);
        $classroom->save();

        return $classroom->fresh();
    }

    public function activeCountForClassroom(int $classroomId): int
    {
        return Enrollment::query()
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->count();
    }
}
