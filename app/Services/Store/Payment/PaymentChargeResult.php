<?php

declare(strict_types=1);

namespace App\Services\Store\Payment;

final class PaymentChargeResult
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    public function __construct(
        public readonly string $reference,
        public readonly string $provider,
        public readonly string $status = self::STATUS_COMPLETED,
        public readonly ?string $redirectUrl = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
