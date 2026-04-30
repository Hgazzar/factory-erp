<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;

/**
 * تمييز أدوار المحاسبة: أدمن عادي مقابل سوبر أدمن (دور أو إيميل محدد).
 */
final class ErpRoles
{
    public const SUPER_ADMIN_EMAIL = 'admin@admin.com';

    /**
     * سوبر أدمن: دور super_admin أو الإيميل المعتمد للطوارئ.
     */
    public static function isSuperAdmin(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return strtolower(trim((string) $user->email)) === self::SUPER_ADMIN_EMAIL;
    }

    /**
     * أدمن عادي (يمكنه إرجاع مصروف معتمد للمسودة دون حذف نهائي).
     */
    public static function isStandardAdmin(?Authenticatable $user): bool
    {
        return $user instanceof User
            && $user->role === 'admin'
            && ! self::isSuperAdmin($user);
    }

    /**
     * حذف مسودة مصروف: أدمن أو سوبر أدمن.
     */
    public static function canDeleteExpenseDraft(?Authenticatable $user): bool
    {
        return $user instanceof User
            && ($user->role === 'admin' || self::isSuperAdmin($user));
    }

    /**
     * نفس منطق middleware role:admin: يمرّ الأدمن أو سوبر الأدمن الصريح (لروابط الواجهة مثل سجل التدقيق).
     */
    public static function hasFinanceAdminPanelAccess(?Authenticatable $user): bool
    {
        return $user instanceof User
            && \in_array($user->role, ['admin', 'super_admin'], true);
    }

    /**
     * حذف نهائي لمصروف معتمد: سوبر أدمن فقط.
     */
    public static function canHardDeleteApprovedExpense(?Authenticatable $user): bool
    {
        return self::isSuperAdmin($user);
    }

    /**
     * مسح جميع المصروفات المطابقة للفلاتر من الشاشة: سوبر أدمن فقط (قد يشمل معتمداً وقيداً).
     */
    public static function canBulkDeleteAllExpensesMatchingFilters(?Authenticatable $user): bool
    {
        return self::isSuperAdmin($user);
    }

    /**
     * إرجاع مصروف معتمد إلى مسودة: الأدمن العادي فقط (لا السوبر أدمن حسب السياسة).
     */
    public static function canRevertApprovedExpenseToDraft(?Authenticatable $user): bool
    {
        return self::isStandardAdmin($user);
    }

    /**
     * نفس صلاحيات سوبر الأدمن للصيانة الشاملة (مالك النظام التقليدي id=1 أو سوبر أدمن).
     */
    public static function canRunSystemFinancialMaintenance(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ((int) $user->id === 1) {
            return true;
        }

        return self::isSuperAdmin($user);
    }
}
