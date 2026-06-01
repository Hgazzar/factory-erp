<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\TenantProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class ClinicPortalBookingService
{
    public function __construct(
        private readonly ClinicAppointmentService $appointments,
        private readonly ClinicPatientService $patients,
        private readonly ClinicAvailabilityService $availability,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function book(int $tenantUserId, array $data): Appointment
    {
        $name = trim((string) ($data['patient_name'] ?? ''));
        $phone = trim((string) ($data['patient_phone'] ?? ''));
        $doctorId = (int) ($data['doctor_employee_id'] ?? 0);
        $date = trim((string) ($data['appointment_date'] ?? ''));
        $startTime = trim((string) ($data['start_time'] ?? ''));

        if ($name === '' || $phone === '' || $doctorId < 1 || $date === '' || $startTime === '') {
            throw new InvalidArgumentException('جميع الحقول مطلوبة لإتمام الحجز.');
        }

        if (! $this->availability->isSlotAvailable($tenantUserId, $doctorId, $date, $startTime)) {
            throw new RuntimeException('عذراً، هذا الموعد لم يعد متاحاً. يرجى اختيار وقت آخر.');
        }

        return DB::transaction(function () use ($tenantUserId, $name, $phone, $doctorId, $date, $startTime): Appointment {
            $patient = $this->resolvePatient($tenantUserId, $name, $phone);

            $appointment = $this->appointments->schedule(
                $tenantUserId,
                [
                    'patient_id' => $patient->id,
                    'doctor_employee_id' => $doctorId,
                    'appointment_date' => $date,
                    'start_time' => $startTime,
                    'booking_source' => Appointment::SOURCE_PORTAL,
                    'portal_manage_token' => Str::random(48),
                ],
                $tenantUserId,
            );

            return $appointment->fresh(['patient', 'doctor']);
        });
    }

    private function resolvePatient(int $tenantUserId, string $name, string $phone): Patient
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? $phone;

        $existing = Patient::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where(function ($q) use ($phone, $normalizedPhone): void {
                $q->where('phone', $phone);

                if ($normalizedPhone !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?", [$normalizedPhone]);
                }
            })
            ->first();

        if ($existing !== null) {
            if ($existing->name !== $name) {
                $existing->name = $name;
                $existing->save();
            }

            return $existing;
        }

        return $this->patients->create($tenantUserId, [
            'name' => $name,
            'phone' => $phone,
        ], $tenantUserId);
    }

    public function findByManageToken(int $tenantUserId, string $token): ?Appointment
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        return Appointment::withoutGlobalScopes()
            ->with(['patient:id,name,phone', 'doctor:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('portal_manage_token', $token)
            ->first();
    }

    public function manageUrlForAppointment(Appointment $appointment): ?string
    {
        $token = $this->ensureManageToken($appointment);
        $profile = TenantProfile::forTenantUser((int) $appointment->user_id);
        $slug = $profile?->slug ?? $profile?->domain;

        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return route('clinic.portal.manage', [
            'tenant_slug' => $slug,
            'token' => $token,
        ]);
    }

    public function ensureManageToken(Appointment $appointment): string
    {
        $token = trim((string) $appointment->portal_manage_token);

        if ($token !== '') {
            return $token;
        }

        $token = Str::random(48);
        $appointment->portal_manage_token = $token;
        $appointment->save();

        return $token;
    }

    public function cancelFromPortal(Appointment $appointment): Appointment
    {
        $this->assertPortalActionAllowed($appointment);

        return $this->appointments->cancel($appointment);
    }

    public function rescheduleFromPortal(Appointment $appointment, string $date, string $startTime): Appointment
    {
        $this->assertPortalActionAllowed($appointment);

        return $this->appointments->reschedule($appointment, $date, $startTime);
    }

    private function assertPortalActionAllowed(Appointment $appointment): void
    {
        if ($appointment->isCancelled()) {
            throw new RuntimeException('هذا الموعد ملغى بالفعل.');
        }

        if ($appointment->isPaid()) {
            throw new RuntimeException('لا يمكن تعديل/إلغاء موعد مدفوع عبر البوابة.');
        }

        $cutoffHours = max(1, (int) config('clinic.portal.manage_cutoff_hours', 24));
        $scheduledAt = Carbon::parse(
            $appointment->appointment_date?->format('Y-m-d').' '.substr((string) $appointment->start_time, 0, 8)
        );

        if ($scheduledAt->lte(now()->addHours($cutoffHours))) {
            throw new RuntimeException("يمكن تعديل أو إلغاء الموعد قبل {$cutoffHours} ساعة على الأقل.");
        }
    }
}
