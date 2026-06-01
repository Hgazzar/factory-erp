<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ClinicTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function migrateFreshUsing(): array
    {
        return [
            '--drop-views' => false,
            '--drop-types' => false,
            '--path' => base_path('tests/database/migrations'),
            '--realpath' => true,
        ];
    }

    protected function migrateDatabases(): void
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        if (! Schema::hasTable('patients')) {
            throw new \RuntimeException('Clinic test migrations did not create patients table.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'admin']);
        DB::table('tenant_features')->insert([
            ['tenant_id' => $this->tenant->id, 'feature_key' => 'clinic_patient_portal', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenant->id, 'feature_key' => 'clinic_appointment_self_management', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenant->id, 'feature_key' => 'clinic_whatsapp_automation', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenant->id, 'feature_key' => 'clinic_medical_insurance', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actingAs($this->tenant);
    }
}
