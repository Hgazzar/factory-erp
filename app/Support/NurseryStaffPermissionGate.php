<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Services\Tenant\TenantContext;

/**
 * يحدد ما يمكن للمسؤول الحالي منحه أو تعديله لموظف آخر.
 */
final class NurseryStaffPermissionGate
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly NurseryAccess $nurseryAccess,
    ) {}

    /**
     * @return list<string>
     */
    public function grantableKeys(?User $editor = null): array
    {
        if ($this->nurseryAccess->isTenantOwner($editor)) {
            return NurseryPermissionCatalog::allKeys();
        }

        $employee = $this->resolveEditorEmployee($editor);

        if ($employee === null) {
            return [];
        }

        return NurseryPermissionCatalog::normalize($employee->nursery_permissions);
    }

    public function canGrant(?User $editor, string $permissionKey): bool
    {
        return in_array($permissionKey, $this->grantableKeys($editor), true);
    }

    /**
     * يقيّد الصلاحيات المُرسلة بما يملكه المحرر.
     *
     * @param  mixed  $requested
     * @return list<string>
     */
    public function filterGrantable(?User $editor, mixed $requested): array
    {
        $normalized = NurseryPermissionCatalog::normalize($requested);
        $allowed = $this->grantableKeys($editor);

        return array_values(array_intersect($normalized, $allowed));
    }

    public function employeeHas(Employee $employee, string $permissionKey): bool
    {
        return in_array($permissionKey, NurseryPermissionCatalog::normalize($employee->nursery_permissions), true);
    }

    private function resolveEditorEmployee(?User $editor): ?Employee
    {
        $editor ??= auth()->user();
        if (! $editor instanceof User) {
            return null;
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($editor);
        if ($tenantUserId === null) {
            return null;
        }

        return Employee::query()
            ->where('linked_user_id', $editor->id)
            ->where('user_id', $tenantUserId)
            ->first();
    }
}
