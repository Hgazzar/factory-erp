<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Clinic\Appointment;
use App\Services\Clinic\ClinicPortalBookingService;
use App\Services\Clinic\WhatsAppNotificationService;
use App\Services\Tenant\TenantFeatureRegistry;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class ClinicSendWhatsAppRemindersCommand extends Command
{
    protected $signature = 'clinic:send-whatsapp-reminders {--dry-run : Count without sending}';

    protected $description = 'Send WhatsApp reminders for clinic appointments in next 24 hours.';

    public function handle(
        WhatsAppNotificationService $whatsApp,
        ClinicPortalBookingService $portalBooking,
        TenantFeatureRegistry $features,
    ): int
    {
        $now = now();
        $windowEnd = $now->copy()->addDay();
        $dryRun = (bool) $this->option('dry-run');

        $appointments = Appointment::withoutGlobalScopes()
            ->with(['patient:id,name,phone', 'doctor:id,name'])
            ->where('status', Appointment::STATUS_PENDING)
            ->whereNull('reminder_sent_at')
            ->whereDate('appointment_date', '>=', $now->toDateString())
            ->whereDate('appointment_date', '<=', $windowEnd->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($appointments as $appointment) {
            if (! $features->isEnabled('clinic_whatsapp_automation', (int) $appointment->user_id)) {
                $skipped++;
                continue;
            }

            $scheduledAt = Carbon::parse(
                $appointment->appointment_date->format('Y-m-d').' '.substr((string) $appointment->start_time, 0, 8)
            );

            if ($scheduledAt->lt($now) || $scheduledAt->gt($windowEnd)) {
                $skipped++;
                continue;
            }

            $phone = trim((string) ($appointment->patient?->phone ?? ''));
            if ($phone === '') {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $sent++;
                continue;
            }

            $portalBooking->ensureManageToken($appointment);
            $appointment->refresh();

            $ok = $whatsApp->sendAppointmentReminder((int) $appointment->user_id, $phone, [
                'patient_name' => $appointment->patient?->name,
                'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
                'start_time' => substr((string) $appointment->start_time, 0, 5),
                'doctor_name' => $appointment->doctor?->name,
                'manage_url' => $whatsApp->portalManageUrlForAppointment($appointment),
            ]);

            if (! $ok) {
                $skipped++;
                continue;
            }

            $appointment->reminder_sent_at = now();
            $appointment->save();
            $sent++;
        }

        $this->info("Clinic reminders done. sent={$sent}, skipped={$skipped}");

        return self::SUCCESS;
    }
}
