<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Services\Store\StoreNicheCapabilities;
use App\Services\Tenant\TenantModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveStorePortalTenant
{
    public function __construct(
        private readonly TenantModuleRegistry $modules,
        private readonly StoreNicheCapabilities $storeNiches,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = strtolower(trim((string) $request->route('tenant_slug')));

        if ($slug === '') {
            abort(404);
        }

        $profile = TenantProfile::resolveBySlug($slug);

        if ($profile === null || $profile->status !== TenantProfile::STATUS_ACTIVE) {
            abort(404, 'المتجر غير موجود أو غير نشط.');
        }

        $nicheKey = strtolower(trim((string) $profile->niche_key));
        if (! $this->storeNiches->supportsOnlineStorePortal($nicheKey)) {
            abort(404, 'المتجر الإلكتروني غير متاح لهذا النوع من المنشآت.');
        }

        $tenantUserId = (int) $profile->tenant_user_id;

        if (! $this->modules->isEnabled('pos', $tenantUserId)) {
            abort(404, 'موديول نقاط البيع غير مفعّل.');
        }

        $storeSettings = TenantStoreSetting::forTenant($tenantUserId);
        if (! $storeSettings->is_store_enabled) {
            abort(404, 'المتجر الإلكتروني متوقف مؤقتاً.');
        }

        $request->attributes->set('store_portal_tenant_user_id', $tenantUserId);
        $request->attributes->set('store_portal_profile', $profile);
        $request->attributes->set('store_portal_settings', $storeSettings);
        $request->attributes->set('store_portal_niche_key', $nicheKey);

        return $next($request);
    }
}
