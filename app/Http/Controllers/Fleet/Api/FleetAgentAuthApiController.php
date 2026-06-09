<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet\Api;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use App\Services\Fleet\FleetAgentAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class FleetAgentAuthApiController extends Controller
{
    public function login(Request $request, FleetAgentAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'tenant_slug' => ['required', 'string', 'max:64'],
            'phone' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'string', 'min:4', 'max:8'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $token = $auth->login(
                $validated['tenant_slug'],
                $validated['phone'],
                $validated['pin'],
                $validated['device_name'] ?? 'mobile',
            );

            /** @var \App\Models\Fleet\FleetAgent $agent */
            $agent = $token->accessToken->tokenable;

            return ApiResponse::success([
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $token->accessToken->abilities ?? config('fleet.agent_api.token_abilities'),
                'tenant_user_id' => (int) $agent->user_id,
                'agent' => $auth->agentPayload($agent),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'login_failed');
        }
    }

    public function logout(Request $request, FleetAgentAuthService $auth): JsonResponse
    {
        /** @var \App\Models\Fleet\FleetAgent $agent */
        $agent = $request->attributes->get('fleet_agent');
        $auth->logout($agent);

        return ApiResponse::success(['message' => 'تم تسجيل الخروج.']);
    }
}
