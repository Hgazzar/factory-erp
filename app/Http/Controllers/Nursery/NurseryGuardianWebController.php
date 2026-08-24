<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\User;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryPortalInviteService;
use App\Support\NurseryAccess;
use App\Support\PremiumFeatureKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NurseryGuardianWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenant = $this->resolveTenantUser();
        $tenantUserId = (int) $tenant->id;
        $q = trim((string) $request->query('q', ''));

        $base = Guardian::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'portal_active' => (clone $base)->whereNotNull('portal_access_token')
                ->where('portal_access_token', '!=', '')
                ->count(),
            'logged_in' => (clone $base)->whereNotNull('portal_last_login_at')->count(),
        ];

        $guardians = Guardian::query()
            ->withCount('children')
            ->where('user_id', $tenantUserId)
            ->when($q !== '', function ($query) use ($q): void {
                $digits = preg_replace('/\D+/', '', $q) ?? $q;
                $query->where(function ($inner) use ($q, $digits): void {
                    $inner->where('name', 'like', '%'.$q.'%');
                    if ($digits !== '') {
                        $inner->orWhere('phone', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$digits.'%');
                    } else {
                        $inner->orWhere('phone', 'like', '%'.$q.'%');
                    }
                    $inner->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_CHILDREN);
        $portalEnabled = $tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL);

        return view('nursery.guardians.index', [
            'guardians' => $guardians,
            'listStats' => $listStats,
            'spark' => app(NurseryDashboardService::class)->guardiansSparkMeta($listStats),
            'q' => $q,
            'canManage' => $canManage,
            'portalEnabled' => $portalEnabled,
        ]);
    }

    public function show(Guardian $guardian): View
    {
        $tenant = $this->resolveTenantUser();
        $tenantUserId = (int) $tenant->id;
        abort_unless((int) $guardian->user_id === $tenantUserId, 404);

        $children = Child::query()
            ->with(['activeEnrollment.classroom:id,name', 'attachments'])
            ->where('user_id', $tenantUserId)
            ->where('guardian_id', $guardian->id)
            ->orderBy('name')
            ->get();

        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_CHILDREN);
        $portalEnabled = $tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL);
        $portalActive = trim((string) $guardian->portal_access_token) !== '';

        return view('nursery.guardians.show', [
            'guardian' => $guardian,
            'children' => $children,
            'canManage' => $canManage,
            'portalEnabled' => $portalEnabled,
            'portalActive' => $portalActive,
        ]);
    }

    public function sendPortalInvite(Guardian $guardian, NurseryPortalInviteService $inviteService): RedirectResponse
    {
        $tenant = $this->resolveTenantUser();
        $tenantUserId = (int) $tenant->id;
        abort_unless((int) $guardian->user_id === $tenantUserId, 404);
        abort_unless($tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL), 404);

        $result = $inviteService->sendInviteToGuardian($tenantUserId, $guardian);

        return back()->with($result['sent'] ? 'success' : 'error', $result['message']);
    }

    public function revokePortalAccess(Guardian $guardian, NurseryPortalInviteService $inviteService): RedirectResponse
    {
        $tenant = $this->resolveTenantUser();
        $tenantUserId = (int) $tenant->id;
        abort_unless((int) $guardian->user_id === $tenantUserId, 404);
        abort_unless($tenant->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL), 404);

        $inviteService->revokePortalAccess($tenantUserId, $guardian);

        return back()->with('success', 'تم إلغاء وصول ولي الأمر للبوابة.');
    }

    private function resolveTenantUser(): User
    {
        return User::query()->findOrFail($this->resolveOperationsTenantUserId());
    }
}
