<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FleetAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFleetCapability
{
    public function __construct(
        private readonly FleetAccess $fleetAccess,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if ($this->fleetAccess->allows($capability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية لهذا الإجراء في موديول المناديب.',
                'code' => 'fleet_forbidden',
            ], 403);
        }

        abort(403, 'ليس لديك صلاحية لهذا الإجراء في موديول المناديب.');
    }
}
