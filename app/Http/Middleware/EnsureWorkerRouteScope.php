<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يقيّد مستخدمي دور worker على مسارات التشغيل (POS + تسجيل الإنتاج) فقط.
 */
final class EnsureWorkerRouteScope
{
    /** @var list<string> */
    private const ALLOWED_ROUTE_PREFIXES = [
        'pos.',
        'operations.production-entry',
    ];

    /** @var list<string> */
    private const ALLOWED_ROUTE_NAMES = [
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->role !== 'worker') {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if ($routeName !== '' && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        foreach (self::ALLOWED_ROUTE_PREFIXES as $prefix) {
            if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'هذا القسم غير متاح لحساب العامل. يمكنك استخدام نقاط البيع أو تسجيل الإنتاج فقط.');
    }
}
