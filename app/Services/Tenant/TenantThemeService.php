<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\TenantProfile;
use App\Models\TenantSetting;
use InvalidArgumentException;

/**
 * ألوان هوية المستأجر — متغيرات CSS مع تباين آمن لكل وحدة (حضانة / عيادة / متجر).
 */
final class TenantThemeService
{
    public const MODULE_NURSERY = 'nursery';

    public const MODULE_CLINIC = 'clinic';

    public const MODULE_STORE = 'store';

    public const MODULE_FLEET = 'fleet';

    public const MODULE_TENANT = 'tenant';

    public const DEFAULT_PRIMARY = '#f97316';

    public const DEFAULT_SECONDARY = '#ffedd5';

    public function __construct(
        private readonly NicheCatalog $nicheCatalog,
    ) {}

    /**
     * @return array{primary: string, secondary: string}
     */
    public function defaultColorsForTenant(int $tenantUserId): array
    {
        return $this->defaultColorsForModule(self::MODULE_TENANT, $tenantUserId);
    }

    /**
     * @return array{primary: string, secondary: string}
     */
    public function defaultColorsForModule(string $module, int $tenantUserId): array
    {
        $nicheKey = strtolower(trim((string) (TenantProfile::forTenantUser($tenantUserId)?->niche_key ?? '')));
        $defaults = config('tenant.branding.defaults', []);

        $configKey = match ($module) {
            self::MODULE_NURSERY => 'nurseries',
            self::MODULE_CLINIC => 'medical_clinics',
            self::MODULE_STORE => in_array($nicheKey, ['retail', 'manufacturing', 'fleet_agents', 'full_erp'], true)
                ? $nicheKey
                : 'retail',
            self::MODULE_FLEET => 'fleet_agents',
            default => is_string($nicheKey) && $nicheKey !== '' ? $nicheKey : '_default',
        };

        if (! isset($defaults[$configKey])) {
            $configKey = is_string($nicheKey) && isset($defaults[$nicheKey]) ? $nicheKey : '_default';
        }

        $pair = $defaults[$configKey] ?? ($defaults['_default'] ?? ['primary' => self::DEFAULT_PRIMARY, 'secondary' => self::DEFAULT_SECONDARY]);

        return [
            'primary' => (string) ($pair['primary'] ?? self::DEFAULT_PRIMARY),
            'secondary' => (string) ($pair['secondary'] ?? self::DEFAULT_SECONDARY),
        ];
    }

    /**
     * @return list<string>
     */
    public function cssPrefixesForModule(string $module): array
    {
        return match ($module) {
            self::MODULE_NURSERY => ['nursery', 'np'],
            self::MODULE_CLINIC => ['clinic', 'cp'],
            self::MODULE_STORE => ['store', 'sp'],
            self::MODULE_FLEET => ['fleet', 'fp'],
            default => ['tenant'],
        };
    }

