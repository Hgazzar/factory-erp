<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Services\Clinic\ClinicPortalQrCodeService;
use App\Services\Nursery\NurseryAttendanceService;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryPortalInviteService;
use App\Services\Store\StoreOnlineDashboardPresenter;
use App\Services\Nursery\NurserySubscriptionService;
use App\Support\NurseryAccess;
use App\Support\PremiumFeatureKeys;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class NurseryDashboardController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(
        NurseryDashboardService $dashboard,
        NurseryAttendanceService $attendance,
        NurserySubscriptionService $subscriptions,
        NurseryPortalInviteService $portalInvite,
    ): View {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $board = $attendance->todayBoard($tenantUserId);
        $overview = $dashboard->overviewStats($tenantUserId);
        $subscriptionKpis = $subscriptions->dashboardKpis($tenantUserId);

        $stats = [
            'present_today' => $board['checked_in']->count(),
            'left_today' => $board['checked_out']->count(),
            'waiting_today' => $board['not_yet']->count(),
        ];

        $portalUrl = null;
        $qrDataUri = null;

        $tenant = \App\Models\User::query()->findOrFail($tenantUserId);

        if ($tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL)) {
            $portalUrl = $portalInvite->portalLoginUrl($tenantUserId);
            if ($portalUrl !== null) {
                $qrDataUri = app(ClinicPortalQrCodeService::class)->pngDataUri($portalUrl);
            }
        }

        $settings = NurserySetting::forTenant($tenantUserId);
        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_SETTINGS);

        $storeOnlinePanel = app(StoreOnlineDashboardPresenter::class)->present(
            $tenantUserId,
            StoreOnlineDashboardPresenter::VARIANT_COMPACT,
        );

        return view('nursery.dashboard', compact(
            'overview',
            'stats',
            'board',
            'subscriptionKpis',
            'portalUrl',
            'qrDataUri',
            'settings',
            'canManage',
            'storeOnlinePanel',
        ));
    }

    public function downloadPortalQr(
        NurseryPortalInviteService $portalInvite,
        ClinicPortalQrCodeService $qrCode,
    ): Response {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $portalUrl = $portalInvite->portalLoginUrl($tenantUserId);

        abort_if($portalUrl === null, 404, 'رابط البوابة غير متاح.');

        $profile = TenantProfile::forTenantUser($tenantUserId);
        $slug = $profile?->slug ?? $profile?->domain ?? 'nursery';

        return response($qrCode->pngBinary($portalUrl), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="nursery-portal-qr-'.$slug.'.png"',
        ]);
    }
}
