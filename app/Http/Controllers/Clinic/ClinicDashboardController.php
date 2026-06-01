<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\Prescription;
use App\Models\TenantProfile;
use App\Services\Clinic\ClinicPortalQrCodeService;
use App\Services\Tenant\TenantFeatureRegistry;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class ClinicDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(ClinicPortalQrCodeService $qrCode, TenantFeatureRegistry $features): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $today = now()->toDateString();

        $stats = [
            'patients_total' => Patient::query()->where('user_id', $tenantUserId)->count(),
            'appointments_today' => Appointment::query()
                ->where('user_id', $tenantUserId)
                ->whereDate('appointment_date', $today)
                ->count(),
            'pending_today' => Appointment::query()
                ->where('user_id', $tenantUserId)
                ->whereDate('appointment_date', $today)
                ->where('status', Appointment::STATUS_PENDING)
                ->count(),
            'prescriptions_week' => Prescription::query()
                ->where('user_id', $tenantUserId)
                ->where('prescribed_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $upcoming = Appointment::query()
            ->with(['patient:id,name,phone', 'doctor:id,name'])
            ->where('user_id', $tenantUserId)
            ->where('appointment_date', '>=', $today)
            ->where('status', Appointment::STATUS_PENDING)
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(8)
            ->get();

        $portalUrl = null;
        $qrDataUri = null;

        if ($features->isEnabled('clinic_patient_portal', $tenantUserId)) {
            $profile = TenantProfile::forTenantUser($tenantUserId);
            $tenantSlug = $profile?->slug ?? $profile?->domain;
            $portalUrl = $tenantSlug ? route('clinic.portal.book', ['tenant_slug' => $tenantSlug]) : null;
            $qrDataUri = $portalUrl ? $qrCode->pngDataUri($portalUrl) : null;
        }

        return view('clinic.dashboard', compact('stats', 'upcoming', 'portalUrl', 'qrDataUri'));
    }

    public function downloadPortalQr(ClinicPortalQrCodeService $qrCode): Response
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $profile = TenantProfile::forTenantUser($tenantUserId);
        $tenantSlug = $profile?->slug ?? $profile?->domain;

        abort_if($tenantSlug === null || trim($tenantSlug) === '', 404, 'رابط البوابة غير متاح.');

        $portalUrl = route('clinic.portal.book', ['tenant_slug' => $tenantSlug]);
        $png = $qrCode->pngBinary($portalUrl);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="clinic-portal-qr-'.$tenantSlug.'.png"',
        ]);
    }
}
