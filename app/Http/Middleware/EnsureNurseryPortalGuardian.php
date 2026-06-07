<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتأكد أن ولي الأمر مسجّل دخوله في جلسة البوابة لنفس المستأجر.
 */
final class EnsureNurseryPortalGuardian
{
    public function __construct(
        private readonly NurseryPortalAuthService $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');

        if ($tenantUserId < 1 || ! $this->auth->isAuthenticatedForTenant($tenantUserId)) {
            $slug = (string) $request->route('tenant_slug');

            return redirect()
                ->route('nursery.portal.login', ['tenant_slug' => $slug])
                ->with('warning', 'يرجى تسجيل الدخول للمتابعة.');
        }

        $guardian = $this->auth->currentGuardian($tenantUserId);

        if ($guardian === null) {
            $this->auth->logout();

            return redirect()
                ->route('nursery.portal.login', ['tenant_slug' => $request->route('tenant_slug')])
                ->with('warning', 'انتهت الجلسة. يرجى تسجيل الدخول مجدداً.');
        }

        $request->attributes->set('nursery_portal_guardian', $guardian);

        return $next($request);
    }
}
