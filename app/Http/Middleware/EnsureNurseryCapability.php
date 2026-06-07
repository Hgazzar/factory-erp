<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\NurseryAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureNurseryCapability
{
    public function __construct(
        private readonly NurseryAccess $nurseryAccess,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if ($this->nurseryAccess->allows($capability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية لهذا الإجراء في موديول الحضانة.',
                'code' => 'nursery_forbidden',
            ], 403);
        }

        abort(403, 'ليس لديك صلاحية لهذا الإجراء في موديول الحضانة.');
    }
}
