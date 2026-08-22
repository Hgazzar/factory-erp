<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\NicheLexiconService;
use App\Services\Tenant\TenantContext;
use App\Support\ErpRoles;
use App\Support\NurseryAccess;
use App\Support\NurseryFinanceRouteGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * حماية مسارات Finance:
 * - niches غير الحضانة: يبقى السلوك السابق (admin / super_admin فقط).
 * - Niche الحضانة: صلاحية per-screen عبر NurseryAccess (المالك يمرّ دائماً).
 */
final class EnsureNurseryStaffCanAccessFinance
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly NicheLexiconService $lexicon,
        private readonly NurseryAccess $nurseryAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);
        $nicheKey = $tenantUserId !== null
            ? $this->lexicon->resolveNicheKey($tenantUserId)
            : null;

        if ($nicheKey !== 'nurseries') {
            if ($user->role === 'admin' || ErpRoles::isSuperAdmin($user)) {
                return $next($request);
            }

            return $this->deny($request, 'ليس لديك صلاحية للوصول إلى المحاسبة.');
        }

        if ($this->nurseryAccess->isTenantOwner($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $capability = NurseryFinanceRouteGate::capabilityForRoute($routeName);

        if ($capability === null || ! $this->nurseryAccess->allows($capability, $user)) {
            return $this->deny($request, 'ليس لديك صلاحية لهذه الشاشة المالية في الحضانة.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
                'code' => 'nursery_finance_forbidden',
            ], 403);
        }

        abort(403, $message);
    }
}
