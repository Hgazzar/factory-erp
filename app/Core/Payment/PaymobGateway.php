<?php

declare(strict_types=1);

namespace App\Core\Payment;

use App\Contracts\Core\Payment\PaymentGatewayInterface;
use App\Models\TenantStoreSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PaymobGateway implements PaymentGatewayInterface
{
    public function key(): string
    {
        return 'paymob';
    }

    public function label(): string
    {
        return 'Paymob';
    }

    public function charge(
        int $tenantUserId,
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): PaymentChargeResult {
        if (! $settings->acceptsOnlineCardPayments()) {
            throw new InvalidArgumentException('الدفع الإلكتروني (Paymob) غير مفعّل.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الدفع غير صالح.');
        }

        $mode = strtolower(trim((string) ($settings->online_payment_mode ?: 'sandbox')));
        $sandbox = $mode === 'sandbox' || (bool) config('store.payment.sandbox', true);

        if ($sandbox) {
            $reference = 'PAYMOB-SANDBOX-'.Str::upper(Str::random(12));

            Log::info('Paymob gateway (sandbox): auto-approved', [
                'tenant_user_id' => $tenantUserId,
                'amount' => $amount,
                'currency' => $currency,
                'invoice_number' => $invoiceNumber,
                'reference' => $reference,
            ]);

            return new PaymentChargeResult($reference, $this->key(), PaymentChargeResult::STATUS_COMPLETED);
        }

        $secret = trim((string) $settings->online_payment_secret_key);
        if ($secret === '') {
            throw new InvalidArgumentException('مفتاح Paymob السري غير مُعد.');
        }

        $response = Http::withToken($secret)
            ->timeout(20)
            ->post('https://accept.paymob.com/api/acceptance/payments/charge', [
                'amount_cents' => (int) round($amount * 100),
                'currency' => strtoupper($currency),
                'order_ref' => $invoiceNumber,
                'billing_data' => [
                    'first_name' => $customer['name'],
                    'phone_number' => $customer['phone'],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Paymob charge failed', ['body' => $response->body(), 'status' => $response->status()]);
            throw new RuntimeException('تعذر إتمام الدفع عبر Paymob.');
        }

        $reference = (string) ($response->json('id') ?? $response->json('transaction_id') ?? '');
        if ($reference === '') {
            throw new RuntimeException('استجابة Paymob غير متوقعة.');
        }

        $pending = in_array(strtolower((string) $response->json('pending')), ['true', '1'], true)
            || strtolower((string) $response->json('success')) === 'false';

        return new PaymentChargeResult(
            $reference,
            $this->key(),
            $pending ? PaymentChargeResult::STATUS_PENDING : PaymentChargeResult::STATUS_COMPLETED,
        );
    }

    public function handleWebhook(int $tenantUserId, TenantStoreSetting $settings, array $payload): ?PaymentWebhookResult
    {
        $reference = (string) ($payload['obj']['id'] ?? $payload['id'] ?? $payload['transaction_id'] ?? '');
        if ($reference === '') {
            return null;
        }

        $success = filter_var(
            $payload['obj']['success'] ?? $payload['success'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        return new PaymentWebhookResult(
            gatewayReference: $reference,
            success: $success,
            failureReason: $success ? null : (string) ($payload['obj']['data']['message'] ?? $payload['message'] ?? 'فشل الدفع'),
        );
    }
}
