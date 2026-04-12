<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTechnicianOrAdmin
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_technician && $user->role !== 'admin')) {
            abort(403, 'هذه الصفحة مخصصة للفنيين.');
        }

        return $next($request);
    }
}
