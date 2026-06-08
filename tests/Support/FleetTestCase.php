<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Support\PremiumFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class FleetTestCase extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);

        $this->tenant = User::factory()->create(['role' => 'admin']);

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenant->id,
            ['core', 'fleet', 'finance']
        );

        \App\Models\TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenant->id,
            'niche_key' => 'fleet_agents',
            'domain' => 'test-fleet',
            'slug' => 'test-fleet',
            'status' => \App\Models\TenantProfile::STATUS_ACTIVE,
        ]);

        DB::table('tenant_features')->insert([
            'tenant_id' => $this->tenant->id,
            'feature_key' => PremiumFeatureKeys::FLEET_FIELD_OPS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $this->actingAs($this->tenant);
    }
}
