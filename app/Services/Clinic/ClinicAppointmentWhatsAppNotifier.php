<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Jobs\Clinic\SendClinicAppointmentConfirmationWhatsAppJob;
use App\Jobs\Clinic\SendClinicAppointmentReminderWhatsAppJob;
use App\Models\Clinic\Appointment;
use Illuminate\Support\Facades\Log;

final class ClinicAppointmentWhatsAppNotifier
{
    public function __construct(
        private readonly WhatsAppNotificationService $whatsapp,
        private readonly ClinicPortalBookingService $portalBooking,
    ) {}

    public function dispatchConfirmation(int $tenantUserId, int $appointmentId): void
    {
        SendClinicAppointmentConfirmationWhatsAppJob::dispatch($tenantUserId, $appointmentId);
    }

    public function dispatchReminder(int $tenantUserId, int $appointmentId): void
    {
        SendClinicAppointmentReminderWhatsAppJob::dispatch($tenantUserId, $appointmentId);
    }

    public function notifyConfirmation(int $tenantUserId, int $appointmentId): void
    {
        $appointment = $this->loadAppointment($tenantUserId, $appointmentId);
        if ($appointment === null) {
            return;
        }

        $phone = trim((string) ($appointment->patient?->phone ?? ''));
        if ($phone === '') {
            return;
        }

        try {
            $this->portalBooking->ensureManageToken($appointment);
            $appointment->refresh();

            $this->whatsapp->sendAppointmentConfirmation($tenantUserId, $phone, [
                'patient_name' => $appointment->patient?->name,
                'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
                'start_time' => substr((string) $appointment->start_time, 0, 5),
                'doctor_name' => $appointment->doctor?->name,
                'appointment_number' => $appointment->appointment_number,
                'portal_url' => $this->whatsapp->portalUrlForTenant($tenantUserId),
                'manage_url' => $this->whatsapp->portalManageUrlForAppointment($appointment),
            ]);
        } catch (\Throwable $e) {
            Log::error('Clinic WhatsApp confirmation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyReminder(int $tenantUserId, int $appointmentId): bool
    {
        $appointment = $this->loadAppointment($tenantUserId, $appointmentId);
        if ($appointment === null || $appointment->reminder_sent_at !== null) {
            return false;
        }

        $phone = trim((string) ($appointment->patient?->phone ?? ''));
        if ($phone === '') {
            return false;
        }

        try {
            $this->portalBooking->ensureManageToken($appointment);
            $appointment->refresh();

            $ok = $this->whatsapp->sendAppointmentReminder($tenantUserId, $phone, [
                'patient_name' => $appointment->patient?->name,
                'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
                'start_time' => substr((string) $appointment->start_time, 0, 5),
                'doctor_name' => $appointment->doctor?->name,
                'manage_url' => $this->whatsapp->portalManageUrlForAppointment($appointment),
            ]);

            if ($ok) {
                $appointment->forceFill(['reminder_sent_at' => now()])->save();
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('Clinic WhatsApp reminder failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function loadAppointment(int $tenantUserId, int $appointmentId): ?Appointment
    {
        /** @var Appointment|null $appointment */
        $appointment = Appointment::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($appointmentId)
            ->first();

        return $appointment?->loadMissing(['patient', 'doctor']);
    }
}
