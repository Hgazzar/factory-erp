<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * حارس البوابة — يمنع الوصول لموديول غير مفعّل للمستأجر.
 */
class EnsureTenantModuleEnabled
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantModuleRegistry $moduleRegistry,
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($this->tenantContext->isPlatformOperator($user)) {
            return $next($request);
        }

        $module = strtolower(trim($module));

        if ($this->moduleRegistry->isEnabled($module)) {
            return $next($request);
        }

        $label = config("modules.modules.{$module}.name_ar", $module);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => "موديول «{$label}» غير مفعّل في باقتك. تواصل مع الدعم أو ترقِّ باقتك.",
                'code' => 'module_disabled',
                'module' => $module,
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', "موديول «{$label}» غير مفعّل في باقتك.");
    }
}
