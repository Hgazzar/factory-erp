<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // If user is admin, allow everything
        if ($user->role === 'admin') {
            return $next($request);
        }

        // If middleware expects supervisor and user is supervisor, allow
        if ($role === 'supervisor' && $user->role === 'supervisor') {
            return $next($request);
        }

        // If middleware expects worker and user is worker, allow
        if ($role === 'worker' && $user->role === 'worker') {
            return $next($request);
        }

        abort(403);
    }
}

