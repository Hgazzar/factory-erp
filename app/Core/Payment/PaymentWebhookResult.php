<?php

declare(strict_types=1);

namespace App\Core\Payment;

class PaymentWebhookResult
{
    public function __construct(
        public readonly string $gatewayReference,
        public readonly bool $success,
        public readonly ?string $failureReason = null,
    ) {}
}
