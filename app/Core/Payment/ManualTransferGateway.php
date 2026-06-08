<?php

declare(strict_types=1);

namespace App\Core\Payment;

use App\Contracts\Core\Payment\PaymentGatewayInterface;
use App\Models\TenantStoreSetting;
use InvalidArgumentException;

/**
 * تحويل بنكي يدوي — لا يوجد charge فعلي؛ يُنشأ الطلب بحالة pending_verification.
 */
class ManualTransferGateway implements PaymentGatewayInterface
{
    public function key(): string
    {
        return 'manual_transfer';
    }

    public function label(): string
    {
        return 'تحويل بنكي';
    }

    public function charge(
        int $tenantUserId,
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): PaymentChargeResult {
        if (! $settings->manual_transfer_enabled) {
            throw new InvalidArgumentException('التحويل البنكي غير مفعّل.');
        }

        return new PaymentChargeResult(
            reference: 'MANUAL-'.$invoiceNumber,
            provider: $this->key(),
            status: PaymentChargeResult::STATUS_PENDING,
        );
    }

    public function handleWebhook(int $tenantUserId, TenantStoreSetting $settings, array $payload): ?PaymentWebhookResult
    {
        return null;
    }
}
