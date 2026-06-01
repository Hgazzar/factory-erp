<?php

declare(strict_types=1);

namespace App\Events\Clinic;

use App\Models\Clinic\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ClinicAppointmentBooked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly int $tenantUserId,
    ) {}
}
