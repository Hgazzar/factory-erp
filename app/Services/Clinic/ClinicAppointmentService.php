<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * إدارة حجوزات العيادة — الحالات، الترقيم، والترحيل المالي عند الاكتمال والدفع.
 */
final class ClinicAppointmentService
{
    public function __construct(
        private readonly ClinicAccountingService $accounting,
        private readonly ClinicBillingService $billing,
        private readonly ClinicAvailabilityService $availability,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(
        int $tenantUserId,
        array $data,
        ?int $createdByUserId = null,
        bool $dispatchEvent = true,
    ): Appointment {
        $patientId = (int) ($data['patient_id'] ?? 0);
        $doctorId = isset($data['doctor_employee_id']) ? (int) $data['doctor_employee_id'] : null;
        $date = (string) ($data['appointment_date'] ?? '');
        $startTime = (string) ($data['start_time'] ?? '');
        $endTime = isset($data['end_time']) ? (string) $data['end_time'] : null;
        $bookingSource = (string) ($data['booking_source'] ?? Appointment::SOURCE_STAFF);
        $portalManageToken = isset($data['portal_manage_token']) ? trim((string) $data['portal_manage_token']) : null;

        if ($patientId < 1 || $date === '' || $startTime === '') {
            throw new InvalidArgumentException('المريض والتاريخ والوقت مطلوبون.');
        }

        $patientExists = Patient::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($patientId)
            ->exists();

        if (! $patientExists) {
            throw new InvalidArgumentException('المريض غير موجود.');
        }

        if ($doctorId !== null && $doctorId > 0) {
            $this->availability->assertSlotAvailable($tenantUserId, $doctorId, $date, $startTime, $endTime);
        }

        $appointment = Appointment::query()->create([
            'user_id' => $tenantUserId,
            'appointment_number' => $this->nextAppointmentNumber($tenantUserId),
            'patient_id' => $patientId,
            'doctor_employee_id' => $doctorId > 0 ? $doctorId : null,
            'appointment_date' => $date,
            'start_time' => $this->normalizeTime($startTime),
            'end_time' => $endTime !== null && trim($endTime) !== '' ? $this->normalizeTime($endTime) : null,
            'status' => Appointment::STATUS_PENDING,
            'booking_source' => in_array($bookingSource, [Appointment::SOURCE_STAFF, Appointment::SOURCE_PORTAL], true)
                ? $bookingSource
                : Appointment::SOURCE_STAFF,
            'portal_manage_token' => $portalManageToken !== '' ? $portalManageToken : null,
            'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            'created_by' => $createdByUserId ?? $tenantUserId,
        ]);

        if ($dispatchEvent) {
            event(new ClinicAppointmentBooked($appointment->fresh(['patient', 'doctor']), $tenantUserId));
        }

        return $appointment;
    }

    /**
     * مريض + حجز في عملية واحدة (Quick Add).
     *
     * @param  array<string, mixed>  $patientData
     * @param  array<string, mixed>  $appointmentData
     */
    public function quickSchedule(
        int $tenantUserId,
        array $patientData,
        array $appointmentData,
        ClinicPatientService $patients,
        ?int $createdByUserId = null,
    ): Appointment {
        return DB::transaction(function () use ($tenantUserId, $patientData, $appointmentData, $patients, $createdByUserId): Appointment {
            $patient = $patients->create($tenantUserId, $patientData, $createdByUserId);

            return $this->schedule($tenantUserId, [
                ...$appointmentData,
                'patient_id' => $patient->id,
            ], $createdByUserId);
        });
    }

    public function cancel(Appointment $appointment): Appointment
    {
        if ($appointment->isPaid()) {
            throw new RuntimeException('لا يمكن إلغاء حجز تم تحصيله محاسبياً.');
        }

        if ($appointment->isCancelled()) {
            return $appointment;
        }

        $appointment->status = Appointment::STATUS_CANCELLED;
        $appointment->save();

        return $appointment->fresh(['patient', 'doctor']);
    }

    public function markPending(Appointment $appointment): Appointment
    {
        if ($appointment->isPaid()) {
            throw new RuntimeException('لا يمكن إعادة حجز مدفوع إلى الانتظار.');
        }

        $appointment->status = Appointment::STATUS_PENDING;
        $appointment->save();

        return $appointment->fresh(['patient', 'doctor']);
    }

    public function reschedule(Appointment $appointment, string $date, string $startTime, ?string $endTime = null): Appointment
    {
        if ($appointment->isPaid()) {
            throw new RuntimeException('لا يمكن إعادة جدولة حجز تم تحصيله.');
        }

        if ($appointment->isCancelled()) {
            throw new RuntimeException('لا يمكن إعادة جدولة حجز ملغى.');
        }

        $date = trim($date);
        $startTime = trim($startTime);

        if ($date === '' || $startTime === '') {
            throw new InvalidArgumentException('التاريخ والوقت مطلوبان.');
        }

        $doctorId = $appointment->doctor_employee_id !== null ? (int) $appointment->doctor_employee_id : null;

        if ($doctorId !== null && $doctorId > 0) {
            $this->availability->assertSlotAvailable(
                (int) $appointment->user_id,
                $doctorId,
                $date,
                $startTime,
                $endTime,
                (int) $appointment->id,
            );
        }

        $appointment->appointment_date = $date;
        $appointment->start_time = $this->normalizeTime($startTime);
        $appointment->end_time = $endTime !== null && trim($endTime) !== ''
            ? $this->normalizeTime($endTime)
            : null;
        $appointment->save();

        return $appointment->fresh(['patient', 'doctor']);
    }

    /**
     * @param  list<int>|null  $serviceIds
     * @param  array<int, int>|null  $quantities
     */
    public function completeWithPayment(
        Appointment $appointment,
        float $feeAmount,
        string $paymentMethod = 'cash',
        ?array $serviceIds = null,
        ?array $quantities = null,
    ): Appointment {
        if ($appointment->isCancelled()) {
            throw new RuntimeException('لا يمكن إكمال حجز ملغى.');
        }

        if ($appointment->isPaid()) {
            throw new RuntimeException('تم تحصيل هذا الحجز مسبقاً.');
        }

        $paymentMethod = in_array($paymentMethod, ['cash', 'bank', 'card'], true) ? $paymentMethod : 'cash';

        return DB::transaction(function () use ($appointment, $feeAmount, $paymentMethod, $serviceIds, $quantities): Appointment {
            /** @var Appointment $locked */
            $locked = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isPaid()) {
                throw new RuntimeException('تم تحصيل هذا الحجز مسبقاً.');
            }

            $tenantUserId = (int) $locked->user_id;
            $subtotal = null;
            $vatAmount = null;

            if ($serviceIds !== null && $serviceIds !== []) {
                $quote = $this->billing->attachLinesToAppointment($locked, $tenantUserId, $serviceIds, $quantities);
                $feeAmount = $quote['grand_total'];
                $subtotal = $quote['subtotal'];
                $vatAmount = $quote['vat_total'];
            } else {
                $feeAmount = round($feeAmount, 4);

                if ($feeAmount <= 0) {
                    throw new InvalidArgumentException('مبلغ الكشف يجب أن يكون أكبر من صفر.');
                }
            }

            $locked->loadMissing(['patient']);
            $locked->fee_amount = $feeAmount;
            $locked->subtotal_amount = $subtotal;
            $locked->vat_amount = $vatAmount;
            $locked->payment_method = $paymentMethod;
            $locked->status = Appointment::STATUS_COMPLETED;

            $entry = $this->accounting->recordAppointmentPayment($locked, $tenantUserId);

            $locked->journal_entry_id = $entry->id;
            $locked->paid_at = now();
            $locked->save();

            return $locked->fresh(['patient', 'doctor', 'journalEntry', 'serviceLines.service']);
        });
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function forDateRange(int $tenantUserId, Carbon $from, Carbon $to): Collection
    {
        return Appointment::query()
            ->with(['patient:id,name,phone', 'doctor:id,name,job_title'])
            ->where('user_id', $tenantUserId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();
    }

    private function nextAppointmentNumber(int $tenantUserId): string
    {
        $today = now()->format('Ymd');
        $prefix = 'CLN-'.$today.'-';

        $last = Appointment::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('appointment_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('appointment_number');

        $seq = 1;

        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (strlen($time) === 5) {
            return $time.':00';
        }

        return $time;
    }
}
