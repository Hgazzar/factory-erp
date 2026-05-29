<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Pos;

use App\Models\Item;
use App\Services\PosCostingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class PosCostingServiceTest extends InventoryTestCase
{
    private PosCostingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PosCostingService::class);
    }

    #[Test]
    public function unit_cost_for_finished_good_matches_item_wac(): void
    {
        $item = Item::factory()
            ->forTenant($this->tenant)
            ->finishedGood()
            ->create(['cost' => 37.5625]);

        $cost = $this->service->unitCostForFinishedGoodSale($item);

        $this->assertEqualsWithDelta(37.5625, $cost, 0.0001);
        $this->assertItemCost($item, 37.5625);
    }

    #[Test]
    public function it_rejects_non_finished_good_items(): void
    {
        $raw = Item::factory()->forTenant($this->tenant)->rawMaterial()->create(['cost' => 10]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('نقطة البيع تبيع منتجات تامة فقط');

        $this->service->unitCostForFinishedGoodSale($raw);
    }

    #[Test]
    public function it_rejects_finished_good_without_positive_wac(): void
    {
        $item = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 0]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('لم تُحدَّد تكلفة للصنف');

        $this->service->unitCostForFinishedGoodSale($item);
    }

    #[Test]
    public function discounted_line_keeps_wac_and_reduces_gross_profit(): void
    {
        $item = Item::factory()
            ->forTenant($this->tenant)
            ->finishedGood()
            ->create(['cost' => 40]);

        $unitCost = $this->service->unitCostForFinishedGoodSale($item);
        $qty = 2.0;
        $listUnitPrice = 100.0;
        $discountedUnitPrice = 75.0;

        $profitAtList = $this->lineGrossProfit($qty, $listUnitPrice, $unitCost);
        $profitAtDiscount = $this->lineGrossProfit($qty, $discountedUnitPrice, $unitCost);

        $this->assertEqualsWithDelta(120.0, $profitAtList, 0.0001);
        $this->assertEqualsWithDelta(70.0, $profitAtDiscount, 0.0001);
        $this->assertLessThan($profitAtList, $profitAtDiscount);
    }

    #[Test]
    public function multi_line_invoice_profitability_sums_per_line_wac(): void
    {
        $itemA = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 25]);
        $itemB = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 10]);

        $costA = $this->service->unitCostForFinishedGoodSale($itemA);
        $costB = $this->service->unitCostForFinishedGoodSale($itemB);

        $lineA = $this->lineGrossProfit(3, 80, $costA);
        $lineB = $this->lineGrossProfit(5, 30, $costB);

        $this->assertEqualsWithDelta(165.0, $lineA, 0.0001);
        $this->assertEqualsWithDelta(100.0, $lineB, 0.0001);
        $this->assertEqualsWithDelta(265.0, round($lineA + $lineB, 4), 0.0001);
    }

    private function lineGrossProfit(float $quantity, float $unitPrice, float $unitCost): float
    {
        $revenue = round($quantity * $unitPrice, 4);
        $cost = round($quantity * $unitCost, 4);

        return round($revenue - $cost, 4);
    }
}
