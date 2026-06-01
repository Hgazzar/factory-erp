<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantFeatureRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckFeature
{
    public function __construct(
        private readonly TenantFeatureRegistry $features,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $featureKey = strtolower(trim($featureKey));
        if ($featureKey === '') {
            abort(404);
        }

        $tenantId = $request->attributes->get('store_portal_tenant_user_id')
            ?? $request->attributes->get('clinic_portal_tenant_user_id');
        $tenantId = $tenantId !== null ? (int) $tenantId : $this->tenantContext->resolveTenantUserId();

        if ($tenantId < 1) {
            abort(404, 'الميزة غير متاحة.');
        }

        if (! $this->features->isEnabled($featureKey, $tenantId)) {
            abort(404, 'الميزة غير متاحة في باقتك الحالية.');
        }

        return $next($request);
    }
}
