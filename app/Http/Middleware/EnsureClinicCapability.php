<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ClinicAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureClinicCapability
{
    public function __construct(
        private readonly ClinicAccess $clinicAccess,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if ($this->clinicAccess->allows($capability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية لهذا الإجراء في موديول العيادة.',
                'code' => 'clinic_forbidden',
            ], 403);
        }

        abort(403, 'ليس لديك صلاحية لهذا الإجراء في موديول العيادة.');
    }
}
