<?php

declare(strict_types=1);

namespace App\Jobs\Clinic;

use App\Services\Clinic\ClinicAppointmentWhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendClinicAppointmentConfirmationWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $appointmentId,
    ) {}

    public function handle(ClinicAppointmentWhatsAppNotifier $notifier): void
    {
        $notifier->notifyConfirmation($this->tenantUserId, $this->appointmentId);
    }
}
