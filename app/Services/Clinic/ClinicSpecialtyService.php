<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\ClinicSpecialty;
use App\Models\Employee;

final class ClinicSpecialtyService
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public function activeForPortal(int $tenantUserId): array
    {
        $this->seedDefaultsIfEmpty($tenantUserId);

        return ClinicSpecialty::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ClinicSpecialty $s) => ['id' => (int) $s->id, 'name' => $s->name])
            ->all();
    }

    public function seedDefaultsIfEmpty(int $tenantUserId): void
    {
        $exists = ClinicSpecialty::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->exists();

        if ($exists) {
            return;
        }

        $defaults = [
            ['name' => 'طب عام', 'sort_order' => 10],
            ['name' => 'أسنان', 'sort_order' => 20],
            ['name' => 'أطفال', 'sort_order' => 30],
            ['name' => 'جلدية', 'sort_order' => 40],
            ['name' => 'نساء وولادة', 'sort_order' => 50],
        ];

        foreach ($defaults as $row) {
            ClinicSpecialty::query()->create([
                'user_id' => $tenantUserId,
                'name' => $row['name'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array{id: int, name: string, job_title: string|null}>
     */
    public function doctorsForSpecialty(int $tenantUserId, ?int $specialtyId = null): array
    {
        $query = Employee::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->where('clinic_role', 'doctor');

        if ($specialtyId !== null && $specialtyId > 0) {
            $query->where('clinic_specialty_id', $specialtyId);
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'job_title', 'clinic_specialty_id'])
            ->map(fn (Employee $e) => [
                'id' => (int) $e->id,
                'name' => $e->name,
                'job_title' => $e->job_title,
            ])
            ->all();
    }
}
