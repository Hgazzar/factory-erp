<?php

declare(strict_types=1);

namespace App\Core\Payment;

final class PaymobWebhookAuthResult
{
    public const REASON_SECRET_NOT_CONFIGURED = 'hmac secret not configured';

    public const REASON_INVALID_SIGNATURE = 'invalid signature';

    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $failureReason = null,
        public readonly int $httpStatus = 401,
    ) {}

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function rejected(string $reason, int $httpStatus = 401): self
    {
        return new self(false, $reason, $httpStatus);
    }
}