    /**
     * @return array<string, string> CSS custom properties
     */
    public function cssVariablesForTenant(int $tenantUserId, string $module = self::MODULE_TENANT): array
    {
        $setting = TenantSetting::query()->where('tenant_user_id', $tenantUserId)->first();
        $defaults = $this->defaultColorsForModule($module, $tenantUserId);
        [$primaryCol, $secondaryCol] = $this->moduleColorColumns($module);

        $storedPrimary = $setting?->{$primaryCol};
        $storedSecondary = $setting?->{$secondaryCol};
        if ($module === self::MODULE_TENANT) {
            $storedPrimary ??= $setting?->theme_primary_color;
            $storedSecondary ??= $setting?->theme_secondary_color;
        }

        return $this->cssVariables(
            $storedPrimary,
            $storedSecondary,
            $defaults['primary'],
            $defaults['secondary'],
            $this->cssPrefixesForModule($module),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function moduleColorColumns(string $module): array
    {
        return match ($module) {
            self::MODULE_NURSERY => ['nursery_theme_primary_color', 'nursery_theme_secondary_color'],
            self::MODULE_CLINIC => ['clinic_theme_primary_color', 'clinic_theme_secondary_color'],
            self::MODULE_STORE => ['store_theme_primary_color', 'store_theme_secondary_color'],
            self::MODULE_FLEET => ['fleet_theme_primary_color', 'fleet_theme_secondary_color'],
            default => ['theme_primary_color', 'theme_secondary_color'],
        };
    }

    /**
     * @param  list<string>  $prefixes
     * @return array<string, string>
     */
    public function cssVariables(
        ?string $primary,
        ?string $secondary,
        ?string $defaultPrimary = null,
        ?string $defaultSecondary = null,
        array $prefixes = ['tenant'],
    ): array {
        $primary = self::normalizeHex($primary) ?? $defaultPrimary ?? self::DEFAULT_PRIMARY;
        $secondary = self::normalizeHex($secondary) ?? $defaultSecondary ?? self::DEFAULT_SECONDARY;

        $primaryDark = self::darken($primary, 0.14);
        $primaryRgb = self::hexToRgb($primary);
        $text = self::readableTextColor($primary);
        $textMuted = self::darken($primary, 0.42);
        $onPrimary = self::contrastTextOn($primary);
        $bg = self::mixHex($primary, '#ffffff', 0.97);
        $bgMid = self::mixHex($primary, '#ffffff', 0.94);
        $border = self::mixHex($primary, '#ffffff', 0.72);
        $cardTint = self::mixHex($primary, '#ffffff', 0.98);
        $focusRing = sprintf('rgba(%d,%d,%d,0.28)', ...$primaryRgb);
        $shadow = sprintf('rgba(%d,%d,%d,0.10)', ...$primaryRgb);

        $base = [
            'primary' => $primary,
            'primary-dark' => $primaryDark,
            'secondary' => $secondary,
            'bg' => $bg,
            'bg-mid' => $bgMid,
            'border' => $border,
            'card-tint' => $cardTint,
            'text' => $text,
            'text-muted' => $textMuted,
            'on-primary' => $onPrimary,
            'shadow' => $shadow,
            'focus-ring' => $focusRing,
            'card' => '#ffffff',
        ];

        $vars = [];
        foreach ($prefixes as $prefix) {
            foreach ($base as $key => $value) {
                $vars["--{$prefix}-{$key}"] = $value;
            }
        }

        return $vars;
    }

    public static function normalizeHex(?string $hex): ?string
    {
        $hex = trim((string) $hex);
        if ($hex === '') {
            return null;
        }
        if (! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }

        return strtolower($hex);
    }

    public static function validateHex(?string $hex, string $label): ?string
    {
        if ($hex === null || trim($hex) === '') {
            return null;
        }

        $normalized = self::normalizeHex($hex);
        if ($normalized === null) {
            throw new InvalidArgumentException("لون {$label} غير صالح — استخدم صيغة #RRGGBB.");
        }

        return $normalized;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function darken(string $hex, float $amount): string
    {
        return self::mixHex($hex, '#000000', max(0.0, min(1.0, $amount)));
    }

    private static function mixHex(string $color, string $with, float $weight): string
    {
        $weight = max(0.0, min(1.0, $weight));
        $a = self::hexToRgb($color);
        $b = self::hexToRgb($with);
        $r = (int) round($a[0] * (1 - $weight) + $b[0] * $weight);
        $g = (int) round($a[1] * (1 - $weight) + $b[1] * $weight);
        $bl = (int) round($a[2] * (1 - $weight) + $b[2] * $weight);

        return sprintf('#%02x%02x%02x', $r, $g, $bl);
    }

    private static function readableTextColor(string $primary): string
    {
        $luminance = self::relativeLuminance($primary);

        return $luminance > 0.55
            ? self::darken($primary, 0.72)
            : self::darken($primary, 0.48);
    }

    private static function contrastTextOn(string $background): string
    {
        return self::relativeLuminance($background) > 0.45 ? '#1c1917' : '#ffffff';
    }

    private static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $channels = [$r / 255, $g / 255, $b / 255];
        $linear = array_map(static function (float $c): float {
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }
}
