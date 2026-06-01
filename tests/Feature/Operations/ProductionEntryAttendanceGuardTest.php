<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ProductionShift;
use App\Models\Shift;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Hr\ProductionEntryAttendanceGuard;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class ProductionEntryAttendanceGuardTest extends InventoryTestCase
{
    private ProductionShift $productionShift;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'manufacturing', 'inventory']
        );

        $shift = Shift::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'T1',
            'name_ar' => 'وردية',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $this->productionShift = ProductionShift::query()->create([
            'user_id' => $this->tenant->id,
            'date' => now()->toDateString(),
            'shift_id' => $shift->id,
        ]);

        $this->employee = Employee::query()->create([
            'user_id' => $this->tenant->id,
            'linked_user_id' => $this->tenant->id,
            'code' => 'EMP-ATT',
            'name' => 'موظف حضور',
            'first_name' => 'موظف',
            'middle_name' => 'حضور',
            'last_name' => 'اختبار',
            'status' => 'active',
            'salary_type' => 'monthly',
            'attendance_policy' => 'none',
        ]);
    }

    #[Test]
    public function it_blocks_production_when_employee_is_absent_today(): void
    {
        Attendance::query()->create([
            'user_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'work_date' => now()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
        ]);

        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create();

        $this->actingAs($this->tenant)
            ->post(route('operations.production-entry.store'), [
                'production_shift_id' => $this->productionShift->id,
                'item_id' => $finished->id,
                'quantity' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', ProductionEntryAttendanceGuard::BLOCK_MESSAGE);
    }

    #[Test]
    public function it_allows_production_when_employee_is_present_today(): void
    {
        Attendance::query()->create([
            'user_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'work_date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'check_in_at' => now(),
        ]);

        $warehouse = Warehouse::factory()->forTenant($this->tenant)->create();
        $finished = Item::factory()->forTenant($this->tenant)->finishedGood()->create(['cost' => 5]);

        $this->actingAs($this->tenant)
            ->post(route('operations.production-entry.store'), [
                'production_shift_id' => $this->productionShift->id,
                'item_id' => $finished->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
