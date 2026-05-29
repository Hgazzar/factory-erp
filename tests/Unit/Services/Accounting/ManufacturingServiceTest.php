<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Accounting;

use App\Models\ManufacturingRunLine;
use App\Services\ManufacturingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ManufacturingServiceTest extends TestCase
{
    #[Test]
    public function planned_consumption_includes_scrap_percent(): void
    {
        $qty = ManufacturingService::plannedConsumptionFromBomLine(
            qtyPerFgUnit: 2.0,
            scrapPercent: 10.0,
            workOrderQty: 100.0,
        );

        $this->assertEqualsWithDelta(220.0, $qty, 0.0001);
    }

    #[Test]
    public function inventory_withdrawal_uses_planned_net_base_and_actual_scrap(): void
    {
        $line = new ManufacturingRunLine([
            'planned_quantity' => 110.0,
            'planned_scrap_percent' => 10.0,
            'actual_scrap_percent' => 5.0,
            'quantity_consumed' => 0,
        ]);

        $withdrawal = ManufacturingService::inventoryWithdrawalQuantityForLine($line);

        $this->assertEqualsWithDelta(105.0, $withdrawal, 0.0001);
    }

    #[Test]
    public function inventory_withdrawal_falls_back_to_quantity_consumed_when_planned_missing(): void
    {
        $line = new ManufacturingRunLine([
            'planned_quantity' => null,
            'planned_scrap_percent' => null,
            'actual_scrap_percent' => null,
            'quantity_consumed' => 42.5,
        ]);

        $this->assertEqualsWithDelta(42.5, ManufacturingService::inventoryWithdrawalQuantityForLine($line), 0.0001);
    }
}
