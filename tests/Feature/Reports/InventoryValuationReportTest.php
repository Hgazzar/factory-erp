<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class InventoryValuationReportTest extends InventoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'inventory']
        );
    }

    #[Test]
    public function valuation_report_shows_item_totals(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $item = Item::factory()->forTenant($this->tenant)->create([
            'code' => 'VAL-001',
            'name_ar' => 'صنف تقييم',
            'cost' => 10,
        ]);

        ItemWarehouse::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        $this->actingAs($this->tenant)
            ->get(route('inventory.reports.valuation'))
            ->assertOk()
            ->assertSee('VAL-001')
            ->assertSee('50');
    }

    #[Test]
    public function valuation_report_exports_excel(): void
    {
        $this->actingAs($this->tenant)
            ->get(route('inventory.reports.valuation', ['export' => 'excel']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
    }
}
