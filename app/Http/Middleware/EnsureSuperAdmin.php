<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يقيّد الوصول بمشغّلي المنصة (super_admin) فقط.
 */
class EnsureSuperAdmin
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_super_admin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'غير مصرح — لوحة التحكم المركزية للمشغّلين فقط.',
                    'code' => 'super_admin_required',
                ], 403);
            }

            abort(403, 'غير مصرح — لوحة التحكم المركزية للمشغّلين فقط.');
        }

        return $next($request);
    }
}
