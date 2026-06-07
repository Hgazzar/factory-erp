<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StorePortalCheckoutTest extends PosTestCase
{
    private string $slug = 'demo-store';

    private PosProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);

        $tenantId = (int) $this->tenant->id;

        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant($tenantId, [
            'core', 'pos', 'finance', 'inventory',
        ]);

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => StoreFeatureKeys::ONLINE_STORE,
        ]);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => $this->slug,
            'slug' => $this->slug,
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        $category = PosProductCategory::query()->create([
            'user_id' => $tenantId,
            'name' => 'إلكترونيات',
            'code' => 'ELEC',
        ]);

        $this->product = PosProduct::query()->create([
            'user_id' => $tenantId,
            'pos_product_category_id' => $category->id,
            'name' => 'سماعة لاسلكية',
            'sku' => 'HP-01',
            'barcode' => '555666777',
            'cost_price' => 50,
            'sale_price' => 100,
            'vat_percent' => 15,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'is_active' => true,
            'is_published_online' => true,
        ]);
    }

    #[Test]
    public function store_home_is_public_for_retail_tenant(): void
    {
        $this->get(route('store.portal.home', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('سماعة لاسلكية')
            ->assertSee('تسوق الآن')
            ->assertSee('akStoreQuickAdd', false);
    }

    #[Test]
    public function store_static_pages_and_nav_are_available(): void
    {
        $this->get(route('store.portal.offers', ['tenant_slug' => $this->slug]))
            ->assertRedirect(route('store.portal.home', ['tenant_slug' => $this->slug]).'?featured=1#productsSection');
        $this->get(route('store.portal.returns', ['tenant_slug' => $this->slug]))->assertOk()->assertSee('سياسة الإرجاع');
        $this->get(route('store.portal.track', ['tenant_slug' => $this->slug]))->assertOk()->assertSee('تتبع الطلب');
        $this->get(route('store.portal.faq', ['tenant_slug' => $this->slug]))->assertOk();
    }

    #[Test]
    public function api_lists_only_published_products(): void
    {
        PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'مخفي',
            'sale_price' => 20,
            'vat_percent' => 0,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => false,
        ]);

        $this->getJson(route('store.portal.api.products', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertJsonCount(1, 'products');
    }

    #[Test]
    public function online_checkout_auto_provisions_pos_device_when_missing(): void
    {
        $this->assertSame(0, \App\Models\PosDevice::withoutGlobalScopes()->where('user_id', $this->tenant->id)->count());

        $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'عميل',
            'customer_phone' => '0509999999',
            'customer_address' => 'القاهرة',
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->assertSame(1, \App\Models\PosDevice::withoutGlobalScopes()->where('user_id', $this->tenant->id)->count());
    }

    #[Test]
    public function checkout_page_shows_cod_and_online_payment_options(): void
    {
        $this->get(route('store.portal.checkout', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('paymentMethods', false);

        $this->getJson(route('store.portal.api.payment-methods', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertJsonFragment(['key' => 'cod', 'label' => 'الدفع عند الاستلام'])
            ->assertJsonFragment(['key' => 'card']);
    }

    #[Test]
    public function online_checkout_creates_cod_sale_and_deducts_stock(): void
    {
        $this->makePosDevice();

        $response = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'أحمد محمد',
            'customer_phone' => '0500000000',
            'customer_address' => 'الرياض — حي النخيل',
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.payment_method', PosSale::PAYMENT_COD);

        $sale = PosSale::withoutGlobalScopes()->findOrFail((int) $response->json('order.id'));
        $this->assertSame(PosSale::CHANNEL_ONLINE_STORE, $sale->sale_channel);
        $this->assertSame('أحمد محمد', $sale->customer_name);
        $this->assertNull($sale->journal_entry_id);
        $this->assertSame(PosSale::STATUS_PENDING, $sale->status);
        $this->assertEqualsWithDelta(8.0, (float) $this->product->fresh()->current_quantity, 0.0001);

        $this->get(route('store.portal.order.success', ['tenant_slug' => $this->slug, 'saleId' => $sale->id]))
            ->assertOk()
            ->assertSee('تم تأكيد طلبك');
    }

    #[Test]
    public function store_product_page_has_add_to_cart_handler(): void
    {
        $this->get(route('store.portal.product', ['tenant_slug' => $this->slug, 'product' => $this->product->id]))
            ->assertOk()
            ->assertSee('أضف للسلة')
            ->assertSee('akStoreQuickAdd', false);
    }

    #[Test]
    public function unpublished_product_cannot_be_ordered(): void
    {
        $this->makePosDevice();
        $hidden = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'سرّي',
            'sale_price' => 10,
            'vat_percent' => 0,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => false,
        ]);

        $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'زائر',
            'customer_phone' => '0501111111',
            'customer_address' => 'عنوان',
            'lines' => [
                ['pos_product_id' => $hidden->id, 'quantity' => 1],
            ],
        ])->assertStatus(422);
    }
}
