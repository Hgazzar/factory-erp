<?php

declare(strict_types=1);

namespace App\Contracts\Core\Payment;

use App\Core\Payment\PaymentChargeResult;
use App\Core\Payment\PaymentWebhookResult;
use App\Models\TenantStoreSetting;

interface PaymentGatewayInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @param  array{name:string, phone:string}  $customer
     */
    public function charge(
        int $tenantUserId,
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): PaymentChargeResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(int $tenantUserId, TenantStoreSetting $settings, array $payload): ?PaymentWebhookResult;
}
