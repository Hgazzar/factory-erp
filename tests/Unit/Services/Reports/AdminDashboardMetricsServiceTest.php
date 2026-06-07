<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Reports;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Services\Pos\PosSaleFulfillmentService;
use App\Services\Pos\PosSaleService;
use App\Services\Reports\AdminDashboardMetricsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class AdminDashboardMetricsServiceTest extends PosTestCase
{
    #[Test]
    public function channel_sales_comparison_splits_online_and_pos(): void
    {
        $device = $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Widget',
            'cost_price' => 2,
            'sale_price' => 10,
            'vat_percent' => 0,
            'opening_quantity' => 20,
            'current_quantity' => 20,
            'is_active' => true,
        ]);

        $posSale = app(PosSaleService::class)->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_CASH,
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ], (int) $this->tenant->id);

        $this->assertSame(PosSale::STATUS_COMPLETED, $posSale->status);

        $onlinePending = app(PosSaleService::class)->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_COD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ], (int) $this->tenant->id);

        $this->assertSame(PosSale::STATUS_PENDING, $onlinePending->status);

        $onlineCollected = app(PosSaleFulfillmentService::class)->markCollected(
            (int) $this->tenant->id,
            (int) $onlinePending->id,
            (int) $this->tenant->id,
        );

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $comparison = app(AdminDashboardMetricsService::class)->channelSalesComparison(
            (int) $this->tenant->id,
            $from,
            $to,
        );

        $this->assertEqualsWithDelta(10.0, $comparison['pos_total'], 0.0001);
        $this->assertSame(1, $comparison['pos_count']);
        $this->assertEqualsWithDelta(10.0, $comparison['online_total'], 0.0001);
        $this->assertSame(1, $comparison['online_count']);

        $this->assertSame(PosSale::STATUS_COLLECTED, $onlineCollected->status);
    }

    #[Test]
    public function pending_online_orders_do_not_count_in_channel_totals(): void
    {
        $device = $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Pending only',
            'cost_price' => 1,
            'sale_price' => 50,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
        ]);

        app(PosSaleService::class)->processSale((int) $this->tenant->id, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_COD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ], (int) $this->tenant->id);

        $comparison = app(AdminDashboardMetricsService::class)->channelSalesComparison(
            (int) $this->tenant->id,
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
        );

        $this->assertEqualsWithDelta(0.0, $comparison['online_total'], 0.0001);
        $this->assertSame(0, $comparison['online_count']);
    }
}
