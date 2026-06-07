<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\JournalItem;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreOnlineOrderFulfillmentTest extends PosTestCase
{
    private string $slug = 'fulfillment-store';

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
    }

    #[Test]
    public function merchant_can_collect_pending_online_order(): void
    {
        $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'COD Product',
            'cost_price' => 3,
            'sale_price' => 30,
            'vat_percent' => 0,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $checkout = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'عميل',
            'customer_phone' => '0500000000',
            'customer_address' => 'الرياض',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $saleId = (int) $checkout->json('order.id');
        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);
        $this->assertSame(PosSale::STATUS_PENDING, $sale->status);

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'collected'])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame(PosSale::STATUS_COLLECTED, $sale->status);
        $this->assertNotNull($sale->journal_entry_id);
    }

    #[Test]
    public function ar_two_step_delivered_then_collected(): void
    {
        $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'AR Product',
            'cost_price' => 2,
            'sale_price' => 40,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $checkout = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'AR Client',
            'customer_phone' => '0502222222',
            'customer_address' => 'الدمام',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $saleId = (int) $checkout->json('order.id');

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'delivered'])
            ->assertRedirect();

        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);
        $this->assertSame(PosSale::STATUS_DELIVERED, $sale->status);
        $this->assertNotNull($sale->journal_entry_id);
        $this->assertNull($sale->collection_journal_entry_id);

        $arDebit = (float) JournalItem::query()
            ->where('journal_entry_id', $sale->journal_entry_id)
            ->where('account_id', $this->receivableAccount->id)
            ->sum('debit');
        $this->assertGreaterThan(0, $arDebit);

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'collected'])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame(PosSale::STATUS_COLLECTED, $sale->status);
        $this->assertNotNull($sale->collection_journal_entry_id);

        $cashDebit = (float) JournalItem::query()
            ->where('journal_entry_id', $sale->collection_journal_entry_id)
            ->where('account_id', $this->cashAccount->id)
            ->sum('debit');
        $this->assertGreaterThan(0, $cashDebit);
    }

    #[Test]
    public function orders_index_lists_pending_order(): void
    {
        $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Listed',
            'cost_price' => 1,
            'sale_price' => 15,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'زائر',
            'customer_phone' => '0501111111',
            'customer_address' => 'جدة',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->actingAs($this->tenant)
            ->get(route('pos.orders.index'))
            ->assertOk()
            ->assertSee('زائر')
            ->assertSee('بانتظار التسليم');
    }

    #[Test]
    public function online_card_payment_completes_immediately(): void
    {
        $this->makePosDevice();
        TenantStoreSetting::forTenant((int) $this->tenant->id)->update([
            'online_payment_enabled' => true,
            'online_payment_provider' => 'paymob',
            'online_payment_mode' => 'sandbox',
        ]);

        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Card Product',
            'cost_price' => 4,
            'sale_price' => 50,
            'vat_percent' => 0,
            'opening_quantity' => 3,
            'current_quantity' => 3,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $response = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'بطاقة',
            'customer_phone' => '0503333333',
            'customer_address' => 'مكة',
            'payment_method' => 'card',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('order.payment_method', PosSale::PAYMENT_CARD);

        $sale = PosSale::withoutGlobalScopes()->findOrFail((int) $response->json('order.id'));
        $this->assertSame(PosSale::STATUS_COMPLETED, $sale->status);
        $this->assertNotNull($sale->journal_entry_id);
        $this->assertNotNull($sale->payment_gateway_reference);
    }
}
