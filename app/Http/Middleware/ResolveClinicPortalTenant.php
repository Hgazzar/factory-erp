<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TenantProfile;
use App\Services\Tenant\TenantModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحلّ مستأجر بوابة المريض من slug دون تسجيل دخول الموظفين.
 */
final class ResolveClinicPortalTenant
{
    public function __construct(
        private readonly TenantModuleRegistry $modules,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = strtolower(trim((string) $request->route('tenant_slug')));

        if ($slug === '') {
            abort(404);
        }

        $profile = TenantProfile::resolveBySlug($slug);

        if ($profile === null || $profile->status !== TenantProfile::STATUS_ACTIVE) {
            abort(404, 'العيادة غير موجودة أو غير نشطة.');
        }

        if ($profile->niche_key !== 'medical_clinics') {
            abort(404, 'بوابة الحجز غير متاحة لهذا المستأجر.');
        }

        $tenantUserId = (int) $profile->tenant_user_id;

        if (! $this->modules->isEnabled('clinic', $tenantUserId)) {
            abort(404, 'موديول العيادة غير مفعّل.');
        }

        $request->attributes->set('clinic_portal_tenant_user_id', $tenantUserId);
        $request->attributes->set('clinic_portal_profile', $profile);

        return $next($request);
    }
}
