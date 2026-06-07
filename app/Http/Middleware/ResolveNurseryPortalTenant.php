<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TenantProfile;
use App\Services\Tenant\TenantModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحلّ مستأجر بوابة أولياء الأمور من slug دون تسجيل دخول موظفي ERP.
 */
final class ResolveNurseryPortalTenant
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
            abort(404, 'الحضانة غير موجودة أو غير نشطة.');
        }

        if ($profile->niche_key !== 'nurseries') {
            abort(404, 'بوابة أولياء الأمور غير متاحة لهذا المستأجر.');
        }

        $tenantUserId = (int) $profile->tenant_user_id;

        if (! $this->modules->isEnabled('nursery', $tenantUserId)) {
            abort(404, 'موديول الحضانة غير مفعّل.');
        }

        $request->attributes->set('nursery_portal_tenant_user_id', $tenantUserId);
        $request->attributes->set('nursery_portal_profile', $profile);

        return $next($request);
    }
}
