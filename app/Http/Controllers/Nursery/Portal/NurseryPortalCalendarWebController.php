<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurserySetting;
use App\Models\TenantProfile;
use App\Services\Nursery\Portal\NurseryPortalCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * التقويم الأسبوعي في بوابة ولي الأمر — قراءة فقط.
 */
final class NurseryPortalCalendarWebController extends Controller
{
    public function index(
        Request $request,
        NurseryPortalCalendarService $calendar,
    ): View {
        /** @var Guardian $guardian */
        $guardian = $request->attributes->get('nursery_portal_guardian');
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');

        $weekParam = trim((string) $request->query('week', ''));
        $anchor = $weekParam !== '' ? Carbon::parse($weekParam) : now();

        $grid = $calendar->weekGrid($tenantUserId, (int) $guardian->id, $anchor);
        $slug = (string) $request->route('tenant_slug');
        $prevWeek = $grid['weekStart']->copy()->subWeek()->toDateString();
        $nextWeek = $grid['weekStart']->copy()->addWeek()->toDateString();

        return view('nursery.portal.calendar', $this->portalViewData($request, [
            'guardian' => $guardian,
            'grid' => $grid,
            'prevWeekUrl' => route('nursery.portal.calendar', ['tenant_slug' => $slug, 'week' => $prevWeek]),
            'nextWeekUrl' => route('nursery.portal.calendar', ['tenant_slug' => $slug, 'week' => $nextWeek]),
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
