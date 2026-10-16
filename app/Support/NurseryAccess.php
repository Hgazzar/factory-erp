<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Services\Tenant\TenantContext;

/**
 * صلاحيات موديول الحضانة — مالك المنشأة + صلاحيات تفصيلية على الموظف.
 *
 * القاعدة: الموظف يرى فقط ما مُنح صراحة في nursery_permissions.
 * login.app يفتح لوحة التحكم فقط ولا يفتح باقي الشاشات.
 */
final class NurseryAccess
{
    public const ROLE_RECEPTION = 'reception';

    public const ROLE_TEACHER = 'teacher';

    public const CAP_VIEW_DAILY = 'view_daily';

    public const CAP_VIEW_ATTENDANCE = 'view_attendance';

    public const CAP_VIEW_CHILDREN = 'view_children';

    public const CAP_VIEW_GUARDIANS = 'view_guardians';

    public const CAP_VIEW_CLASSROOMS = 'view_classrooms';

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

    public const CAP_VIEW_CHILD_ACTIVITY = 'view_child_activity';

    public const CAP_MANAGE_CHILD_ACTIVITY = 'manage_child_activity';

    public const CAP_VIEW_SUBSCRIPTIONS = 'view_subscriptions';

    public const CAP_MANAGE_SUBSCRIPTIONS = 'manage_subscriptions';

    /** ملخص مالية الحضانة (Stage B) — finance.view */
    public const CAP_VIEW_FINANCE = 'view_finance';

    /** لوحة المالية + P&L + ميزان + تقرير ضريبة */
    public const CAP_VIEW_FINANCE_REPORTS = 'view_finance_reports';

    /** مصروفات وتصنيفاتها (اعتماد/إنشاء) */
    public const CAP_MANAGE_FINANCE_EXPENSES = 'manage_finance_expenses';

    /** سندات تحصيل/صرف + بنوك */
    public const CAP_MANAGE_FINANCE_TREASURY = 'manage_finance_treasury';

    /** دليل حسابات + قيود + دفتر أستاذ */
    public const CAP_MANAGE_FINANCE_LEDGER = 'manage_finance_ledger';

    /** تسوية بنكية + معدلات ضريبة + ربط طرق الدفع */
    public const CAP_FINANCE_ADMIN = 'finance_admin';

    public const CAP_MANAGE_SETTINGS = 'manage_settings';

    /** @var array<int, Employee|null> */
    private array $employeeByUserId = [];

    /** @var array<string, bool> */
    private array $allowsCache = [];

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

        $employee = $this->resolveEmployee($user);
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
        $user ??= auth()->user();
        $userId = $user instanceof User ? (int) $user->id : 0;
        $cacheKey = $userId.'|'.$capability;

        if (array_key_exists($cacheKey, $this->allowsCache)) {
            return $this->allowsCache[$cacheKey];
        }

        return $this->allowsCache[$cacheKey] = $this->computeAllows($capability, $user);
    }

    private function computeAllows(string $capability, ?User $user): bool
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
            // لوحة التحكم: دخول التطبيق أو أي صلاحية تشغيلية أخرى
            self::CAP_VIEW_DAILY => $this->has($perms, 'login.app') || $perms !== [],

            self::CAP_VIEW_ATTENDANCE => $this->has($perms, 'attendance.children', 'attendance.staff'),

            self::CAP_VIEW_CHILDREN => $this->has(
                $perms,
                'children.manage',
                'children.files_page',
                'children.archive',
                'children.delete',
                'attendance.children',
            ),

            self::CAP_VIEW_GUARDIANS => $this->has($perms, 'children.parents_page', 'children.manage'),

            self::CAP_VIEW_CLASSROOMS => $this->has(
                $perms,
                'classrooms.manage',
                'classrooms.assign_child',
                'classrooms.archive',
                'attendance.children',
            ),

            self::CAP_VIEW_STAFF => $this->has($perms, 'employees.manage'),
            self::CAP_MANAGE_STAFF => $this->has($perms, 'employees.manage'),
            self::CAP_MANAGE_CHILDREN => $this->has($perms, 'children.manage'),
            self::CAP_MANAGE_CLASSROOMS => $this->has($perms, 'classrooms.manage'),
            self::CAP_VIEW_UNITS => $this->has($perms, 'units.manage', 'units.archive'),
            self::CAP_MANAGE_UNITS => $this->has($perms, 'units.manage'),
            self::CAP_VIEW_CALENDAR => $this->has($perms, 'scheduling.manage'),
            self::CAP_MANAGE_CALENDAR => $this->has($perms, 'scheduling.manage'),
            self::CAP_MANAGE_CHILD_ATTENDANCE => $this->has($perms, 'attendance.children'),
            self::CAP_VIEW_CHILD_ACTIVITY => $this->has(
                $perms,
                'attendance.children',
                'children.manage',
                'children.files_page',
            ),
            self::CAP_MANAGE_CHILD_ACTIVITY => $this->has($perms, 'attendance.children', 'children.manage'),
            self::CAP_MANAGE_STAFF_ATTENDANCE => $this->has($perms, 'attendance.staff'),
            self::CAP_VIEW_SUBSCRIPTIONS => $this->has($perms, 'subscriptions.manage'),
            self::CAP_MANAGE_SUBSCRIPTIONS => $this->has($perms, 'subscriptions.manage'),
            self::CAP_VIEW_FINANCE => $this->has($perms, 'finance.view', 'finance.admin'),
            self::CAP_VIEW_FINANCE_REPORTS => $this->has($perms, 'finance.view_reports', 'finance.admin'),
            self::CAP_MANAGE_FINANCE_EXPENSES => $this->has($perms, 'finance.manage_expenses', 'finance.admin'),
            self::CAP_MANAGE_FINANCE_TREASURY => $this->has($perms, 'finance.manage_treasury', 'finance.admin'),
            self::CAP_MANAGE_FINANCE_LEDGER => $this->has($perms, 'finance.manage_ledger', 'finance.admin'),
            self::CAP_FINANCE_ADMIN => $this->has($perms, 'finance.admin'),
            self::CAP_MANAGE_SETTINGS => $this->has($perms, 'settings.manage'),
            default => false,
        };
    }

    /** @param  list<string>  $perms */
    private function has(array $perms, string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (in_array($key, $perms, true)) {
                return true;
            }
        }

        return false;
    }

    private function resolveEmployee(?User $user): ?Employee
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $userId = (int) $user->id;
        if (array_key_exists($userId, $this->employeeByUserId)) {
            return $this->employeeByUserId[$userId];
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId === null) {
            return $this->employeeByUserId[$userId] = null;
        }

        return $this->employeeByUserId[$userId] = Employee::query()
            ->where('linked_user_id', $user->id)
            ->where('user_id', $tenantUserId)
            ->first();
    }
}
