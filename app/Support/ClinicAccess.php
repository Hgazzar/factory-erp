<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Services\Tenant\TenantContext;

/**
 * صلاحيات موديول العيادة — استقبال vs طبيب vs مالك المنشأة.
 */
final class ClinicAccess
{
    public const ROLE_RECEPTIONIST = 'receptionist';

    public const ROLE_DOCTOR = 'doctor';

    public const CAP_VIEW_APPOINTMENTS = 'view_appointments';

    public const CAP_COLLECT_PAYMENT = 'collect_payment';

    public const CAP_VIEW_CLINICAL = 'view_clinical';

    public const CAP_MANAGE_CLINICAL = 'manage_clinical';

    public const CAP_MANAGE_SERVICES = 'manage_services';

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function resolveClinicRole(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->role === 'admin' || ErpRoles::isSuperAdmin($user)) {
            return null;
        }

        if (! $user->id) {
            return null;
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId === null) {
            return null;
        }

        $employee = Employee::query()
            ->where('linked_user_id', $user->id)
            ->where('user_id', $tenantUserId)
            ->first();

        $role = $employee?->clinic_role;

        return in_array($role, [self::ROLE_RECEPTIONIST, self::ROLE_DOCTOR], true) ? $role : null;
    }

    public function isTenantOwner(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->role === 'admin' || ErpRoles::isSuperAdmin($user);
    }

    public function allows(string $capability, ?User $user = null): bool
    {
        if ($this->isTenantOwner($user)) {
            return true;
        }

        $role = $this->resolveClinicRole($user);

        if ($role === null) {
            return false;
        }

        return match ($capability) {
            self::CAP_VIEW_APPOINTMENTS => true,
            self::CAP_COLLECT_PAYMENT => $role === self::ROLE_RECEPTIONIST,
            self::CAP_VIEW_CLINICAL, self::CAP_MANAGE_CLINICAL => $role === self::ROLE_DOCTOR,
            self::CAP_MANAGE_SERVICES => false,
            default => false,
        };
    }

    public function denies(string $capability, ?User $user = null): bool
    {
        return ! $this->allows($capability, $user);
    }
}
