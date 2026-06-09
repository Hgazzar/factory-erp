<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FleetAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFleetOperationsEnabled
{
    public function __construct(
        private readonly FleetAccess $fleetAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->fleetAccess->operationsEnabled()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'العمليات الميدانية غير مفعّلة في باقتك.',
                'code' => 'fleet_ops_disabled',
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'العمليات الميدانية (المناديب) غير مفعّلة. فعّل موديول «المناديب» وميزة «العمليات الميدانية» من لوحة التحكم المركزية (Super Admin).');
    }
}
