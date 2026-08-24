<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\CompanySetting;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class TenantBrandingService
{
    public const MODULE_NURSERY = TenantThemeService::MODULE_NURSERY;

    public const MODULE_CLINIC = TenantThemeService::MODULE_CLINIC;

    public const MODULE_STORE = TenantThemeService::MODULE_STORE;

    public const MODULE_FLEET = TenantThemeService::MODULE_FLEET;

    public const MODULE_TENANT = TenantThemeService::MODULE_TENANT;

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
    public function branding(
        int $tenantUserId,
        ?string $fallbackName = null,
        string $module = self::MODULE_TENANT,
    ): array {
        $setting = TenantSetting::query()->where('tenant_user_id', $tenantUserId)->first();
        $defaults = $this->themeService->defaultColorsForModule($module, $tenantUserId);
        $fallbackName = $this->resolveFallbackName($tenantUserId, $fallbackName);

        $display = trim((string) ($setting?->display_name ?? ''));
        $displayName = $display !== '' ? $display : $fallbackName;

        $themeVars = $this->themeService->cssVariablesForTenant($tenantUserId, $module);
        $prefix = $this->themeService->cssPrefixesForModule($module)[0] ?? 'tenant';

        return [
            'display_name' => $displayName,
            'logo_url' => $this->logoUrl($setting),
            'fallback_name' => $fallbackName,
            'theme_vars' => $themeVars,
            'theme_primary' => $themeVars["--{$prefix}-primary"] ?? $defaults['primary'],
            'theme_secondary' => $themeVars["--{$prefix}-secondary"] ?? $defaults['secondary'],
            'module' => $module,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranding(
        int $tenantUserId,
        array $data,
        string $module = self::MODULE_TENANT,
    ): TenantSetting {
        $setting = TenantSetting::forTenant($tenantUserId);
        [$primaryCol, $secondaryCol] = $this->themeService->moduleColorColumns($module);

        $displayName = trim((string) ($data['display_name'] ?? ''));
        $setting->display_name = $displayName !== '' ? $displayName : null;

        if (! empty($data['reset_theme_colors'])) {
            $setting->{$primaryCol} = null;
            $setting->{$secondaryCol} = null;
        } else {
            $setting->{$primaryCol} = TenantThemeService::validateHex(
                $data['theme_primary_color'] ?? null,
                'الأساسي'
            );
            $setting->{$secondaryCol} = TenantThemeService::validateHex(
                $data['theme_secondary_color'] ?? null,
                'الثانوي'
            );
        }

        $setting->save();

        $this->forgetBrandingCache($tenantUserId);

        return $setting->fresh();
    }

    public function updateLogo(int $tenantUserId, ?UploadedFile $file, bool $remove = false): TenantSetting
    {
        $setting = TenantSetting::forTenant($tenantUserId);

        if ($remove) {
            $previous = $setting->logo_path;
            $setting->forceFill([
                'logo_path' => null,
                'logo_mime' => null,
                'logo_data' => null,
            ])->save();
            $this->deleteStoredLogo($previous);
            $this->forgetBrandingCache($tenantUserId);

            return $setting->fresh();
        }

        if ($file === null) {
            return $setting;
        }

        $previous = $setting->logo_path;
        $dir = trim((string) config('tenant.branding.logo_directory', 'tenant'), '/');
        $relativeDir = $dir.'/'.(int) $tenantUserId;
        Storage::disk('public')->makeDirectory($relativeDir);
        $path = $file->store($relativeDir, 'public');
        if ($path === false || $path === '') {
            throw new \RuntimeException('فشل رفع الشعار إلى التخزين.');
        }

        $bytes = @file_get_contents($file->getRealPath() ?: $file->getPathname());
        if ($bytes === false || $bytes === '') {
            $bytes = Storage::disk('public')->get($path);
        }
        $mime = $file->getMimeType() ?: (string) (Storage::disk('public')->mimeType($path) ?: 'image/png');

        // Persist path + DB blob so redeploys without a Volume do not lose the logo.
        $setting->forceFill([
            'logo_path' => $path,
            'logo_mime' => $mime,
            'logo_data' => base64_encode($bytes),
        ])->save();

        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            $this->deleteStoredLogo($previous);
        }

        $this->forgetBrandingCache($tenantUserId);

        return $setting->fresh();
    }

    public function logoUrl(?TenantSetting $setting): ?string
    {
        if ($setting === null) {
            return null;
        }

        $hasPath = $this->isAllowedLogoPath($setting->logo_path);
        $hasBlob = is_string($setting->logo_data) && $setting->logo_data !== '';
        if (! $hasPath && ! $hasBlob) {
            return null;
        }

        $version = $setting->updated_at?->getTimestamp() ?? time();

        return route('tenant.branding.logo', ['tenantUserId' => (int) $setting->tenant_user_id]).'?v='.$version;
    }

    /**
     * @return array{bytes: string, mime: string}|null
     */
    public function resolveLogoBinary(TenantSetting $setting): ?array
    {
        $path = trim((string) ($setting->logo_path ?? ''));
        if ($this->isAllowedLogoPath($path) && Storage::disk('public')->exists($path)) {
            $bytes = Storage::disk('public')->get($path);
            if (is_string($bytes) && $bytes !== '') {
                $mime = trim((string) ($setting->logo_mime ?? '')) ?: (string) (Storage::disk('public')->mimeType($path) ?: 'image/png');

                return ['bytes' => $bytes, 'mime' => $mime];
            }
        }

        $encoded = trim((string) ($setting->logo_data ?? ''));
        if ($encoded === '') {
            return null;
        }

        $bytes = base64_decode($encoded, true);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $mime = trim((string) ($setting->logo_mime ?? ''));
        if ($mime === '') {
            $mime = 'image/png';
        }

        // Best-effort restore to disk for nginx/static consumers.
        if ($this->isAllowedLogoPath($path)) {
            $this->writeLogoToDisk($path, $bytes);
        }

        return ['bytes' => $bytes, 'mime' => $mime];
    }

    /**
     * Re-materialize DB-backed logos onto the local public disk (after redeploy).
     */
    public function restoreLogosToDisk(): int
    {
        $restored = 0;
        TenantSetting::query()
            ->whereNotNull('logo_data')
            ->where('logo_data', '!=', '')
            ->orderBy('id')
            ->chunkById(50, function ($rows) use (&$restored): void {
                foreach ($rows as $setting) {
                    /** @var TenantSetting $setting */
                    $path = trim((string) ($setting->logo_path ?? ''));
                    if (! $this->isAllowedLogoPath($path)) {
                        continue;
                    }
                    if (Storage::disk('public')->exists($path)) {
                        continue;
                    }

                    $bytes = base64_decode((string) $setting->logo_data, true);
                    if ($bytes === false || $bytes === '') {
                        continue;
                    }

                    if ($this->writeLogoToDisk($path, $bytes)) {
                        $restored++;
                    }
                }
            });

        return $restored;
    }

    private function writeLogoToDisk(string $path, string $bytes): bool
    {
        try {
            $dir = dirname($path);
            if ($dir !== '' && $dir !== '.') {
                Storage::disk('public')->makeDirectory($dir);
            }

            return Storage::disk('public')->put($path, $bytes);
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAllowedLogoPath(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        $allowed = config('tenant.branding.legacy_logo_prefixes', ['tenant/', 'nursery/']);
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function deleteStoredLogo(?string $path): void
    {
        if (! $this->isAllowedLogoPath($path)) {
            return;
        }

        $path = trim((string) $path);
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

    private function forgetBrandingCache(int $tenantUserId): void
    {
        foreach ([
            self::MODULE_NURSERY,
            self::MODULE_CLINIC,
            self::MODULE_STORE,
            self::MODULE_FLEET,
            self::MODULE_TENANT,
        ] as $module) {
            Cache::forget('tenant.branding.'.$module.'.'.$tenantUserId);
        }
    }
}
