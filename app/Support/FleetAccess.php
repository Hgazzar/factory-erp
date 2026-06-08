<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantFeatureRegistry;

/**
 * صلاحيات موديول المناديب — مالك المنشأة + ميزة fleet_field_ops للنيشات غير المناديب.
 */
final class FleetAccess
{
    public const CAP_VIEW_DASHBOARD = 'view_dashboard';

    public const CAP_MANAGE_AGENTS = 'manage_agents';

    public const CAP_MANAGE_CUSTOMERS = 'manage_customers';

    public const CAP_MANAGE_PRODUCTS = 'manage_products';

    public const CAP_VIEW_ROUTES = 'view_routes';

    public const CAP_MANAGE_ROUTES = 'manage_routes';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantFeatureRegistry $features,
    ) {}

    public function isTenantOwner(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->role === 'admin' || ErpRoles::isSuperAdmin($user);
    }

    public function operationsEnabled(?int $tenantUserId = null): bool
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId === null || $tenantUserId < 1) {
            return false;
        }

        $nicheKey = strtolower(trim((string) (
            \App\Models\TenantProfile::forTenantUser($tenantUserId)?->niche_key ?? ''
        )));

        if ($nicheKey === 'fleet_agents') {
            return true;
        }

        return $this->features->isEnabled(PremiumFeatureKeys::FLEET_FIELD_OPS, $tenantUserId);
    }

    public function allows(string $capability, ?User $user = null): bool
    {
        if (! $this->operationsEnabled()) {
            return false;
        }

        if ($this->isTenantOwner($user)) {
            return true;
        }

        return match ($capability) {
            self::CAP_VIEW_DASHBOARD, self::CAP_VIEW_ROUTES => true,
            default => false,
        };
    }
}
