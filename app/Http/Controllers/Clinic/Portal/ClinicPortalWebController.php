<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic\Portal;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Appointment;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantBrandingService;
use App\Services\Clinic\ClinicAvailabilityService;
use App\Services\Clinic\ClinicPortalBookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicPortalWebController extends Controller
{
    public function __construct(
        private readonly TenantBrandingService $brandingService,
    ) {}

    public function book(Request $request): View
    {
        /** @var TenantProfile $profile */
        $profile = $request->attributes->get('clinic_portal_profile');
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $clinicName = $this->brandingService->branding($tenantUserId)['display_name'];

        return view('clinic.portal.book', [
            'clinicName' => $clinicName,
            'tenantSlug' => $profile->slug ?? $profile->domain,
            'apiBase' => url('/c/'.($profile->slug ?? $profile->domain).'/api'),
            'manageToken' => trim((string) $request->query('manage_token', '')),
            'manageAppointmentId' => (int) $request->query('appointment_id', 0),
        ]);
    }

    public function manage(
        Request $request,
        string $token,
        ClinicPortalBookingService $portalBooking,
        ClinicAvailabilityService $availability,
    ): View {
        /** @var TenantProfile $profile */
        $profile = $request->attributes->get('clinic_portal_profile');
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $clinicName = $this->brandingService->branding($tenantUserId)['display_name'];

        $appointment = $this->resolveManagedAppointment($tenantUserId, $token);
        abort_if($appointment === null, 404, 'رابط إدارة الموعد غير صالح.');

        $dates = $appointment->doctor_employee_id
            ? $availability->availableDates($tenantUserId, (int) $appointment->doctor_employee_id, now(), now()->addDays((int) config('clinic.portal.booking_lookahead_days', 30)))
            : [];

        return view('clinic.portal.manage', [
            'clinicName' => $clinicName,
            'tenantSlug' => $profile->slug ?? $profile->domain,
            'appointment' => $appointment,
            'token' => $token,
            'dates' => $dates,
            'canManage' => $this->canManage($appointment),
        ]);
    }

    public function cancel(Request $request, string $token, ClinicPortalBookingService $portalBooking): RedirectResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $appointment = $this->resolveManagedAppointment($tenantUserId, $token);
        abort_if($appointment === null, 404, 'رابط إدارة الموعد غير صالح.');

        try {
            $portalBooking->cancelFromPortal($appointment);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['manage' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إلغاء الموعد بنجاح.');
    }

    public function reschedule(Request $request, string $token, ClinicPortalBookingService $portalBooking): RedirectResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $appointment = $this->resolveManagedAppointment($tenantUserId, $token);
        abort_if($appointment === null, 404, 'رابط إدارة الموعد غير صالح.');

        $data = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'max:8'],
        ]);

        try {
            $portalBooking->rescheduleFromPortal($appointment, (string) $data['appointment_date'], (string) $data['start_time']);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return back()->withErrors(['manage' => $e->getMessage()]);
        }

        return back()->with('success', 'تمت إعادة الجدولة بنجاح.');
    }

    private function canManage(Appointment $appointment): bool
    {
        if ($appointment->isCancelled() || $appointment->isPaid()) {
            return false;
        }

        $cutoffHours = max(1, (int) config('clinic.portal.manage_cutoff_hours', 24));
        $scheduledAt = Carbon::parse(
            $appointment->appointment_date?->format('Y-m-d').' '.substr((string) $appointment->start_time, 0, 8)
        );

        return $scheduledAt->gt(now()->addHours($cutoffHours));
    }

    private function resolveManagedAppointment(int $tenantUserId, string $token): ?Appointment
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $appointment = Appointment::withoutGlobalScopes()
            ->with(['patient:id,name,phone', 'doctor:id,name'])
            ->where('portal_manage_token', $token)
            ->first();

        if ($appointment === null) {
            return null;
        }

        return (int) $appointment->user_id === $tenantUserId ? $appointment : null;
    }
}
