<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\AuditTrail;
use App\Models\ProductionLog;
use App\Models\ProductionShift;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class ProductionLogAuditTrailTest extends InventoryTestCase
{
    #[Test]
    public function creating_production_log_writes_audit_trail(): void
    {
        $this->seed(SystemModuleSeeder::class);

        $shift = Shift::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'S1',
            'name_ar' => 'وردية',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $productionShift = ProductionShift::query()->create([
            'user_id' => $this->tenant->id,
            'date' => now()->toDateString(),
            'shift_id' => $shift->id,
        ]);

        $item = \App\Models\Item::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->tenant);

        $log = ProductionLog::query()->create([
            'user_id' => $this->tenant->id,
            'production_shift_id' => $productionShift->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'logged_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'table_name' => 'production_logs',
            'record_id' => $log->id,
            'action' => 'create',
        ]);
    }
}
