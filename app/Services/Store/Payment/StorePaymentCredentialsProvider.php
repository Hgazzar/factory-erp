<?php

declare(strict_types=1);

namespace App\Services\Store\Payment;

use App\Contracts\Core\Payment\PaymentCredentialsProvider;
use App\Models\TenantStoreSetting;
use InvalidArgumentException;

final class StorePaymentCredentialsProvider implements PaymentCredentialsProvider
{
    public function paymobWebhookSettings(object $context): array
    {
        if (! $context instanceof TenantStoreSetting) {
            throw new InvalidArgumentException('Expected TenantStoreSetting.');
        }

        $mode = strtolower(trim((string) ($context->online_payment_mode ?: 'sandbox')));
        $liveMode = $mode === 'live' && ! (bool) config('store.payment.sandbox', true);

        return [
            'hmac_secret' => trim((string) $context->paymob_hmac_secret),
            'live_mode' => $liveMode,
        ];
    }
}
