<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class FilamentAccess
{
    /**
     * @return list<int>
     */
    public static function allowedUserIds(): array
    {
        return config('filament-access.allowed_user_ids', []);
    }

    /**
     * هل تم ضبط قائمة IDs صريحة (اختيارية — الأدمن وسوبر الأدمن يمرّون عبر userMayAccessPanel حتى بدونها).
     */
    public static function panelIsConfigured(): bool
    {
        return self::allowedUserIds() !== [];
    }

    public static function userMayAccessPanel(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        // نفس امتياز لوحة ERP (admin | super_admin)
        if (ErpRoles::hasFinanceAdminPanelAccess($user)) {
            return true;
        }

        $ids = self::allowedUserIds();

        return $ids !== [] && in_array((int) $user->id, $ids, true);
    }
}
