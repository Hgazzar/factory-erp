<?php

declare(strict_types=1);

namespace App\Support\Nursery;

use App\Models\Nursery\NurserySetting;
use App\Services\Tenant\TenantThemeService;

/**
 * @deprecated Prefer TenantThemeService — kept for backward compatibility in nursery code.
 */
final class NurseryThemePalette
{
    public const DEFAULT_PRIMARY = TenantThemeService::DEFAULT_PRIMARY;

    public const DEFAULT_SECONDARY = TenantThemeService::DEFAULT_SECONDARY;

    /**
     * @return array<string, string>
     */
    public static function cssVariables(?string $primary, ?string $secondary): array
    {
        return app(TenantThemeService::class)->cssVariables($primary, $secondary);
    }

    /**
     * @return array<string, string>
     */
    public static function forSetting(NurserySetting $settings): array
    {
        return app(TenantThemeService::class)->cssVariablesForTenant((int) $settings->user_id);
    }

    public static function normalizeHex(?string $hex): ?string
    {
        return TenantThemeService::normalizeHex($hex);
    }

    public static function validateHex(?string $hex, string $label): ?string
    {
        return TenantThemeService::validateHex($hex, $label);
    }
}
