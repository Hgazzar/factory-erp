<?php

declare(strict_types=1);

namespace Tests\Feature\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InventoryTestCase;

final class HrDashboardAttendanceTest extends InventoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'hr']
        );
    }

    #[Test]
    public function hr_dashboard_reflects_today_attendance(): void
    {
        $employee = Employee::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'HR-DASH',
            'name' => 'موظف لوحة',
            'first_name' => 'موظف',
            'middle_name' => 'لوحة',
            'last_name' => 'اختبار',
            'status' => 'active',
            'salary_type' => 'monthly',
            'attendance_policy' => 'none',
        ]);

        Attendance::query()->create([
            'user_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
        ]);

        Attendance::query()->create([
            'user_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'work_date' => now()->subDay()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->actingAs($this->tenant)
            ->get(route('hr.dashboard'))
            ->assertOk()
            ->assertSee('الحاضرون اليوم');
    }
}
