<?php

declare(strict_types=1);

namespace App\Listeners\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Services\Clinic\ClinicPortalBookingService;
use App\Services\Clinic\WhatsAppNotificationService;

final class SendClinicAppointmentWhatsAppNotification
{
    public function __construct(
        private readonly WhatsAppNotificationService $whatsapp,
        private readonly ClinicPortalBookingService $portalBooking,
    ) {}

    public function handle(ClinicAppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $appointment->loadMissing(['patient', 'doctor']);

        $phone = $appointment->patient?->phone;

        if ($phone === null || trim($phone) === '') {
            return;
        }

        $this->portalBooking->ensureManageToken($appointment);
        $appointment->refresh();

        $this->whatsapp->sendAppointmentConfirmation($event->tenantUserId, $phone, [
            'patient_name' => $appointment->patient?->name,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'start_time' => substr((string) $appointment->start_time, 0, 5),
            'doctor_name' => $appointment->doctor?->name,
            'appointment_number' => $appointment->appointment_number,
            'portal_url' => $this->whatsapp->portalUrlForTenant($event->tenantUserId),
            'manage_url' => $this->whatsapp->portalManageUrlForAppointment($appointment),
        ]);
    }
}
