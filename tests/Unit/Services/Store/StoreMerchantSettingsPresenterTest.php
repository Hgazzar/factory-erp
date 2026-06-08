<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Store;

use App\Models\CompanySetting;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Services\Store\StoreMerchantSettingsPresenter;
use App\Support\PremiumFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreMerchantSettingsPresenterTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function manufacturing_niche_uses_showroom_labels_and_cod_wording(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'manufacturing',
            'domain' => 'factory-shop',
            'slug' => 'factory-shop',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        CompanySetting::query()->updateOrCreate(
            ['user_id' => $tenantId],
            ['country_code' => 'SA'],
        );

        $settings = TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
            'cod_enabled' => true,
        ]);

        config(['store.whatsapp.enabled' => false]);

        $ui = app(StoreMerchantSettingsPresenter::class)->present(
            $tenantId,
            $settings,
            TenantProfile::forTenantUser($tenantId),
        );

        $this->assertSame('معرض المنتجات أونلاين', $ui['page_title']);
        $this->assertSame('طرق دفع طلبات المعرض', $ui['payment_methods_heading']);
        $this->assertStringContainsString('استلام الطلب', $ui['payment_toggles'][0]['label']);
    }

    #[Test]
    public function egypt_tenant_hides_tamara_and_tabby_from_payment_toggles(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => 'eg-shop',
            'slug' => 'eg-shop',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        CompanySetting::query()->updateOrCreate(
            ['user_id' => $tenantId],
            ['country_code' => 'EG'],
        );

        $settings = TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
            'cod_enabled' => true,
            'online_payment_enabled' => true,
            'tamara_enabled' => true,
            'tabby_enabled' => true,
        ]);

        $fields = array_column(
            app(StoreMerchantSettingsPresenter::class)->present($tenantId, $settings, null)['payment_toggles'],
            'field',
        );

        $this->assertContains('cod_enabled', $fields);
        $this->assertContains('online_payment_enabled', $fields);
        $this->assertNotContains('tamara_enabled', $fields);
        $this->assertNotContains('tabby_enabled', $fields);
    }

    #[Test]
    public function whatsapp_status_uses_core_resolver_and_tenant_features(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'medical_clinics',
            'domain' => 'clinic-shop',
            'slug' => 'clinic-shop',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $settings = TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
        ]);

        config([
            'store.whatsapp.enabled' => true,
            'store.whatsapp.access_token' => 'token',
            'store.whatsapp.phone_number_id' => '123',
        ]);

        DB::table('tenant_features')->insert([
            'tenant_id' => $tenantId,
            'feature_key' => PremiumFeatureKeys::CLINIC_WHATSAPP_AUTOMATION,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache($tenantId);

        $ui = app(StoreMerchantSettingsPresenter::class)->present(
            $tenantId,
            $settings,
            TenantProfile::forTenantUser($tenantId),
        );

        $this->assertTrue($ui['whatsapp']['api_enabled']);
        $this->assertTrue($ui['whatsapp']['automation_enabled']);
        $this->assertContains('clinic_whatsapp_automation', $ui['whatsapp']['automation_feature_keys']);
    }
}
