<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Services\Nursery\Portal\NurseryPortalFinanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * السجل المالي (اشتراكات) في بوابة ولي الأمر — قراءة فقط.
 */
final class NurseryPortalFinanceWebController extends Controller
{
    public function index(
        Request $request,
        NurseryPortalFinanceService $finance,
    ): View {
        /** @var Guardian $guardian */
        $guardian = $request->attributes->get('nursery_portal_guardian');
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');

        $subscriptions = $finance->subscriptionsForGuardian($tenantUserId, (int) $guardian->id);

        return view('nursery.portal.finance', $this->portalViewData($request, [
            'guardian' => $guardian,
            'subscriptions' => $subscriptions,
        ]));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function portalViewData(Request $request, array $extra = []): array
    {
        /** @var TenantProfile $profile */
        $profile = $request->attributes->get('nursery_portal_profile');
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');
        $slug = $profile->slug ?? $profile->domain ?? (string) $request->route('tenant_slug');

        return array_merge([
            'tenantSlug' => $slug,
            'nurseryName' => NurserySetting::forTenant($tenantUserId)->nursery_name,
            'tenantUserId' => $tenantUserId,
        ], $extra);
    }
}
