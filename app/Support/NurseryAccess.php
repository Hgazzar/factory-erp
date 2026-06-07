<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Services\Tenant\TenantContext;

/**
 * صلاحيات موديول الحضانة — مالك المنشأة + صلاحيات تفصيلية على الموظف.
 */
final class NurseryAccess
{
    public const ROLE_RECEPTION = 'reception';

    public const ROLE_TEACHER = 'teacher';

    public const CAP_VIEW_DAILY = 'view_daily';

    public const CAP_MANAGE_CHILDREN = 'manage_children';

    public const CAP_MANAGE_CLASSROOMS = 'manage_classrooms';

    public const CAP_MANAGE_STAFF = 'manage_staff';

    public const CAP_VIEW_STAFF = 'view_staff';

    public const CAP_VIEW_UNITS = 'view_units';

    public const CAP_MANAGE_UNITS = 'manage_units';

    public const CAP_VIEW_CALENDAR = 'view_calendar';

    public const CAP_MANAGE_CALENDAR = 'manage_calendar';

    public const CAP_MANAGE_CHILD_ATTENDANCE = 'manage_child_attendance';

    public const CAP_MANAGE_STAFF_ATTENDANCE = 'manage_staff_attendance';

    public const CAP_VIEW_SUBSCRIPTIONS = 'view_subscriptions';

    public const CAP_MANAGE_SUBSCRIPTIONS = 'manage_subscriptions';

    public const CAP_MANAGE_SETTINGS = 'manage_settings';

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function resolveNurseryRole(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->role === 'admin' || ErpRoles::isSuperAdmin($user)) {
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

        $role = $employee?->nursery_role;

        return in_array($role, [self::ROLE_RECEPTION, self::ROLE_TEACHER], true) ? $role : null;
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

        $employee = $this->resolveEmployee($user);

        if ($employee === null) {
            return false;
        }

        $perms = NurseryPermissionCatalog::normalize($employee->nursery_permissions);

        return match ($capability) {
            self::CAP_VIEW_DAILY => in_array('login.app', $perms, true)
                || in_array('attendance.children', $perms, true)
                || $employee->nursery_role !== null,
            self::CAP_VIEW_STAFF => in_array('employees.manage', $perms, true),
            self::CAP_MANAGE_STAFF => in_array('employees.manage', $perms, true),
            self::CAP_MANAGE_CHILDREN => in_array('children.manage', $perms, true)
                || $employee->nursery_role === self::ROLE_RECEPTION,
            self::CAP_MANAGE_CLASSROOMS => in_array('classrooms.manage', $perms, true),
            self::CAP_VIEW_UNITS => in_array('units.manage', $perms, true)
                || in_array('units.archive', $perms, true),
            self::CAP_MANAGE_UNITS => in_array('units.manage', $perms, true),
            self::CAP_VIEW_CALENDAR => in_array('scheduling.manage', $perms, true)
                || in_array('login.app', $perms, true),
            self::CAP_MANAGE_CALENDAR => in_array('scheduling.manage', $perms, true),
            self::CAP_MANAGE_CHILD_ATTENDANCE => in_array('attendance.children', $perms, true),
            self::CAP_MANAGE_STAFF_ATTENDANCE => in_array('attendance.staff', $perms, true),
            self::CAP_VIEW_SUBSCRIPTIONS => in_array('subscriptions.manage', $perms, true)
                || in_array('login.app', $perms, true),
            self::CAP_MANAGE_SUBSCRIPTIONS => in_array('subscriptions.manage', $perms, true),
            self::CAP_MANAGE_SETTINGS => in_array('settings.manage', $perms, true),
            default => false,
        };
    }

    private function resolveEmployee(?User $user): ?Employee
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId === null) {
            return null;
        }

        return Employee::query()
            ->where('linked_user_id', $user->id)
            ->where('user_id', $tenantUserId)
            ->first();
    }
}
