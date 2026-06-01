<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\PosFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class PosTerminalCheckoutTest extends PosTestCase
{
    private PosProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(SystemModuleSeeder::class);

        app(TenantModuleRegistry::class)->syncModulesForTenant((int) $this->tenant->id, [
            'core', 'pos', 'finance', 'inventory',
        ]);

        $category = PosProductCategory::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'مشروبات',
            'code' => 'BEV',
        ]);

        $this->product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'pos_product_category_id' => $category->id,
            'name' => 'مياه 600 مل',
            'sku' => 'W-600',
            'barcode' => '999888777',
            'cost_price' => 2.0,
            'sale_price' => 5.0,
            'vat_percent' => 15,
            'opening_quantity' => 20,
            'current_quantity' => 20,
            'low_stock_alert_quantity' => 3,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function cashier_terminal_page_loads_with_products(): void
    {
        $device = $this->makePosDevice();

        $this->get(route('pos.cashier', ['device_id' => $device->id]))
            ->assertOk()
            ->assertSee('كاشير سريع')
            ->assertSee('999888777');
    }

    #[Test]
    public function api_lists_categories_and_products(): void
    {
        $this->getJson(route('pos.api.categories'))
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'مشروبات');

        $this->getJson(route('pos.api.products', ['q' => 'مياه']))
            ->assertOk()
            ->assertJsonPath('products.0.barcode', '999888777');
    }

    #[Test]
    public function barcode_lookup_adds_product_context(): void
    {
        $this->getJson(route('pos.api.products.lookup', ['barcode' => '999888777']))
            ->assertOk()
            ->assertJsonPath('product.name', 'مياه 600 مل');

        $this->getJson(route('pos.api.products.lookup', ['barcode' => 'missing']))
            ->assertNotFound();
    }

    #[Test]
    public function checkout_completes_sale_and_opens_receipt(): void
    {
        $device = $this->makePosDevice();

        $response = $this->postJson(route('pos.api.checkout'), [
            'pos_device_id' => $device->id,
            'payment_method' => 'cash',
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('sale.total_amount', 11.5);

        $saleId = (int) $response->json('sale.id');
        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);

        $this->assertNotNull($sale->journal_entry_id);
        $this->assertSame('completed', $sale->status);
        $this->assertEqualsWithDelta(18.0, (float) $this->product->fresh()->current_quantity, 0.0001);

        $this->get(route('pos.terminal.receipt', $sale))
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('مياه 600 مل');
    }

    #[Test]
    public function manual_price_override_requires_feature_flag(): void
    {
        $device = $this->makePosDevice();

        $this->postJson(route('pos.api.checkout'), [
            'pos_device_id' => $device->id,
            'payment_method' => 'cash',
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 4.0],
            ],
        ])->assertForbidden();

        TenantFeature::query()->create([
            'tenant_id' => $this->tenant->id,
            'feature_key' => PosFeatureKeys::MANUAL_PRICE_OVERRIDE,
        ]);
        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $this->postJson(route('pos.api.checkout'), [
            'pos_device_id' => $device->id,
            'payment_method' => 'cash',
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 4.0],
            ],
        ])->assertCreated()
            ->assertJsonPath('sale.subtotal_amount', 4);
    }

    #[Test]
    public function split_payment_posts_balanced_journal(): void
    {
        $device = $this->makePosDevice();

        $this->postJson(route('pos.api.checkout'), [
            'pos_device_id' => $device->id,
            'payment_method' => 'mixed',
            'payment_splits' => [
                ['method' => 'cash', 'amount' => 5.75],
                ['method' => 'card', 'amount' => 5.75],
            ],
            'lines' => [
                ['pos_product_id' => $this->product->id, 'quantity' => 2],
            ],
        ])->assertCreated()
            ->assertJsonPath('sale.payment_method', 'mixed');

        $sale = PosSale::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertJournalIsBalanced($sale->journalEntry);
    }
}
