<?php

declare(strict_types=1);

namespace App\Core\Payment;

final class PaymobWebhookAuthenticator
{
    public function __construct(
        private readonly PaymobHmacVerifier $hmacVerifier,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function authenticate(
        array $payload,
        ?string $incomingHmac,
        string $hmacSecret,
        bool $liveMode,
    ): PaymobWebhookAuthResult {
        $secret = trim($hmacSecret);

        if ($secret === '') {
            if ($liveMode) {
                return PaymobWebhookAuthResult::rejected(
                    PaymobWebhookAuthResult::REASON_SECRET_NOT_CONFIGURED,
                );
            }

            return PaymobWebhookAuthResult::allowed();
        }

        if (! $this->hmacVerifier->verify($payload, $incomingHmac, $secret)) {
            return PaymobWebhookAuthResult::rejected(
                PaymobWebhookAuthResult::REASON_INVALID_SIGNATURE,
            );
        }

        return PaymobWebhookAuthResult::allowed();
    }
}
