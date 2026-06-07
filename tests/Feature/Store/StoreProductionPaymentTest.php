<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Jobs\Store\SendStoreOrderInvoiceWhatsAppJob;
use App\Jobs\Store\SendStoreOrderReceivedWhatsAppJob;
use App\Models\CompanySetting;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Services\Store\Payment\PaymobHmacVerifier;
use App\Support\PremiumFeatureKeys;
use App\Support\StoreFeatureKeys;
use Database\Seeders\SystemModuleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\PosTestCase;

final class StoreProductionPaymentTest extends PosTestCase
{
    private string $slug = 'production-store';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
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
            'cod_enabled' => true,
            'manual_transfer_enabled' => true,
            'online_payment_enabled' => true,
            'online_payment_mode' => 'sandbox',
        ]);

        CompanySetting::query()->updateOrCreate(
            ['user_id' => $tenantId],
            ['country_code' => 'SA'],
        );

        config([
            'store.whatsapp.enabled' => false,
            'store.payment.sandbox' => true,
        ]);
    }

    #[Test]
    public function manual_transfer_checkout_stores_receipt_and_pending_verification(): void
    {
        $this->makePosDevice();
        $product = $this->makePublishedProduct();

        $response = $this->post(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'محوّل',
            'customer_phone' => '0505555555',
            'customer_address' => 'الرياض',
            'payment_method' => 'manual_transfer',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertCreated();

        $sale = PosSale::withoutGlobalScopes()->findOrFail((int) $response->json('order.id'));
        $this->assertSame(PosSale::STATUS_PENDING_VERIFICATION, $sale->status);
        $this->assertSame(PosSale::PAYMENT_MANUAL_TRANSFER, $sale->payment_method);
        $this->assertNotNull($sale->payment_receipt_path);
        Storage::disk('public')->assertExists($sale->payment_receipt_path);
    }

    #[Test]
    public function merchant_can_verify_manual_transfer_and_collect(): void
    {
        $saleId = $this->placeManualTransferOrder();

        $this->actingAs($this->tenant)
            ->post(route('pos.orders.update-status', ['pos_sale' => $saleId]), ['status' => 'collected'])
            ->assertRedirect();

        $sale = PosSale::withoutGlobalScopes()->findOrFail($saleId);
        $this->assertSame(PosSale::STATUS_COLLECTED, $sale->status);
        $this->assertNotNull($sale->journal_entry_id);
    }

    #[Test]
    public function paymob_webhook_completes_pending_card_order(): void
    {
        $this->makePosDevice();
        $product = $this->makePublishedProduct();

        $hmacSecret = 'paymob-test-hmac-secret';

        TenantStoreSetting::forTenant((int) $this->tenant->id)->update([
            'online_payment_mode' => 'live',
            'online_payment_secret_key' => 'test-secret',
            'paymob_hmac_secret' => $hmacSecret,
        ]);

        $sale = PosSale::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'pos_device_id' => $this->makePosDevice()->id,
            'receipt_number' => PosSale::nextReceiptNumber((int) $this->tenant->id),
            'invoice_number' => PosSale::nextInvoiceNumber((int) $this->tenant->id),
            'total_price' => 50,
            'subtotal_amount' => 50,
            'vat_amount' => 0,
            'total_amount' => 50,
            'cogs_amount' => 5,
            'payment_method' => PosSale::PAYMENT_CARD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'customer_name' => 'Webhook',
            'customer_phone' => '0506666666',
            'customer_address' => 'جدة',
            'status' => PosSale::STATUS_PENDING,
            'payment_gateway_reference' => 'PAYMOB-WH-123',
        ]);

        PosProduct::query()->whereKey($product->id)->decrement('current_quantity', 1);

        $transactionObj = [
            'amount_cents' => 5000,
            'created_at' => '2026-06-01T12:00:00.000000',
            'currency' => 'SAR',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => 'PAYMOB-WH-123',
            'integration_id' => 12345,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => ['id' => 987654],
            'owner' => 1,
            'pending' => false,
            'source_data' => [
                'pan' => '2346',
                'sub_type' => 'MasterCard',
                'type' => 'card',
            ],
            'success' => true,
        ];

        $hmac = app(PaymobHmacVerifier::class)->computeTransactionHmac($transactionObj, $hmacSecret);

        $this->postJson(route('store.webhooks.paymob', ['hmac' => $hmac]), [
            'type' => 'TRANSACTION',
            'obj' => $transactionObj,
        ])->assertOk();

        $sale->refresh();
        $this->assertSame(PosSale::STATUS_COMPLETED, $sale->status);
        $this->assertNotNull($sale->journal_entry_id);
    }

    #[Test]
    public function paymob_webhook_rejects_invalid_hmac(): void
    {
        $this->makePosDevice();
        $product = $this->makePublishedProduct();

        TenantStoreSetting::forTenant((int) $this->tenant->id)->update([
            'online_payment_mode' => 'live',
            'online_payment_secret_key' => 'test-secret',
            'paymob_hmac_secret' => 'paymob-test-hmac-secret',
        ]);

        PosSale::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'pos_device_id' => $this->makePosDevice()->id,
            'receipt_number' => PosSale::nextReceiptNumber((int) $this->tenant->id),
            'invoice_number' => PosSale::nextInvoiceNumber((int) $this->tenant->id),
            'total_price' => 50,
            'subtotal_amount' => 50,
            'vat_amount' => 0,
            'total_amount' => 50,
            'cogs_amount' => 5,
            'payment_method' => PosSale::PAYMENT_CARD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'customer_name' => 'Webhook',
            'customer_phone' => '0506666666',
            'customer_address' => 'جدة',
            'status' => PosSale::STATUS_PENDING,
            'payment_gateway_reference' => 'PAYMOB-WH-INVALID',
        ]);

        PosProduct::query()->whereKey($product->id)->decrement('current_quantity', 1);

        $this->postJson(route('store.webhooks.paymob', ['hmac' => 'invalid-signature']), [
            'type' => 'TRANSACTION',
            'obj' => [
                'id' => 'PAYMOB-WH-INVALID',
                'success' => true,
            ],
        ])->assertUnauthorized();
    }

    #[Test]
    public function checkout_dispatches_whatsapp_jobs_to_queue(): void
    {
        Queue::fake();

        $this->makePosDevice();
        $product = $this->makePublishedProduct();

        $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'Queue',
            'customer_phone' => '0507777777',
            'customer_address' => 'الدمام',
            'payment_method' => 'cod',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        Queue::assertPushed(SendStoreOrderReceivedWhatsAppJob::class);
        Queue::assertNotPushed(SendStoreOrderInvoiceWhatsAppJob::class);
    }

    #[Test]
    public function card_checkout_dispatches_received_and_invoice_jobs(): void
    {
        Queue::fake();

        $this->makePosDevice();
        $product = $this->makePublishedProduct();

        $this->postJson(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'Card Queue',
            'customer_phone' => '0508888888',
            'customer_address' => 'مكة',
            'payment_method' => 'card',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        Queue::assertPushed(SendStoreOrderReceivedWhatsAppJob::class);
        Queue::assertPushed(SendStoreOrderInvoiceWhatsAppJob::class);
    }

    #[Test]
    public function payment_methods_filtered_by_country(): void
    {
        CompanySetting::forTenant((int) $this->tenant->id)?->update(['country_code' => 'EG']);

        $response = $this->getJson(route('store.portal.api.payment-methods', ['tenant_slug' => $this->slug]))
            ->assertOk();

        $keys = collect($response->json('methods'))->pluck('key')->all();
        $this->assertContains('cod', $keys);
        $this->assertContains('card', $keys);
        $this->assertNotContains('tamara', $keys);
        $this->assertNotContains('tabby', $keys);
    }

    private function makePublishedProduct(): PosProduct
    {
        return PosProduct::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'Prod',
            'cost_price' => 5,
            'sale_price' => 50,
            'vat_percent' => 0,
            'opening_quantity' => 10,
            'current_quantity' => 10,
            'is_active' => true,
            'is_published_online' => true,
        ]);
    }

    private function placeManualTransferOrder(): int
    {
        $product = $this->makePublishedProduct();
        $this->makePosDevice();

        $response = $this->post(route('store.portal.api.checkout', ['tenant_slug' => $this->slug]), [
            'customer_name' => 'Manual',
            'customer_phone' => '0509999999',
            'customer_address' => 'الرياض',
            'payment_method' => 'manual_transfer',
            'lines' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_receipt' => UploadedFile::fake()->image('transfer.png'),
        ])->assertCreated();

        return (int) $response->json('order.id');
    }
}
