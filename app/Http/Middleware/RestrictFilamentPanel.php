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
