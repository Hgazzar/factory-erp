<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\SystemModule;
use App\Models\User;
use App\Models\TenantProfile;
use App\Services\Tenant\NicheCatalog;
use App\Services\Tenant\PremiumFeatureCatalog;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\TenantSlug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * إدارة المستأجرين من لوحة التحكم المركزية.
 */
final class SuperAdminTenantService
{
    public function __construct(
        private readonly TenantModuleRegistry $moduleRegistry,
        private readonly NicheCatalog $nicheCatalog,
        private readonly PremiumFeatureCatalog $premiumCatalog,
        private readonly TenantFeatureRegistry $featureRegistry,
    ) {}

    public function paginateTenants(int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'admin')
            ->with(['companySetting:id,user_id,name', 'tenantProfile:id,tenant_user_id,niche_key,domain,slug,status'])
            ->withCount('employees')
            ->orderByDesc('created_at')
            ->paginate(max(1, min(50, $perPage)))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function tenantSummary(User $tenant): array
    {
        $this->assertTenantAdmin($tenant);

        $companyName = $tenant->companySetting?->name ?: $tenant->name;
        $enabledKeys = $this->moduleRegistry->enabledKeys((int) $tenant->id);
        $hasExplicitRegistry = $tenant->tenantModules()->exists();
        $profile = $tenant->relationLoaded('tenantProfile') ? $tenant->tenantProfile : $tenant->tenantProfile;
        $nicheKey = $profile?->niche_key;
        $niche = $nicheKey !== null ? $this->nicheCatalog->find($nicheKey) : null;

        return [
            'id' => $tenant->id,
            'name' => $companyName,
            'owner_name' => $tenant->name,
            'email' => $tenant->email,
            'domain' => $profile?->domain,
            'slug' => $profile?->slug ?? $profile?->domain,
            'store_url' => ($profile?->slug ?? $profile?->domain)
                ? url('/s/'.($profile->slug ?? $profile->domain))
                : null,
            'niche_key' => $nicheKey,
            'niche_name' => $niche['name_ar'] ?? null,
            'employee_count' => (int) ($tenant->employees_count ?? $tenant->employees()->count()),
            'subscribed_at' => $tenant->created_at?->format('Y-m-d'),
            'subscribed_at_label' => $tenant->created_at?->translatedFormat('d M Y'),
            'enabled_modules' => $enabledKeys,
            'enabled_modules_count' => count($enabledKeys),
            'has_explicit_module_registry' => $hasExplicitRegistry,
        ];
    }

    /**
     * @return Collection<int, SystemModule>
     */
    public function moduleCatalog(): Collection
    {
        return $this->moduleRegistry->catalog();
    }

    /**
     * @return list<array{value: string, label: string, description?: string}>
     */
    public function nicheSelectOptions(): array
    {
        return $this->nicheCatalog->selectOptions();
    }

    public function platformStats(): array
    {
        $tenantQuery = User::query()->where('role', 'admin');

        return [
            'tenants_total' => (clone $tenantQuery)->count(),
            'tenants_with_profile' => (clone $tenantQuery)->whereHas('tenantProfile')->count(),
            'niches_available' => count($this->nicheCatalog->keys()),
        ];
    }

    /**
     * @return list<string>
     */
    public function enabledModuleKeysForTenant(User $tenant): array
    {
        $this->assertTenantAdmin($tenant);

        return $this->moduleRegistry->enabledKeys((int) $tenant->id);
    }

    /**
     * @param  list<string>  $moduleKeys
     */
    public function syncTenantModules(User $tenant, array $moduleKeys): void
    {
        $this->assertTenantAdmin($tenant);

        $this->moduleRegistry->syncEnabledModuleKeys((int) $tenant->id, $moduleKeys);
    }

    public function assertTenantAdmin(User $tenant): void
    {
        if ($tenant->role !== 'admin') {
            abort(404);
        }
    }

    public function updateTenantSlug(User $tenant, string $slug): TenantProfile
    {
        $this->assertTenantAdmin($tenant);

        $slug = TenantSlug::normalize($slug);

        if (! TenantSlug::isAvailable($slug, (int) $tenant->id)) {
            throw new \InvalidArgumentException('هذا الـ slug مستخدم بالفعل لمستأجر آخر.');
        }

        $profile = $tenant->tenantProfile ?? TenantProfile::query()->firstOrCreate(
            ['tenant_user_id' => $tenant->id],
            ['niche_key' => 'full_erp', 'status' => TenantProfile::STATUS_ACTIVE],
        );

        $profile->slug = $slug;
        $profile->domain = $slug;
        $profile->save();

        return $profile;
    }

