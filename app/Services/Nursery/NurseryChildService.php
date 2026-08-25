<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\Guardian;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NurseryChildService
{
    public function __construct(
        private readonly NurseryChildMedicationService $medications,
    ) {}
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(int $tenantUserId, array $data): Child
    {
        $name = trim((string) ($data['name'] ?? ''));
        $guardianName = trim((string) ($data['guardian_name'] ?? ''));
        $guardianPhone = trim((string) ($data['guardian_phone'] ?? ''));

        if ($name === '' || $guardianName === '' || $guardianPhone === '') {
            throw new InvalidArgumentException('اسم الطفل وولي الأمر ورقم الجوال مطلوبة.');
        }

        return DB::transaction(function () use ($tenantUserId, $data, $name, $guardianName, $guardianPhone): Child {
            $guardian = $this->resolveGuardian($tenantUserId, $guardianName, $guardianPhone, $data);

            $existing = $this->findSiblingByNormalizedName($tenantUserId, (int) $guardian->id, $name);
            if ($existing !== null) {
                throw new DuplicateChildNameException(
                    'لا يمكن تسجيل أخوين لنفس ولي الأمر بنفس الاسم. ميّز الاسم (مثل فهد الأكبر / فهد الأصغر).',
                    $existing,
                );
            }

            $child = Child::query()->create([
                'user_id' => $tenantUserId,
                'code' => $this->nextChildCode($tenantUserId),
                'name' => $name,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'guardian_id' => $guardian->id,
                'guardian_relationship' => $data['guardian_relationship'] ?? null,
                'allergies' => trim((string) ($data['allergies'] ?? '')) ?: null,
                'diseases' => trim((string) ($data['diseases'] ?? '')) ?: null,
                'health_notes' => trim((string) ($data['health_notes'] ?? '')) ?: null,
                'status' => Child::STATUS_ACTIVE,
            ]);

            $classroomId = (int) ($data['classroom_id'] ?? 0);
            if ($classroomId > 0) {
                Enrollment::query()->create([
                    'user_id' => $tenantUserId,
                    'child_id' => $child->id,
                    'classroom_id' => $classroomId,
                    'starts_on' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }

            $this->medications->sync($child, $tenantUserId, $data['medications'] ?? null);

            return $child->fresh(['guardian', 'activeEnrollment.classroom', 'medications']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveGuardian(int $tenantUserId, string $name, string $phone, array $data): Guardian
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? $phone;

        $existing = Guardian::query()
            ->where('user_id', $tenantUserId)
            ->where(function ($q) use ($phone, $normalized): void {
                $q->where('phone', $phone);
                if ($normalized !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?", [$normalized]);
                }
            })
            ->first();

        $payload = [
            'name' => $name,
            'phone' => $phone,
            'email' => $data['guardian_email'] ?? null,
            'national_id' => $data['guardian_national_id'] ?? null,
            'address' => $data['guardian_address'] ?? null,
            'region' => $data['guardian_region'] ?? null,
            'city' => $data['guardian_city'] ?? null,
        ];

        if ($existing !== null) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return Guardian::query()->create([
            'user_id' => $tenantUserId,
            ...$payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Child $child, int $tenantUserId, array $data): Child
    {
        $name = trim((string) ($data['name'] ?? ''));
        $guardianName = trim((string) ($data['guardian_name'] ?? ''));
        $guardianPhone = trim((string) ($data['guardian_phone'] ?? ''));

        if ($name === '' || $guardianName === '' || $guardianPhone === '') {
            throw new InvalidArgumentException('اسم الطفل وولي الأمر ورقم الجوال مطلوبة.');
        }

        return DB::transaction(function () use ($child, $tenantUserId, $data, $name, $guardianName, $guardianPhone): Child {
            $guardian = $this->resolveGuardian($tenantUserId, $guardianName, $guardianPhone, $data);
            $existing = $this->findSiblingByNormalizedName($tenantUserId, (int) $guardian->id, $name, (int) $child->id);
            if ($existing !== null) {
                throw new DuplicateChildNameException(
                    'لا يمكن تسجيل أخوين لنفس ولي الأمر بنفس الاسم. ميّز الاسم (مثل فهد الأكبر / فهد الأصغر).',
                    $existing,
                );
            }

            $child->fill([
                'name' => $name,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'guardian_id' => $guardian->id,
                'guardian_relationship' => $data['guardian_relationship'] ?? null,
                'allergies' => trim((string) ($data['allergies'] ?? '')) ?: null,
                'diseases' => trim((string) ($data['diseases'] ?? '')) ?: null,
                'health_notes' => trim((string) ($data['health_notes'] ?? '')) ?: null,
                'status' => $data['status'] ?? $child->status,
            ]);
            $child->save();

            $classroomId = (int) ($data['classroom_id'] ?? 0);
            $active = $child->activeEnrollment;

            if ($classroomId > 0) {
                if ($active !== null && (int) $active->classroom_id !== $classroomId) {
                    $active->is_active = false;
                    $active->ends_on = now()->toDateString();
                    $active->save();
                    $active = null;
                }

                if ($active === null) {
                    Enrollment::query()->create([
                        'user_id' => $tenantUserId,
                        'child_id' => $child->id,
                        'classroom_id' => $classroomId,
                        'starts_on' => now()->toDateString(),
                        'is_active' => true,
                    ]);
                }
            } elseif ($active !== null) {
                $active->is_active = false;
                $active->ends_on = now()->toDateString();
                $active->save();
            }

            $this->medications->sync($child, $tenantUserId, $data['medications'] ?? null);

            return $child->fresh(['guardian', 'activeEnrollment.classroom', 'attachments', 'medications']);
        });
    }

    public function findSiblingByNormalizedName(
        int $tenantUserId,
        int $guardianId,
        string $name,
        ?int $exceptChildId = null,
    ): ?Child {
        $normalized = $this->normalizePersonName($name);
        if ($normalized === '') {
            return null;
        }

        $siblings = Child::query()
            ->where('user_id', $tenantUserId)
            ->where('guardian_id', $guardianId)
            ->when($exceptChildId !== null, fn ($q) => $q->where('id', '!=', $exceptChildId))
            ->get(['id', 'user_id', 'name', 'guardian_id', 'code', 'status', 'created_at']);

        foreach ($siblings as $sibling) {
            if ($this->normalizePersonName((string) $sibling->name) === $normalized) {
                return $sibling;
            }
        }

        return null;
    }

    private function normalizePersonName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name));
    }

    private function nextChildCode(int $tenantUserId): string
    {
        $codes = Child::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/(\d+)\s*$/', (string) $code, $m) === 1) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'CH-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
