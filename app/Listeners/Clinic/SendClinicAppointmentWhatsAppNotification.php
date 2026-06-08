<?php

declare(strict_types=1);

namespace App\Listeners\Clinic;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Services\Clinic\ClinicAppointmentWhatsAppNotifier;

final class SendClinicAppointmentWhatsAppNotification
{
    public function __construct(
        private readonly ClinicAppointmentWhatsAppNotifier $notifier,
    ) {}

    public function handle(ClinicAppointmentBooked $event): void
    {
        $this->notifier->dispatchConfirmation(
            $event->tenantUserId,
            (int) $event->appointment->id,
        );
    }
}
