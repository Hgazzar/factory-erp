<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use App\Models\User;
use App\Services\Tenant\TenantModuleRegistry;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AccountingTestCase;

final class InventoryApiTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemModuleSeeder::class);
    }

    #[Test]
    public function tenant_admin_can_issue_token_and_list_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'tenant-admin@example.com',
            'password' => 'secret-password',
        ]);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $admin->id, [
            'core', 'inventory',
        ]);

        Item::factory()->forTenant($admin)->create([
            'code' => 'SKU-API-1',
            'name_ar' => 'صنف API',
        ]);

        $tokenResponse = $this->postJson('/api/v1/auth/token', [
            'email' => 'tenant-admin@example.com',
            'password' => 'secret-password',
            'device_name' => 'Test Client',
        ]);

        $tokenResponse
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.tenant_user_id', $admin->id)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'email']]]);

        $tokenResponse->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

        Sanctum::actingAs($admin, ['inventory:read']);

        $itemsResponse = $this->getJson('/api/v1/inventory/items');

        $itemsResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.items.0.code', 'SKU-API-1');
    }

    #[Test]
    public function sanctum_token_owner_resolves_tenant_for_item_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $admin->id, ['core', 'inventory']);

        $item = Item::factory()->forTenant($admin)->create([
            'code' => 'SKU-SHOW',
        ]);

        Sanctum::actingAs($admin, ['inventory:read']);

        $response = $this->getJson("/api/v1/inventory/items/{$item->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.code', 'SKU-SHOW');
    }

    #[Test]
    public function token_cannot_access_other_tenant_items(): void
    {
        $tenantA = User::factory()->create(['role' => 'admin']);
        $tenantB = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenantA->id, ['core', 'inventory']);
        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $tenantB->id, ['core', 'inventory']);

        $foreignItem = Item::factory()->forTenant($tenantB)->create([
            'code' => 'FOREIGN',
        ]);

        Sanctum::actingAs($tenantA, ['inventory:read']);

        $this->getJson("/api/v1/inventory/items/{$foreignItem->id}")
            ->assertForbidden();
    }

    #[Test]
    public function inventory_api_requires_enabled_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $admin->id, ['core']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/inventory/items')
            ->assertForbidden()
            ->assertJsonPath('code', 'module_disabled');
    }
}
