<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Support\PremiumFeatureKeys;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreOrderWhatsAppTest extends PosTestCase
{
    private string $slug = 'whatsapp-store';

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

        TenantFeature::query()->create([
            'tenant_id' => $tenantId,
            'feature_key' => PremiumFeatureKeys::RETAIL_WHATSAPP_AUTOMATION,
        ]);

        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache($tenantId);

        TenantProfile::query()->create([
            'tenant_user_id' => $tenantId,
            'niche_key' => 'retail',
            'domain' => $this->slug,
            'slug' => $this->slug,
            'status' => TenantProfile::STATUS_ACTIVE,
        ]);

        TenantStoreSetting::query()->create([
            'tenant_user_id' => $tenantId,
            'is_store_enabled' => true,
        ]);

        config([
            'store.whatsapp.enabled' => false,
            'store.whatsapp.access_token' => '',
            'store.whatsapp.phone_number_id' => '',
        ]);
    }

    #[Test]
    public function delivered_status_sends_whatsapp_delivery_notification_in_dry_run(): void
    {
        $saleId = $this->placeCodOrder();

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'delivered'])
            ->assertRedirect();

        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);
        $this->assertSame(PosSale::STATUS_DELIVERED, $sale->status);
        $this->assertNotNull($sale->whatsapp_delivered_notified_at);
        $this->assertNull($sale->whatsapp_invoice_notified_at);
    }

    #[Test]
    public function collected_status_sends_whatsapp_invoice_notification_in_dry_run(): void
    {
        $saleId = $this->placeCodOrder();

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'collected'])
            ->assertRedirect();

        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);
        $this->assertSame(PosSale::STATUS_COLLECTED, $sale->status);
        $this->assertNotNull($sale->whatsapp_invoice_notified_at);
    }

    #[Test]
    public function completed_card_checkout_sends_invoice_whatsapp_automatically_in_dry_run(): void
    {
        $this->makePosDevice();
        TenantStoreSetting::forTenant((int) $this->tenant->id)->update([
            'online_payment_enabled' => true,
            'online_payment_provider' => 'paymob',
            'online_payment_mode' => 'sandbox',
        ]);

        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Card WhatsApp Product',
            'cost_price' => 5,
            'sale_price' => 60,
            'vat_percent' => 0,
            'opening_quantity' => 5,
            'current_quantity' => 5,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $checkout = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'عميل فيزا',
            'customer_phone' => '0504444444',
            'customer_address' => 'الرياض',
            'payment_method' => 'card',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $sale = PosSale::withoutGlobalScopes()->findOrFail((int) $checkout->json('order.id'));
        $this->assertSame(PosSale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(PosSale::PAYMENT_CARD, $sale->payment_method);
        $this->assertNotNull($sale->journal_entry_id);
        $this->assertNotNull($sale->whatsapp_invoice_notified_at);
        $this->assertNull($sale->whatsapp_delivered_notified_at);
    }

    #[Test]
    public function merchant_can_download_store_invoice_pdf(): void
    {
        $saleId = $this->placeCodOrder();

        $this->actingAs($this->tenant)
            ->get(route('pos.orders.invoice.pdf', ['pos_sale' => $saleId]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function customer_can_download_signed_portal_invoice_pdf(): void
    {
        $saleId = $this->placeCodOrder();

        $url = URL::temporarySignedRoute(
            'store.portal.invoice.pdf',
            now()->addHour(),
            ['tenant_slug' => $this->slug, 'saleId' => $saleId],
        );

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function placeCodOrder(): int
    {
        $this->makePosDevice();
        $product = PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'WhatsApp Product',
            'cost_price' => 5,
            'sale_price' => 50,
            'vat_percent' => 0,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'is_active' => true,
            'is_published_online' => true,
        ]);

        $checkout = $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'عميل واتساب',
            'customer_phone' => '0501111111',
            'customer_address' => 'الرياض',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        return (int) $checkout->json('order.id');
    }
}
