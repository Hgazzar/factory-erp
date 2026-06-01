<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Item;
use App\Models\ProductionLog;
use App\Models\ProductionRecord;
use App\Models\ProductionShift;
use App\Models\Shift;
use App\Models\TenantFeature;
use App\Models\Warehouse;
use App\Support\PremiumFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class ProductionEntryHardeningTest extends InventoryTestCase
{
    private ProductionShift $productionShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'manufacturing', 'inventory', 'finance']
        );

        $shift = Shift::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'T1',
            'name_ar' => 'وردية اختبار',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $this->productionShift = ProductionShift::query()->create([
            'user_id' => $this->tenant->id,
            'date' => now()->toDateString(),
            'shift_id' => $shift->id,
        ]);
    }

    #[Test]
    public function it_requires_warehouse_when_inventory_auto_link_feature_enabled(): void
    {
        TenantFeature::query()->create([
            'tenant_id' => $this->tenant->id,
            'feature_key' => PremiumFeatureKeys::MANUFACTURING_INVENTORY_AUTO_LINK,
        ]);

        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create();

        $this->actingAs($this->tenant)
            ->post(route('operations.production-entry.store'), [
                'production_shift_id' => $this->productionShift->id,
                'item_id' => $finished->id,
                'quantity' => 5,
            ])
            ->assertSessionHasErrors('warehouse_id');
    }

    #[Test]
    public function it_skips_journal_when_warehouse_not_selected(): void
    {
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 10]);

        $this->actingAs($this->tenant)
            ->post(route('operations.production-entry.store'), [
                'production_shift_id' => $this->productionShift->id,
                'item_id' => $finished->id,
                'quantity' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $record = ProductionRecord::query()->latest('id')->first();

        $this->assertNotNull($record);
        $this->assertNull($record->journal_entry_id);

        $log = ProductionLog::query()->latest('id')->first();
        $this->assertNull($log->inventory_synced_at);
    }

    #[Test]
    public function it_creates_journal_when_warehouse_selected(): void
    {
        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 12]);

        $this->actingAs($this->tenant)
            ->post(route('operations.production-entry.store'), [
                'production_shift_id' => $this->productionShift->id,
                'item_id' => $finished->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $record = ProductionRecord::query()->latest('id')->first();

        $this->assertNotNull($record?->journal_entry_id);

        $log = ProductionLog::query()->latest('id')->first();
        $this->assertNotNull($log->inventory_synced_at);
    }
}
