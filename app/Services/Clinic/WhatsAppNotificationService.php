<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Core\Messaging\WhatsAppChannelFactory;
use App\Core\Messaging\WhatsAppConfigResolver;
use App\Models\Clinic\Appointment;
use App\Models\CompanySetting;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantFeatureRegistry;

/**
 * قوالب رسائل واتساب للعيادة — النقل عبر Core Messaging.
 */
final class WhatsAppNotificationService
{
    public function __construct(
        private readonly WhatsAppChannelFactory $channels,
    ) {}

    public function isEnabled(): bool
    {
        return $this->channel()->isEnabled();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function sendAppointmentConfirmation(int $tenantUserId, string $toPhone, array $context): bool
    {
        if (! app(TenantFeatureRegistry::class)->isEnabled('clinic_whatsapp_automation', $tenantUserId)) {
            return false;
        }

        $message = $this->buildAppointmentConfirmationMessage($tenantUserId, $context);

        return $this->sendTextMessage($toPhone, $message);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function sendAppointmentReminder(int $tenantUserId, string $toPhone, array $context): bool
    {
        if (! app(TenantFeatureRegistry::class)->isEnabled('clinic_whatsapp_automation', $tenantUserId)) {
            return false;
        }

        $message = $this->buildAppointmentReminderMessage($tenantUserId, $context);

        return $this->sendTextMessage($toPhone, $message);
    }

    public function sendTextMessage(string $toPhone, string $message): bool
    {
        return $this->channel()->sendText($toPhone, $message);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildAppointmentConfirmationMessage(int $tenantUserId, array $context): string
    {
        $clinicName = CompanySetting::forTenant($tenantUserId)?->name ?? config('app.name');
        $patientName = (string) ($context['patient_name'] ?? 'عزيزي المريض');
        $date = (string) ($context['appointment_date'] ?? '');
        $time = (string) ($context['start_time'] ?? '');
        $doctorName = (string) ($context['doctor_name'] ?? 'الطبيب المعالج');
        $appointmentNumber = (string) ($context['appointment_number'] ?? '');
        $portalUrl = (string) ($context['portal_url'] ?? '');
        $manageUrl = (string) ($context['manage_url'] ?? '');

        $timeLabel = strlen($time) >= 5 ? substr($time, 0, 5) : $time;

        $lines = [
            "مرحباً {$patientName} 👋",
            '',
            "تم تأكيد موعدك في {$clinicName}.",
            "📅 التاريخ: {$date}",
            "🕐 الوقت: {$timeLabel}",
            "👨‍⚕️ الطبيب: {$doctorName}",
        ];

        if ($appointmentNumber !== '') {
            $lines[] = "🔖 رقم الحجز: {$appointmentNumber}";
        }

        if ($portalUrl !== '') {
            $lines[] = '';
            $lines[] = "🔗 بوابة المريض: {$portalUrl}";
        }

        if ($manageUrl !== '') {
            $lines[] = "✏️ تعديل/إلغاء الموعد: {$manageUrl}";
        }

        $lines[] = '';
        $lines[] = 'نتمنى لك الشفاء العاجل.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildAppointmentReminderMessage(int $tenantUserId, array $context): string
    {
        $clinic = CompanySetting::forTenant($tenantUserId);
        $clinicName = $clinic?->name ?? config('app.name');
        $address = trim((string) ($clinic?->address ?? ''));
        $patientName = (string) ($context['patient_name'] ?? 'عزيزي المريض');
        $date = (string) ($context['appointment_date'] ?? '');
        $time = (string) ($context['start_time'] ?? '');
        $doctorName = (string) ($context['doctor_name'] ?? 'الطبيب المعالج');
        $manageUrl = (string) ($context['manage_url'] ?? '');

        $timeLabel = strlen($time) >= 5 ? substr($time, 0, 5) : $time;

        $lines = [
            "تذكير بموعدك غداً — {$clinicName} ⏰",
            "مرحباً {$patientName}",
            '',
            "📅 التاريخ: {$date}",
            "🕐 الوقت: {$timeLabel}",
            "👨‍⚕️ الطبيب: {$doctorName}",
        ];

        if ($address !== '') {
            $lines[] = "📍 العنوان: {$address}";
        }

        if ($manageUrl !== '') {
            $lines[] = "🔗 تعديل/إلغاء الموعد: {$manageUrl}";
        }

        $lines[] = '';
        $lines[] = 'بانتظارك ونتمنى لك دوام الصحة.';

        return implode("\n", $lines);
    }

    public function portalUrlForTenant(int $tenantUserId): ?string
    {
        if (! app(TenantFeatureRegistry::class)->isEnabled('clinic_patient_portal', $tenantUserId)) {
            return null;
        }

        $profile = TenantProfile::forTenantUser($tenantUserId);
        $slug = $profile?->slug ?? $profile?->domain;

        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return route('clinic.portal.book', ['tenant_slug' => $slug]);
    }

    public function portalManageUrlForAppointment(Appointment $appointment): ?string
    {
        if (! app(TenantFeatureRegistry::class)->isEnabled('clinic_appointment_self_management', (int) $appointment->user_id)) {
            return null;
        }

        $profile = TenantProfile::forTenantUser((int) $appointment->user_id);
        $slug = $profile?->slug ?? $profile?->domain;
        $token = trim((string) $appointment->portal_manage_token);

        if ($slug === null || $token === '') {
            return null;
        }

        return route('clinic.portal.book', ['tenant_slug' => $slug]).'?manage_token='.urlencode($token).'&appointment_id='.(int) $appointment->id;
    }

    private function channel(): \App\Contracts\Core\Messaging\WhatsAppChannelInterface
    {
        return $this->channels->forProfile(WhatsAppConfigResolver::PROFILE_CLINIC);
    }
}
