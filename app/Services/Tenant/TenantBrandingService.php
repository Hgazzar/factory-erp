<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\CompanySetting;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class TenantBrandingService
{
    public function __construct(
        private readonly TenantThemeService $themeService,
    ) {}

    public function ensureForTenant(int $tenantUserId): TenantSetting
    {
        return TenantSetting::forTenant($tenantUserId);
    }

    /**
     * @return array{
     *     display_name: string,
     *     logo_url: string|null,
     *     fallback_name: string,
     *     theme_vars: array<string, string>,
     *     theme_primary: string,
     *     theme_secondary: string
     * }
     */
    public function branding(int $tenantUserId, ?string $fallbackName = null): array
    {
        $setting = TenantSetting::query()->where('tenant_user_id', $tenantUserId)->first();
        $defaults = $this->themeService->defaultColorsForTenant($tenantUserId);
        $fallbackName = $this->resolveFallbackName($tenantUserId, $fallbackName);

        $display = trim((string) ($setting?->display_name ?? ''));
        $displayName = $display !== '' ? $display : $fallbackName;

        $primary = TenantThemeService::normalizeHex($setting?->theme_primary_color) ?? $defaults['primary'];
        $secondary = TenantThemeService::normalizeHex($setting?->theme_secondary_color) ?? $defaults['secondary'];

        return [
            'display_name' => $displayName,
            'logo_url' => $this->logoUrl($setting),
            'fallback_name' => $fallbackName,
            'theme_vars' => $this->themeService->cssVariables(
                $setting?->theme_primary_color,
                $setting?->theme_secondary_color,
                $defaults['primary'],
                $defaults['secondary'],
            ),
            'theme_primary' => $primary,
            'theme_secondary' => $secondary,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranding(int $tenantUserId, array $data): TenantSetting
    {
        $setting = TenantSetting::forTenant($tenantUserId);

        $displayName = trim((string) ($data['display_name'] ?? ''));
        $setting->display_name = $displayName !== '' ? $displayName : null;

        if (! empty($data['reset_theme_colors'])) {
            $setting->theme_primary_color = null;
            $setting->theme_secondary_color = null;
        } else {
            $setting->theme_primary_color = TenantThemeService::validateHex(
                $data['theme_primary_color'] ?? null,
                'الأساسي'
            );
            $setting->theme_secondary_color = TenantThemeService::validateHex(
                $data['theme_secondary_color'] ?? null,
                'الثانوي'
            );
        }

        $setting->save();

        return $setting->fresh();
    }

    public function updateLogo(int $tenantUserId, ?UploadedFile $file, bool $remove = false): TenantSetting
    {
        $setting = TenantSetting::forTenant($tenantUserId);

        if ($remove) {
            $this->deleteStoredLogo($setting->logo_path);
            $setting->forceFill(['logo_path' => null])->save();

            return $setting->fresh();
        }

        if ($file === null) {
            return $setting;
        }

        $this->deleteStoredLogo($setting->logo_path);
        $dir = trim((string) config('tenant.branding.logo_directory', 'tenant'), '/');
        $path = $file->store($dir.'/'.(int) $tenantUserId, 'public');
        $setting->forceFill(['logo_path' => $path])->save();

        return $setting->fresh();
    }

    public function logoUrl(?TenantSetting $setting): ?string
    {
        $path = trim((string) ($setting?->logo_path ?? ''));
        if ($path === '') {
            return null;
        }

        $allowed = config('tenant.branding.legacy_logo_prefixes', ['tenant/', 'nursery/']);
        $ok = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, (string) $prefix)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private function deleteStoredLogo(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        $allowed = config('tenant.branding.legacy_logo_prefixes', ['tenant/', 'nursery/']);
        $ok = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, (string) $prefix)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function resolveFallbackName(int $tenantUserId, ?string $fallbackName): string
    {
        $fallbackName = trim((string) $fallbackName);
        if ($fallbackName !== '') {
            return $fallbackName;
        }

        $company = CompanySetting::query()->where('user_id', $tenantUserId)->value('name');
        if (is_string($company) && trim($company) !== '') {
            return trim($company);
        }

        return (string) (User::query()->whereKey($tenantUserId)->value('name') ?? config('app.name'));
    }
}
