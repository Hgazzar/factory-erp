<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\CompanySetting;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\ChartOfAccountsProvisioner;
use App\Services\Tenant\NicheCatalog;
use App\Services\Tenant\NicheLexiconService;
use App\Services\Tenant\PremiumFeatureCatalog;
use App\Services\Tenant\TenantBrandingService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\TenantSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * إنشاء مستأجر SaaS جديد: حساب المالك، الملف، الموديولات، ودليل الحسابات.
 */
final class TenantProvisionerService
{
    public function __construct(
        private readonly NicheCatalog $nicheCatalog,
        private readonly TenantModuleRegistry $moduleRegistry,
        private readonly NicheLexiconService $lexiconService,
        private readonly PremiumFeatureCatalog $premiumCatalog,
        private readonly TenantFeatureRegistry $featureRegistry,
        private readonly TenantBrandingService $tenantBranding,
    ) {}

    /**
     * @param  array{company_name: string, owner_name: string, email: string, slug: string, niche_key: string}  $data
     * @return array{tenant: User, temporary_password: string}
     */
    public function provision(array $data): array
    {
        $nicheKey = strtolower(trim((string) $data['niche_key']));

        if (! $this->nicheCatalog->exists($nicheKey)) {
            throw new \InvalidArgumentException("Unknown niche: {$nicheKey}");
        }

        $slug = TenantSlug::normalize((string) $data['slug']);
        $temporaryPassword = Str::password(12);

        $tenant = DB::transaction(function () use ($data, $nicheKey, $slug, $temporaryPassword): User {
            $tenant = User::query()->create([
                'name' => trim((string) $data['owner_name']),
                'email' => strtolower(trim((string) $data['email'])),
                'role' => 'admin',
                'password' => Hash::make($temporaryPassword),
            ]);

            CompanySetting::query()->create([
                'user_id' => $tenant->id,
                'name' => trim((string) $data['company_name']),
            ]);

            TenantProfile::query()->create([
                'tenant_user_id' => $tenant->id,
                'niche_key' => $nicheKey,
                'domain' => $slug,
                'slug' => $slug,
                'status' => TenantProfile::STATUS_ACTIVE,
            ]);

            $moduleKeys = $this->nicheCatalog->defaultModuleKeys($nicheKey);
            $this->moduleRegistry->syncEnabledModuleKeys((int) $tenant->id, $moduleKeys);

            ChartOfAccountsProvisioner::ensureForUser((int) $tenant->id);

            $this->tenantBranding->ensureForTenant((int) $tenant->id);

            $this->syncInitialPremiumFeatures((int) $tenant->id, $nicheKey, $data);

            return $tenant;
        });

        $this->moduleRegistry->forgetCache((int) $tenant->id);
        $this->featureRegistry->forgetCache((int) $tenant->id);
        $this->lexiconService->forgetCache((int) $tenant->id);

        return [
            'tenant' => $tenant->fresh(['companySetting']),
            'temporary_password' => $temporaryPassword,
        ];
    }

    /** @deprecated Use TenantSlug::normalize() */
    public function normalizeDomain(string $domain): string
    {
        return TenantSlug::normalize($domain);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncInitialPremiumFeatures(int $tenantUserId, string $nicheKey, array $data): void
    {
        $catalogKeys = $this->premiumCatalog->keysForNiche($nicheKey);
        if ($catalogKeys === []) {
            return;
        }

        $requested = isset($data['premium_features']) && is_array($data['premium_features'])
            ? array_map(static fn ($k) => strtolower(trim((string) $k)), $data['premium_features'])
            : $this->nicheCatalog->defaultPremiumFeatureKeys($nicheKey);

        $enabled = $this->premiumCatalog->filterKeysForNiche($nicheKey, $requested);

        if ($enabled !== []) {
            $this->featureRegistry->syncCatalogKeys($tenantUserId, $catalogKeys, $enabled);
        }
    }
}
