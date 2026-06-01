<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\SystemModule;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * سجل موديولات المستأجر — حارس البوابة (Phase 1).
 */
final class TenantModuleRegistry
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function isEnabled(string $moduleKey, ?int $tenantUserId = null): bool
    {
        $moduleKey = strtolower(trim($moduleKey));

        if ($moduleKey === 'core' || $this->isCoreModule($moduleKey)) {
            return true;
        }

        if (! array_key_exists($moduleKey, config('modules.modules', []))) {
            return false;
        }

        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId === null) {
            return false;
        }

        $enabledKeys = $this->enabledKeysForTenant($tenantUserId);

        return in_array($moduleKey, $enabledKeys, true);
    }

    /**
     * @return list<string>
     */
    public function enabledKeys(?int $tenantUserId = null): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId === null) {
            return ['core'];
        }

        return $this->enabledKeysForTenant($tenantUserId);
    }

    /**
     * @return list<string>
     */
    private function enabledKeysForTenant(int $tenantUserId): array
    {
        return Cache::remember(
            $this->cacheKey($tenantUserId),
            self::CACHE_TTL_SECONDS,
            function () use ($tenantUserId): array {
                $assigned = TenantModule::query()
                    ->where('tenant_user_id', $tenantUserId)
                    ->where('enabled', true)
                    ->whereHas('systemModule')
                    ->with('systemModule:id,key,is_core')
                    ->get()
                    ->pluck('systemModule.key')
                    ->filter()
                    ->values()
                    ->all();

                if ($assigned === []) {
                    return $this->legacyFullAccessKeys();
                }

                $keys = array_values(array_unique(array_merge(['core'], $assigned)));

                sort($keys);

                return $keys;
            }
        );
    }

    /**
     * مستأجر بدون سجل بعد = وصول كامل (توافق مع ERP الحالي حتى تُضبط الباقة).
     *
     * @return list<string>
     */
    private function legacyFullAccessKeys(): array
    {
        return array_values(array_unique(array_merge(
            ['core'],
            array_keys(config('modules.modules', []))
        )));
    }

    public function enableModule(int $tenantUserId, string $moduleKey, ?array $config = null): TenantModule
    {
        $systemModule = SystemModule::query()->where('key', $moduleKey)->firstOrFail();

        $record = TenantModule::query()->updateOrCreate(
            [
                'tenant_user_id' => $tenantUserId,
                'system_module_id' => $systemModule->id,
            ],
            [
                'enabled' => true,
                'enabled_at' => now(),
                'disabled_at' => null,
                'config' => $config,
            ]
        );

        $this->forgetCache($tenantUserId);

        return $record;
    }

    public function disableModule(int $tenantUserId, string $moduleKey): void
    {
        $systemModule = SystemModule::query()->where('key', $moduleKey)->firstOrFail();

        if ($systemModule->is_core) {
            return;
        }

        TenantModule::query()->updateOrCreate(
            [
                'tenant_user_id' => $tenantUserId,
                'system_module_id' => $systemModule->id,
            ],
            [
                'enabled' => false,
                'disabled_at' => now(),
            ]
        );

        $this->forgetCache($tenantUserId);
    }

    /**
     * @param  list<string>|null  $moduleKeys
     */
    public function syncModulesForTenant(int $tenantUserId, ?array $moduleKeys = null): void
    {
        $moduleKeys ??= array_keys(config('modules.modules', []));
        $moduleKeys = array_values(array_unique(array_merge(['core'], $moduleKeys)));

        $modules = SystemModule::query()
            ->whereIn('key', $moduleKeys)
            ->get()
            ->keyBy('key');

        foreach ($moduleKeys as $key) {
            $module = $modules->get($key);
            if ($module === null) {
                continue;
            }

            TenantModule::query()->updateOrCreate(
                [
                    'tenant_user_id' => $tenantUserId,
                    'system_module_id' => $module->id,
                ],
                [
                    'enabled' => true,
                    'enabled_at' => now(),
                    'disabled_at' => null,
                ]
            );
        }

        $this->forgetCache($tenantUserId);
    }

    /**
     * @return Collection<int, SystemModule>
     */
    public function catalog(): Collection
    {
        return SystemModule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * مزامنة الموديولات المفعّلة للمستأجر (يُعطّل غير المحدّد باستثناء core).
     *
     * @param  list<string>  $enabledModuleKeys
     */
    public function syncEnabledModuleKeys(int $tenantUserId, array $enabledModuleKeys): void
    {
        $enabledModuleKeys = array_values(array_unique(array_merge(
            ['core'],
            array_map('strtolower', $enabledModuleKeys)
        )));

        $modules = SystemModule::query()->orderBy('sort_order')->orderBy('id')->get();

        foreach ($modules as $module) {
            $key = strtolower((string) $module->key);

            if ($module->is_core) {
                $this->enableModule($tenantUserId, $key);

                continue;
            }

            if (in_array($key, $enabledModuleKeys, true)) {
                $this->enableModule($tenantUserId, $key);
            } else {
                $this->disableModule($tenantUserId, $key);
            }
        }
    }

    public function forgetCache(?int $tenantUserId = null): void
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();

        if ($tenantUserId !== null) {
            Cache::forget($this->cacheKey($tenantUserId));
        }
    }

    private function isCoreModule(string $moduleKey): bool
    {
        return (bool) (config("modules.modules.{$moduleKey}.is_core") ?? false);
    }

    private function cacheKey(int $tenantUserId): string
    {
        return "akwad.tenant_modules.{$tenantUserId}";
    }
}
