<?php

declare(strict_types=1);

namespace App\Support;

/**
 * يتذكر آخر شاشة دخول استخدمها المستخدم (حضانة vs ERP العام)
 * حتى لا يُحوَّل لانتهاء الجلسة/الخروج إلى /login البيضاء.
 */
final class PreferredLoginShell
{
    public const COOKIE = 'akwad_preferred_login';

    public const NURSERY = 'nursery';

    public const TTL_MINUTES = 60 * 24 * 90;

    public static function rememberNursery(): void
    {
        cookie()->queue(cookie(self::COOKIE, self::NURSERY, self::TTL_MINUTES));
    }

    public static function forget(): void
    {
        cookie()->queue(cookie()->forget(self::COOKIE));
    }

    public static function isNursery(?string $value): bool
    {
        return $value === self::NURSERY;
    }
}
