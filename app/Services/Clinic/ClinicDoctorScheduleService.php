<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\BlockedSlot;
use App\Models\Clinic\DoctorSchedule;
use App\Models\Employee;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class ClinicDoctorScheduleService
{
    /**
     * @return Collection<int, DoctorSchedule>
     */
    public function forDoctor(int $tenantUserId, int $doctorEmployeeId): Collection
    {
        return DoctorSchedule::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertSchedule(int $tenantUserId, array $data): DoctorSchedule
    {
        $doctorId = (int) ($data['doctor_employee_id'] ?? 0);
        $dayOfWeek = (int) ($data['day_of_week'] ?? -1);
        $startTime = $this->normalizeTime((string) ($data['start_time'] ?? ''));
        $endTime = $this->normalizeTime((string) ($data['end_time'] ?? ''));

        if ($doctorId < 1 || $dayOfWeek < 0 || $dayOfWeek > 6 || $startTime === '' || $endTime === '') {
            throw new InvalidArgumentException('بيانات الجدول غير مكتملة.');
        }

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('وقت البداية يجب أن يسبق وقت النهاية.');
        }

        $this->assertDoctorBelongsToTenant($tenantUserId, $doctorId);

        $scheduleId = isset($data['id']) ? (int) $data['id'] : null;

        if ($scheduleId > 0) {
            $schedule = DoctorSchedule::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($scheduleId)
                ->firstOrFail();

            $schedule->fill([
                'doctor_employee_id' => $doctorId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'slot_duration_minutes' => max(5, (int) ($data['slot_duration_minutes'] ?? 30)),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
            $schedule->save();

            return $schedule->fresh();
        }

        return DoctorSchedule::query()->create([
            'user_id' => $tenantUserId,
            'doctor_employee_id' => $doctorId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'slot_duration_minutes' => max(5, (int) ($data['slot_duration_minutes'] ?? 30)),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBlockedSlot(int $tenantUserId, array $data): BlockedSlot
    {
        $doctorId = isset($data['doctor_employee_id']) ? (int) $data['doctor_employee_id'] : null;
        $date = trim((string) ($data['blocked_date'] ?? ''));
        $isFullDay = (bool) ($data['is_full_day'] ?? false);

        if ($date === '') {
            throw new InvalidArgumentException('تاريخ الإغلاق مطلوب.');
        }

        if ($doctorId !== null && $doctorId > 0) {
            $this->assertDoctorBelongsToTenant($tenantUserId, $doctorId);
        } else {
            $doctorId = null;
        }

        return BlockedSlot::query()->create([
            'user_id' => $tenantUserId,
            'doctor_employee_id' => $doctorId,
            'blocked_date' => $date,
            'start_time' => $isFullDay ? null : $this->nullableTime($data['start_time'] ?? null),
            'end_time' => $isFullDay ? null : $this->nullableTime($data['end_time'] ?? null),
            'is_full_day' => $isFullDay,
            'reason' => isset($data['reason']) ? trim((string) $data['reason']) : null,
        ]);
    }

    public function deleteSchedule(int $tenantUserId, int $scheduleId): void
    {
        DoctorSchedule::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($scheduleId)
            ->delete();
    }

    public function deleteBlockedSlot(int $tenantUserId, int $blockedSlotId): void
    {
        BlockedSlot::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($blockedSlotId)
            ->delete();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function doctorOptions(int $tenantUserId): array
    {
        return Employee::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->where('clinic_role', 'doctor')
            ->orderBy('name')
            ->get(['id', 'name', 'job_title'])
            ->map(fn (Employee $e) => [
                'value' => (string) $e->id,
                'label' => trim($e->name.($e->job_title ? ' — '.$e->job_title : '')),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }

    private function assertDoctorBelongsToTenant(int $tenantUserId, int $doctorId): void
    {
        $exists = Employee::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($doctorId)
            ->where('clinic_role', 'doctor')
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('الطبيب غير موجود أو غير مفعّل كطبيب.');
        }
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if ($time === '') {
            return '';
        }

        if (strlen($time) === 5) {
            return $time.':00';
        }

        return $time;
    }

    private function nullableTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $time = $this->normalizeTime((string) $value);

        return $time === '' ? null : $time;
    }
}
