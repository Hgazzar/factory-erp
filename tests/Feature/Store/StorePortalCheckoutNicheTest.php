<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Support\CheckoutBoundaryCatalog;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StorePortalCheckoutNicheTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function storeEnabledNicheProvider(): array
    {
        $cases = [];

        foreach (CheckoutBoundaryCatalog::storeEnabledNicheKeys() as $nicheKey) {
            $cases[$nicheKey] = [$nicheKey];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('storeEnabledNicheProvider')]
    public function cod_checkout_works_when_online_store_feature_is_enabled(string $nicheKey): void
    {
        [$slug, $product] = $this->bootstrapPortalTenant($nicheKey);
        $this->makePosDevice();

        $response = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $slug]), [
            'customer_name' => 'عميل '.$nicheKey,
            'customer_phone' => '0502222222',
            'customer_address' => 'عنوان التوصيل',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.payment_method', PosSale::PAYMENT_COD);

        $sale = PosSale::withoutGlobalScopes()->findOrFail((int) $response->json('order.id'));
        $this->assertSame(PosSale::CHANNEL_ONLINE_STORE, $sale->sale_channel);
        $this->assertSame(PosSale::STATUS_PENDING, $sale->status);
    }

    #[Test]
    #[DataProvider('storeEnabledNicheProvider')]
    public function store_home_is_public_for_enabled_niche(string $nicheKey): void
    {
        [$slug] = $this->bootstrapPortalTenant($nicheKey);

        $this->get(route('store.portal.home', ['tenant_slug' => $slug]))
            ->assertOk()
            ->assertSee('منتج '.$nicheKey);
    }

    #[Test]
    public function portal_returns_not_found_without_online_store_feature(): void
    {
        [$slug] = $this->bootstrapPortalTenant('medical_clinics', enableFeature: false);

        $this->get(route('store.portal.home', ['tenant_slug' => $slug]))
            ->assertNotFound();
    }

    #[Test]
    public function portal_returns_not_found_for_niche_without_store_support(): void
    {
        $tenantId = (int) $this->tenant->id;
        $slug = 'legacy-niche-store';

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant($tenantId, [
            'core', 'pos', 'finance', 'inventory',
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'legacy_unknown',
            'domain' => $slug,
            'slug' => $slug,
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
        ]);

        $this->get(route('store.portal.home', ['tenant_slug' => $slug]))
            ->assertNotFound();
    }

    /**
     * @return array{0: string, 1: PosProduct}
     */
    private function bootstrapPortalTenant(string $nicheKey, bool $enableFeature = true): array
    {
        $tenantId = (int) $this->tenant->id;
        $slug = 'niche-'.$nicheKey;

        TenantProfile::query()->where('tenant_user_id', $tenantId)->delete();
        TenantFeature::query()->where('tenant_id', $tenantId)->where('feature_key', StoreFeatureKeys::ONLINE_STORE)->delete();
        TenantStoreSetting::query()->where('tenant_user_id', $tenantId)->delete();
        PosProduct::query()->where('user_id', $tenantId)->delete();

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant($tenantId, [
            'core', 'pos', 'finance', 'inventory',
        ]);

        if ($enableFeature) {
            TenantFeature::query()->create([
                'tenant_id' => $tenantId,
                'feature_key' => StoreFeatureKeys::ONLINE_STORE,
            ]);

            app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache($tenantId);
        }

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => $nicheKey,
            'domain' => $slug,
            'slug' => $slug,
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
            'cod_enabled' => true,
        ]);

        $product = PosProduct::query()->create([
            'user_id' => $tenantId,
            'name' => 'منتج '.$nicheKey,
            'cost_price' => 10,
            'sale_price' => 50,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        return [$slug, $product];
    }
}
