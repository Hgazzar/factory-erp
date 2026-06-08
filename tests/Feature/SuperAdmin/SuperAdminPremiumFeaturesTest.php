<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Support\PremiumFeatureKeys;
use App\Support\StoreFeatureKeys;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\Support\AccountingTestCase;

final class SuperAdminPremiumFeaturesTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_retail_niche_panel_lists_only_retail_premium_features(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = $this->createTenantWithNiche('retail');

        $response = $this->actingAs($super)
            ->getJson(route('super-admin.tenants.premium-features.show', $tenant));

        $response->assertOk()
            ->assertJsonPath('niche_key', 'retail')
            ->assertJsonPath('has_catalog', true);

        $keys = collect($response->json('features'))->pluck('key')->all();

        $this->assertSame([
            StoreFeatureKeys::ONLINE_STORE,
            PremiumFeatureKeys::RETAIL_MULTI_BRANCHES,
            PremiumFeatureKeys::RETAIL_POS_DEVICE_LINK,
            PremiumFeatureKeys::RETAIL_WHATSAPP_AUTOMATION,
        ], $keys);
    }

    public function test_sync_premium_features_updates_tenant_features_only_for_catalog(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = $this->createTenantWithNiche('medical_clinics');

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature_key' => 'legacy_other_feature',
        ]);

        $this->actingAs($super)
            ->putJson(route('super-admin.tenants.premium-features.update', $tenant), [
                'features' => [
                    PremiumFeatureKeys::CLINIC_MEDICAL_INSURANCE,
                    PremiumFeatureKeys::CLINIC_WHATSAPP_AUTOMATION,
                ],
            ])
            ->assertOk();

        $keys = TenantFeature::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('feature_key')
            ->pluck('feature_key')
            ->all();

        $this->assertContains(PremiumFeatureKeys::CLINIC_MEDICAL_INSURANCE, $keys);
        $this->assertContains(PremiumFeatureKeys::CLINIC_WHATSAPP_AUTOMATION, $keys);
        $this->assertNotContains(PremiumFeatureKeys::CLINIC_BRANCH_APPOINTMENTS, $keys);
        $this->assertContains('legacy_other_feature', $keys);
    }

    public function test_manufacturing_niche_rejects_unknown_feature_keys(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = $this->createTenantWithNiche('manufacturing');

        $this->actingAs($super)
            ->putJson(route('super-admin.tenants.premium-features.update', $tenant), [
                'features' => [PremiumFeatureKeys::RETAIL_MULTI_BRANCHES],
            ])
            ->assertUnprocessable();
    }

    private function createTenantWithNiche(string $nicheKey): User
    {
        $tenant = User::factory()->create([
            'role' => 'admin',
            'email' => "tenant-{$nicheKey}-".uniqid('', true).'@akwad.test',
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenant->id,
            'niche_key' => $nicheKey,
            'domain' => "tenant-{$nicheKey}-{$tenant->id}",
            'slug' => "tenant-{$nicheKey}-{$tenant->id}",
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        return $tenant;
    }
}
