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

    public static function panelIsConfigured(): bool
    {
        return self::allowedUserIds() !== [];
    }

    public static function userMayAccessPanel(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return in_array((int) $user->id, self::allowedUserIds(), true);
    }
}
