<?php

declare(strict_types=1);

namespace App\Contracts\Core\Payment;

interface PaymentCredentialsProvider
{
    /**
     * @return array{hmac_secret: string, live_mode: bool}
     */
    public function paymobWebhookSettings(object $context): array;
}
