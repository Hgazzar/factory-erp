<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;
use RuntimeException;

/**
 * إدارة Personal Access Tokens للمستأجر — Web + API.
 */
final class TenantApiTokenService
{
    /** @var list<string> */
    public const DEFAULT_ABILITIES = ['inventory:read', 'inventory:write'];

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function resolveTenantUserIdForToken(User $user): int
    {
        if ($this->tenantContext->isPlatformOperator($user)) {
            throw new RuntimeException('مشغّل المنصة لا يمكنه إصدار توكن مستأجر. استخدم حساب admin للمستأجر.');
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId === null) {
            throw new RuntimeException('لا يمكن تحديد المستأجر لهذا الحساب.');
        }

        return $tenantUserId;
    }

    public function createToken(User $user, string $deviceName): NewAccessToken
    {
        $this->resolveTenantUserIdForToken($user);

        return $user->createToken($deviceName, self::DEFAULT_ABILITIES);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTokens(User $user): array
    {
        return $user->tokens()
            ->orderByDesc('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at', 'expires_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'last_used_at_label' => $token->last_used_at?->translatedFormat('d M Y H:i'),
                'created_at' => $token->created_at,
                'created_at_label' => $token->created_at?->translatedFormat('d M Y H:i'),
                'expires_at' => $token->expires_at,
            ])
            ->values()
            ->all();
    }

    public function revokeToken(User $user, int $tokenId): bool
    {
        return $user->tokens()->whereKey($tokenId)->delete() > 0;
    }

    public function canManageTokens(User $user): bool
    {
        if ($this->tenantContext->isPlatformOperator($user)) {
            return false;
        }

        return $this->tenantContext->resolveTenantUserId($user) !== null;
    }
}
