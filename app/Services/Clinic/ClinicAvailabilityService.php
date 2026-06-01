<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\BlockedSlot;
use App\Models\Clinic\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * محرك توفر الأطباء — جداول العمل، الإغلاقات، ومنع الحجز المزدوج.
 */
final class ClinicAvailabilityService
{
    public const DEFAULT_SLOT_MINUTES = 30;

    public const LEGACY_DAY_START = '08:00:00';

    public const LEGACY_DAY_END = '20:00:00';

    /**
     * @throws RuntimeException
     */
    public function assertSlotAvailable(
        int $tenantUserId,
        ?int $doctorEmployeeId,
        string $date,
        string $startTime,
        ?string $endTime = null,
        ?int $excludeAppointmentId = null,
    ): void {
        if (! $this->isSlotAvailable($tenantUserId, $doctorEmployeeId, $date, $startTime, $endTime, $excludeAppointmentId)) {
            throw new RuntimeException('الموعد غير متاح: تعارض مع جدول الطبيب أو حجز آخر.');
        }
    }

    public function isSlotAvailable(
        int $tenantUserId,
        ?int $doctorEmployeeId,
        string $date,
        string $startTime,
        ?string $endTime = null,
        ?int $excludeAppointmentId = null,
    ): bool {
        $slotStart = $this->toCarbon($date, $startTime);
        $slotEnd = $this->slotEnd($date, $startTime, $endTime);

        if ($doctorEmployeeId !== null && $doctorEmployeeId > 0) {
            if ($this->isDoctorDayFullyBlocked($tenantUserId, $doctorEmployeeId, $date)) {
                return false;
            }

            if ($this->isTimeBlocked($tenantUserId, $doctorEmployeeId, $date, $slotStart, $slotEnd)) {
                return false;
            }

            if (! $this->isWithinDoctorSchedule($tenantUserId, $doctorEmployeeId, $slotStart, $slotEnd)) {
                return false;
            }

            if ($this->hasConflictingAppointment($tenantUserId, $doctorEmployeeId, $date, $slotStart, $slotEnd, $excludeAppointmentId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{start: string, end: string, label: string}>
     */
    public function availableSlotsForDate(int $tenantUserId, int $doctorEmployeeId, string $date): array
    {
        $date = trim($date);

        if ($date === '') {
            return [];
        }

        if ($this->isDoctorDayFullyBlocked($tenantUserId, $doctorEmployeeId, $date)) {
            return [];
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedules = DoctorSchedule::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $slots = [];

        foreach ($schedules as $schedule) {
            $duration = max(5, (int) $schedule->slot_duration_minutes);
            $windowStart = $this->toCarbon($date, (string) $schedule->start_time);
            $windowEnd = $this->toCarbon($date, (string) $schedule->end_time);

            $cursor = $windowStart->copy();

            while ($cursor->copy()->addMinutes($duration)->lte($windowEnd)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($duration);
                $startStr = $slotStart->format('H:i:s');

                if (
                    ! $this->isTimeBlocked($tenantUserId, $doctorEmployeeId, $date, $slotStart, $slotEnd)
                    && ! $this->hasConflictingAppointment($tenantUserId, $doctorEmployeeId, $date, $slotStart, $slotEnd, null)
                ) {
                    $slots[$startStr] = [
                        'start' => $startStr,
                        'end' => $slotEnd->format('H:i:s'),
                        'label' => $slotStart->format('g:i A'),
                    ];
                }

                $cursor->addMinutes($duration);
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * @return list<array{date: string, label: string, slots_count: int}>
     */
    public function availableDates(int $tenantUserId, int $doctorEmployeeId, Carbon $from, Carbon $to, int $maxDays = 30): array
    {
        $dates = [];
        $cursor = $from->copy()->startOfDay();
        $limit = $to->copy()->startOfDay();

        while ($cursor->lte($limit) && count($dates) < $maxDays) {
            if ($cursor->gte(now()->startOfDay())) {
                $dateStr = $cursor->toDateString();
                $slots = $this->availableSlotsForDate($tenantUserId, $doctorEmployeeId, $dateStr);

                if ($slots !== []) {
                    $dates[] = [
                        'date' => $dateStr,
                        'label' => $cursor->translatedFormat('l j F'),
                        'slots_count' => count($slots),
                    ];
                }
            }

            $cursor->addDay();
        }

        return $dates;
    }

    public function doctorHasSchedules(int $tenantUserId, int $doctorEmployeeId): bool
    {
        return DoctorSchedule::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->where('is_active', true)
            ->exists();
    }

    private function isWithinDoctorSchedule(
        int $tenantUserId,
        int $doctorEmployeeId,
        Carbon $slotStart,
        Carbon $slotEnd,
    ): bool {
        $hasSchedules = $this->doctorHasSchedules($tenantUserId, $doctorEmployeeId);

        if (! $hasSchedules) {
            $legacyStart = $this->toCarbon($slotStart->toDateString(), self::LEGACY_DAY_START);
            $legacyEnd = $this->toCarbon($slotStart->toDateString(), self::LEGACY_DAY_END);

            return $slotStart->gte($legacyStart) && $slotEnd->lte($legacyEnd);
        }

        $dayOfWeek = $slotStart->dayOfWeek;

        /** @var Collection<int, DoctorSchedule> $windows */
        $windows = DoctorSchedule::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        foreach ($windows as $window) {
            $windowStart = $this->toCarbon($slotStart->toDateString(), (string) $window->start_time);
            $windowEnd = $this->toCarbon($slotStart->toDateString(), (string) $window->end_time);

            if ($slotStart->gte($windowStart) && $slotEnd->lte($windowEnd)) {
                return true;
            }
        }

        return false;
    }

    private function isDoctorDayFullyBlocked(int $tenantUserId, int $doctorEmployeeId, string $date): bool
    {
        $clinicWide = BlockedSlot::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereNull('doctor_employee_id')
            ->whereDate('blocked_date', $date)
            ->where('is_full_day', true)
            ->exists();

        if ($clinicWide) {
            return true;
        }

        return BlockedSlot::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->whereDate('blocked_date', $date)
            ->where('is_full_day', true)
            ->exists();
    }

    private function isTimeBlocked(
        int $tenantUserId,
        int $doctorEmployeeId,
        string $date,
        Carbon $slotStart,
        Carbon $slotEnd,
    ): bool {
        /** @var Collection<int, BlockedSlot> $blocks */
        $blocks = BlockedSlot::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereDate('blocked_date', $date)
            ->where(function ($q) use ($doctorEmployeeId): void {
                $q->whereNull('doctor_employee_id')
                    ->orWhere('doctor_employee_id', $doctorEmployeeId);
            })
            ->get();

        foreach ($blocks as $block) {
            if ($block->is_full_day) {
                return true;
            }

            if ($block->start_time === null || $block->end_time === null) {
                continue;
            }

            $blockStart = $this->toCarbon($date, (string) $block->start_time);
            $blockEnd = $this->toCarbon($date, (string) $block->end_time);

            if ($slotStart->lt($blockEnd) && $slotEnd->gt($blockStart)) {
                return true;
            }
        }

        return false;
    }

    private function hasConflictingAppointment(
        int $tenantUserId,
        int $doctorEmployeeId,
        string $date,
        Carbon $slotStart,
        Carbon $slotEnd,
        ?int $excludeAppointmentId,
    ): bool {
        /** @var Collection<int, Appointment> $appointments */
        $appointments = Appointment::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('doctor_employee_id', $doctorEmployeeId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_COMPLETED])
            ->when($excludeAppointmentId !== null, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get(['id', 'start_time', 'end_time']);

        foreach ($appointments as $appointment) {
            $apptStart = $this->toCarbon($date, (string) $appointment->start_time);
            $apptEnd = $appointment->end_time
                ? $this->toCarbon($date, (string) $appointment->end_time)
                : $apptStart->copy()->addMinutes(self::DEFAULT_SLOT_MINUTES);

            if ($slotStart->lt($apptEnd) && $slotEnd->gt($apptStart)) {
                return true;
            }
        }

        return false;
    }

    private function slotEnd(string $date, string $startTime, ?string $endTime): Carbon
    {
        if ($endTime !== null && trim($endTime) !== '') {
            return $this->toCarbon($date, $endTime);
        }

        return $this->toCarbon($date, $startTime)->addMinutes(self::DEFAULT_SLOT_MINUTES);
    }

    private function toCarbon(string $date, string $time): Carbon
    {
        $time = trim($time);

        if (strlen($time) === 5) {
            $time .= ':00';
        }

        return Carbon::parse($date.' '.$time);
    }
}
