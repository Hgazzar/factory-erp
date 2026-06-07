<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Services\Nursery\Portal\NurseryPortalAccessService;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * صفحات بوابة أولياء الأمور — عامة ومحمية.
 */
final class NurseryPortalWebController extends Controller
{
    /**
     * صفحة تسجيل الدخول (OTP أو إعادة توجيه للرئيسية إن كانت الجلسة نشطة).
     */
    public function login(Request $request, NurseryPortalAuthService $auth): View|RedirectResponse
    {
        $tenantUserId = $this->tenantUserId($request);

        if ($auth->isAuthenticatedForTenant($tenantUserId)) {
            return redirect()->route('nursery.portal.home', [
                'tenant_slug' => $this->tenantSlug($request),
            ]);
        }

        return view('nursery.portal.login', $this->portalViewData($request, [
            'otpLogOnly' => (bool) config('nursery.portal.otp_log_only', true),
        ]));
    }

    /**
     * لوحة ولي الأمر — قائمة أطفاله (MVP).
     */
    public function home(
        Request $request,
        NurseryPortalAccessService $access,
    ): View {
        /** @var Guardian $guardian */
        $guardian = $request->attributes->get('nursery_portal_guardian');
        $tenantUserId = $this->tenantUserId($request);

        $children = $access->activeChildrenForGuardian($tenantUserId, (int) $guardian->id);

        return view('nursery.portal.home', $this->portalViewData($request, [
            'guardian' => $guardian,
            'children' => $children,
        ]));
    }

    /**
     * قبول دعوة الانضمام عبر magic link.
     */
    public function acceptInvite(
        Request $request,
        NurseryPortalAuthService $auth,
    ): RedirectResponse {
        $tenantUserId = $this->tenantUserId($request);
        $slug = $this->tenantSlug($request);
        $token = (string) $request->route('token');

        try {
            $auth->loginViaInviteToken($tenantUserId, $token);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('nursery.portal.login', ['tenant_slug' => $slug])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.portal.home', ['tenant_slug' => $slug])
            ->with('success', 'مرحباً بك في بوابة أولياء الأمور.');
    }

    /**
     * إنهاء جلسة ولي الأمر.
     */
    public function logout(Request $request, NurseryPortalAuthService $auth): RedirectResponse
    {
        $auth->logout();

        return redirect()
            ->route('nursery.portal.login', ['tenant_slug' => $this->tenantSlug($request)])
            ->with('success', 'تم تسجيل الخروج.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function portalViewData(Request $request, array $extra = []): array
    {
        /** @var TenantProfile $profile */
        $profile = $request->attributes->get('nursery_portal_profile');
        $tenantUserId = $this->tenantUserId($request);
        $slug = $profile->slug ?? $profile->domain ?? $this->tenantSlug($request);
        $branding = NurserySetting::forTenant($tenantUserId)->branding();

        return array_merge([
            'tenantSlug' => $slug,
            'nurseryName' => $branding['nursery_name'],
            'nurseryDisplayName' => $branding['display_name'],
            'nurseryLogoUrl' => $branding['logo_url'],
            'tenantUserId' => $tenantUserId,
        ], $extra);
    }

    private function tenantUserId(Request $request): int
    {
        return (int) $request->attributes->get('nursery_portal_tenant_user_id');
    }

    private function tenantSlug(Request $request): string
    {
        return (string) $request->route('tenant_slug');
    }
}
