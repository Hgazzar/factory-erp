<?php

declare(strict_types=1);

namespace App\Core\Payment;

use App\Contracts\Core\Payment\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->key()] = $gateway;
    }

    public function get(string $key): PaymentGatewayInterface
    {
        $normalized = strtolower(trim($key));

        if (! isset($this->gateways[$normalized])) {
            throw new InvalidArgumentException("بوابة الدفع «{$key}» غير مسجّلة.");
        }

        return $this->gateways[$normalized];
    }

    /**
     * @return list<PaymentGatewayInterface>
     */
    public function all(): array
    {
        return array_values($this->gateways);
    }

    public function has(string $key): bool
    {
        return isset($this->gateways[strtolower(trim($key))]);
    }
}
