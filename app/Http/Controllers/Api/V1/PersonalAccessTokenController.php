<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssuePersonalAccessTokenRequest;
use App\Models\User;
use App\Services\Api\TenantApiTokenService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * إصدار Personal Access Tokens للمستأجرين (Sanctum).
 */
final class PersonalAccessTokenController extends Controller
{
    public function store(IssuePersonalAccessTokenRequest $request, TenantApiTokenService $tokenService): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return ApiResponse::error('بيانات الدخول غير صحيحة.', 401, 'invalid_credentials');
        }

        try {
            $tenantUserId = $tokenService->resolveTenantUserIdForToken($user);
            $token = $tokenService->createToken($user, $request->string('device_name')->toString());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403, 'tenant_unresolved');
        }

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $token->accessToken->abilities ?? TenantApiTokenService::DEFAULT_ABILITIES,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'tenant_user_id' => $tenantUserId,
        ], 201);
    }

    public function index(Request $request, TenantApiTokenService $tokenService): JsonResponse
    {
        return ApiResponse::success([
            'tokens' => $tokenService->listTokens($request->user()),
        ]);
    }

    public function destroy(Request $request, int $tokenId, TenantApiTokenService $tokenService): JsonResponse
    {
        if (! $tokenService->revokeToken($request->user(), $tokenId)) {
            return ApiResponse::error('التوكن غير موجود.', 404, 'token_not_found');
        }

        return ApiResponse::success(['revoked' => true]);
    }
}