    /**
     * بيانات لوحة المزايا البريميوم (مودال السوبر أدمن).
     *
     * @return array<string, mixed>
     */
    public function premiumFeaturePanelData(User $tenant): array
    {
        $this->assertTenantAdmin($tenant);

        $tenant->loadMissing(['tenantProfile', 'companySetting']);
        $nicheKey = $tenant->tenantProfile?->niche_key;
        $niche = $nicheKey !== null ? $this->nicheCatalog->find($nicheKey) : null;
        $definitions = $this->premiumCatalog->definitionsForNiche($nicheKey);
        $catalogKeys = $this->premiumCatalog->keysForNiche($nicheKey);
        $enabledAll = $this->featureRegistry->enabledKeysForTenant((int) $tenant->id);
        $enabledModules = $this->moduleRegistry->enabledKeys((int) $tenant->id);

        $features = array_map(function (array $def) use ($enabledAll, $enabledModules): array {
            $key = strtolower((string) $def['key']);
            $requiresModule = isset($def['requires_module'])
                ? strtolower((string) $def['requires_module'])
                : null;
            $moduleEnabled = $requiresModule === null
                || in_array($requiresModule, $enabledModules, true);

            return [
                'key' => $key,
                'name_ar' => (string) $def['name_ar'],
                'description_ar' => (string) ($def['description_ar'] ?? ''),
                'hint' => (string) ($def['hint'] ?? ''),
                'group' => isset($def['group']) ? (string) $def['group'] : null,
                'enabled' => in_array($key, $enabledAll, true),
                'requires_module' => $requiresModule,
                'module_enabled' => $moduleEnabled,
                'locked' => ! $moduleEnabled,
                'locked_reason' => $moduleEnabled || $requiresModule === null
                    ? null
                    : 'فعّل موديول «'.(config("modules.modules.{$requiresModule}.name_ar") ?? $requiresModule).'» من قسم الموديولات أولاً.',
            ];
        }, $definitions);

        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->companySetting?->name ?: $tenant->name,
            'niche_key' => $nicheKey,
            'niche_name' => $niche['name_ar'] ?? null,
            'has_catalog' => $definitions !== [],
            'features' => $features,
            'catalog_keys' => $catalogKeys,
            'enabled_modules' => $enabledModules,
        ];
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    public function syncPremiumFeatures(User $tenant, array $enabledKeys): void
    {
        $this->assertTenantAdmin($tenant);

        $tenant->loadMissing('tenantProfile');
        $nicheKey = $tenant->tenantProfile?->niche_key;
        $catalogKeys = $this->premiumCatalog->keysForNiche($nicheKey);

        if ($catalogKeys === []) {
            throw new \InvalidArgumentException('لا توجد مزايا بريميوم معرّفة لنيش هذا المستأجر.');
        }

        $this->assertPremiumFeaturesAllowed($tenant, $enabledKeys);

        $filtered = $this->premiumCatalog->filterKeysForNiche($nicheKey, $enabledKeys);

        $this->featureRegistry->syncCatalogKeys((int) $tenant->id, $catalogKeys, $filtered);
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    public function assertPremiumFeaturesAllowed(User $tenant, array $enabledKeys): void
    {
        $enabledModules = $this->moduleRegistry->enabledKeys((int) $tenant->id);
        $nicheKey = $tenant->tenantProfile?->niche_key;

        foreach ($this->premiumCatalog->definitionsForNiche($nicheKey) as $def) {
            $key = strtolower((string) ($def['key'] ?? ''));
            $requiresModule = isset($def['requires_module'])
                ? strtolower((string) $def['requires_module'])
                : null;

            if ($requiresModule === null || ! in_array($key, $enabledKeys, true)) {
                continue;
            }

            if (! in_array($requiresModule, $enabledModules, true)) {
                $moduleLabel = config("modules.modules.{$requiresModule}.name_ar") ?? $requiresModule;

                throw new \InvalidArgumentException(
                    'لا يمكن تفعيل «'.($def['name_ar'] ?? $key)."» قبل تفعيل موديول «{$moduleLabel}»."
                );
            }
        }
    }
}
