<?php

namespace App\Http\Middleware;

use App\Support\FilamentAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictFilamentPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        // اسمح بصفحات تسجيل الدخول/المصادقة للضيوف (وإلا /admin/login يعطي 404)
        if (
            $request->routeIs('filament.*.auth.*')
            || $request->is('admin/login', 'admin/login/*')
        ) {
            return $next($request);
        }

        if (! $request->user()) {
            abort(404);
        }

        if (! FilamentAccess::userMayAccessPanel($request->user())) {
            if (config('filament-access.unauthorized_response') === 'redirect') {
                return redirect()->route('dashboard');
            }

            abort(404);
        }

        return $next($request);
    }
}
