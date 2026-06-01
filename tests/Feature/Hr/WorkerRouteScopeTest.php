<?php

declare(strict_types=1);

namespace Tests\Feature\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SystemModuleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class WorkerRouteScopeTest extends AccountingTestCase
{
    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'hr', 'manufacturing', 'pos']
        );

        $this->worker = User::factory()->create([
            'role' => 'worker',
            'email' => 'worker-scope@akwad.test',
        ]);

        $workerEmployee = Employee::query()->create([
            'user_id' => $this->tenant->id,
            'linked_user_id' => $this->worker->id,
            'code' => 'EMP-WORKER',
            'name' => 'عامل إنتاج',
            'first_name' => 'عامل',
            'middle_name' => 'إنتاج',
            'last_name' => 'اختبار',
            'status' => 'active',
            'salary_type' => 'monthly',
            'attendance_policy' => 'none',
        ]);

        Attendance::query()->create([
            'user_id' => $this->tenant->id,
            'employee_id' => $workerEmployee->id,
            'work_date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
        ]);
    }

    #[Test]
    public function worker_cannot_access_tenant_dashboard(): void
    {
        $this->actingAs($this->worker)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function worker_cannot_access_hr_payrolls(): void
    {
        $this->actingAs($this->worker)
            ->get(route('hr.payrolls.index'))
            ->assertForbidden();
    }

    #[Test]
    public function worker_cannot_access_operations_dashboard(): void
    {
        $this->actingAs($this->worker)
            ->get(route('operations.dashboard.index'))
            ->assertForbidden();
    }

    #[Test]
    public function worker_can_access_production_entry_when_manufacturing_enabled(): void
    {
        $this->actingAs($this->worker)
            ->get(route('operations.production-entry.create'))
            ->assertOk();
    }

    #[Test]
    public function salaries_route_redirects_to_payrolls(): void
    {
        $this->actingAs($this->tenant)
            ->get('/hr/salaries')
            ->assertRedirect(route('hr.payrolls.index'));
    }
}
