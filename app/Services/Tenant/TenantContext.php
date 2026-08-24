<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Employee;
use App\Models\User;
use App\Support\ErpRoles;

/**
 * يحدّد «مالك المستأجر» (tenant owner) من المستخدم المصادَق.
 * حالياً: admin = المستأجر؛ الموظف/المشرف = مستأجر صاحب employer.user_id.
 */
final class TenantContext
{
    /** @var array<int, User|null> */
    private array $tenantByAuthUserId = [];

    public function resolveTenantUser(?User $user = null): ?User
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $authId = (int) $user->id;
        if (array_key_exists($authId, $this->tenantByAuthUserId)) {
            return $this->tenantByAuthUserId[$authId];
        }

        if ($this->isPlatformOperator($user)) {
            return $this->tenantByAuthUserId[$authId] = null;
        }

        if ($user->role === 'admin') {
            return $this->tenantByAuthUserId[$authId] = $user;
        }

        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->where('linked_user_id', $user->id)
            ->first();

        if ($employee === null) {
            return $this->tenantByAuthUserId[$authId] = null;
        }

        return $this->tenantByAuthUserId[$authId] = User::query()->find($employee->user_id);
    }

    public function resolveTenantUserId(?User $user = null): ?int
    {
        $tenant = $this->resolveTenantUser($user);

        return $tenant ? (int) $tenant->id : null;
    }

    /**
     * مشغّل المنصة (super_admin) — يتجاوز حارس الموديولات.
     */
    public function isPlatformOperator(?User $user = null): bool
    {
        return ErpRoles::isSuperAdmin($user ?? auth()->user());
    }
}
