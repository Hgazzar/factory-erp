<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Services\Nursery\Portal\NurseryPortalChildProfileService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ملف الطفل في بوابة ولي الأمر — قراءة فقط (حضور، صحة، أدوية).
 */
final class NurseryPortalChildWebController extends Controller
{
    /**
     * عرض ملف الطفل لولي الأمر المسجّل فقط.
     */
    public function show(
        Request $request,
        NurseryPortalChildProfileService $profiles,
    ): View {
        /** @var Guardian $guardian */
        $guardian = $request->attributes->get('nursery_portal_guardian');
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');
        $childId = (int) $request->route('childId');

        $profile = $profiles->profile($tenantUserId, (int) $guardian->id, $childId);

        $relationshipLabels = [
            'father' => 'أب',
            'mother' => 'أم',
            'guardian' => 'ولي أمر',
            'other' => 'أخرى',
        ];

        return view('nursery.portal.child', $this->portalViewData($request, [
            'guardian' => $guardian,
            'child' => $profile['child'],
            'todayLog' => $profile['todayLog'],
            'todayStatus' => $profile['todayStatus'],
            'todayStatusLabel' => $profile['todayStatusLabel'],
            'relationshipLabels' => $relationshipLabels,
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
