<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Store;

use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Services\Store\StoreOnlineDashboardPresenter;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreOnlineDashboardPresenterTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function returns_null_when_online_store_feature_disabled(): void
    {
        TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'retail',
            'domain' => 'no-feature',
            'slug' => 'no-feature',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $panel = app(StoreOnlineDashboardPresenter::class)->present((int) $this->tenant->id);

        $this->assertNull($panel);
    }

    #[Test]
    public function full_panel_includes_links_and_niche_labels_for_manufacturing(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'manufacturing',
            'domain' => 'factory-shop',
            'slug' => 'factory-shop',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $panel = app(StoreOnlineDashboardPresenter::class)->present(
            $tenantId,
            StoreOnlineDashboardPresenter::VARIANT_FULL,
        );

        $this->assertNotNull($panel);
        $this->assertSame('full', $panel['variant']);
        $this->assertStringContainsString('معرض', $panel['storefront_label']);
        $this->assertArrayHasKey('daily_sales', $panel['metrics']);
        $this->assertNotNull($panel['links']['storefront']);
        $this->assertStringContainsString('orders', $panel['links']['orders']);
    }

    #[Test]
    public function compact_variant_trims_metrics_to_kpis_only(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => 'compact-store',
            'slug' => 'compact-store',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $panel = app(StoreOnlineDashboardPresenter::class)->present(
            $tenantId,
            StoreOnlineDashboardPresenter::VARIANT_COMPACT,
        );

        $this->assertNotNull($panel);
        $this->assertSame('compact', $panel['variant']);
        $this->assertArrayHasKey('sales_today', $panel['metrics']);
        $this->assertArrayNotHasKey('daily_sales', $panel['metrics']);
        $this->assertArrayNotHasKey('top_products', $panel['metrics']);
    }
}
