<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Metrics;

use App\Contracts\Core\Metrics\MetricsQueryInterface;
use App\Core\Metrics\MetricsQueryRegistry;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Services\Store\StoreMerchantMetricsService;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreMerchantMetricsQueryTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function service_implements_metrics_query_interface_with_store_online_key(): void
    {
        $service = app(StoreMerchantMetricsService::class);

        $this->assertInstanceOf(MetricsQueryInterface::class, $service);
        $this->assertSame('store_online', $service->key());
    }

    #[Test]
    public function registry_resolves_store_online_query(): void
    {
        $registry = app(MetricsQueryRegistry::class);

        $this->assertSame('store_online', $registry->get('store_online')->key());
    }

    #[Test]
    public function snapshot_includes_daily_sales_and_recent_orders_shape(): void
    {
        $tenantId = (int) $this->tenant->id;

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => 'metrics-store',
            'slug' => 'metrics-store',
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $device = $this->makePosDevice();

        PosSale::withoutGlobalScopes()->create([
            'user_id' => $tenantId,
            'pos_device_id' => $device->id,
            'receipt_number' => 'WEB-R-1001',
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'status' => PosSale::STATUS_COMPLETED,
            'payment_method' => PosSale::PAYMENT_COD,
            'total_amount' => 150.00,
            'total_price' => 150.00,
            'invoice_number' => 'WEB-1001',
            'customer_name' => 'عميل تجريبي',
        ]);

        $snapshot = app(StoreMerchantMetricsService::class)->snapshot($tenantId);

        $this->assertArrayHasKey('sales_today', $snapshot);
        $this->assertArrayHasKey('daily_sales', $snapshot);
        $this->assertCount(7, $snapshot['daily_sales']);
        $this->assertNotEmpty($snapshot['recent_orders']);
        $this->assertSame('WEB-1001', $snapshot['recent_orders'][0]['invoice_number']);
    }
}
