<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantModuleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function admin_without_finance_module_is_redirected_from_finance_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $registry = app(TenantModuleRegistry::class);
        $registry->syncModulesForTenant((int) $admin->id, [
            'core', 'inventory', 'sales',
        ]);

        $response = $this->actingAs($admin)->get(route('finance.dashboard'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function admin_with_finance_module_can_access_finance_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $admin->id, [
            'core', 'finance', 'inventory',
        ]);

        $response = $this->actingAs($admin)->get(route('finance.dashboard'));

        $response->assertOk();
    }

    #[Test]
    public function legacy_tenant_without_registry_rows_has_full_module_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('finance.dashboard'));

        $response->assertOk();
    }

    #[Test]
    public function platform_super_admin_bypasses_module_gate(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $superAdmin->id, ['core']);

        $response = $this->actingAs($superAdmin)->get(route('finance.dashboard'));

        $response->assertOk();
    }
}
