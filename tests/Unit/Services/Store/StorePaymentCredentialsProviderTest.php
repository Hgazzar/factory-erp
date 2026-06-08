<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Store;

use App\Services\Store\Payment\StorePaymentCredentialsProvider;
use App\Models\TenantStoreSetting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StorePaymentCredentialsProviderTest extends TestCase
{
    #[Test]
    public function resolves_live_mode_and_hmac_secret_from_store_settings(): void
    {
        config(['store.payment.sandbox' => false]);

        $settings = new TenantStoreSetting([
            'online_payment_mode' => 'live',
            'paymob_hmac_secret' => '  my-hmac  ',
        ]);

        $credentials = (new StorePaymentCredentialsProvider)->paymobWebhookSettings($settings);

        $this->assertSame('my-hmac', $credentials['hmac_secret']);
        $this->assertTrue($credentials['live_mode']);
    }

    #[Test]
    public function sandbox_config_overrides_live_payment_mode(): void
    {
        config(['store.payment.sandbox' => true]);

        $settings = new TenantStoreSetting([
            'online_payment_mode' => 'live',
            'paymob_hmac_secret' => 'secret',
        ]);

        $credentials = (new StorePaymentCredentialsProvider)->paymobWebhookSettings($settings);

        $this->assertFalse($credentials['live_mode']);
    }
}
