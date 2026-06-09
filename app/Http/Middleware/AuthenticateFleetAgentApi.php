<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Fleet\FleetAgent;
use App\Services\Fleet\FleetAgentAuthService;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateFleetAgentApi
{
    public function __construct(
        private readonly FleetAgentAuthService $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = trim((string) $request->bearerToken());

        if ($plain === '') {
            return ApiResponse::error('مطلوب رمز Bearer صالح.', 401, 'unauthenticated');
        }

        $accessToken = PersonalAccessToken::findToken($plain);

        if ($accessToken === null || ! $accessToken->tokenable instanceof FleetAgent) {
            return ApiResponse::error('رمز API غير صالح.', 401, 'invalid_token');
        }

        /** @var FleetAgent $agent */
        $agent = $accessToken->tokenable;

        if (! $agent->isActive()) {
            return ApiResponse::error('حساب المندوب غير نشط.', 403, 'agent_inactive');
        }

        try {
            $this->auth->assertFleetEnabled((int) $agent->user_id);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 403, 'fleet_disabled');
        }

        if (! $accessToken->can('fleet:agent') && ! $accessToken->can('*')) {
            return ApiResponse::error('صلاحيات الرمز غير كافية.', 403, 'insufficient_ability');
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('fleet_agent', $agent);
        $request->attributes->set('fleet_agent_tenant_user_id', (int) $agent->user_id);

        return $next($request);
    }
}
