<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\TenantStoreSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class StoreOnlinePaymentService
{
    /**
     * @param  array{name:string, phone:string}  $customer
     * @return array{reference:string, provider:string}
     */
    public function charge(
        int $tenantUserId,
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): array {
        if (! $settings->acceptsOnlineCardPayments()) {
            throw new InvalidArgumentException('الدفع الإلكتروني غير مفعّل في إعدادات المتجر.');
        }

        $provider = strtolower(trim((string) ($settings->online_payment_provider ?: config('store.payment.default_provider', 'paymob'))));
        if (! in_array($provider, config('store.payment.providers', ['paymob', 'stripe']), true)) {
            throw new InvalidArgumentException('مزود الدفع غير مدعوم.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الدفع غير صالح.');
        }

        $mode = strtolower(trim((string) ($settings->online_payment_mode ?: 'sandbox')));
        $sandbox = $mode === 'sandbox' || (bool) config('store.payment.sandbox', true);

        if ($sandbox) {
            $reference = strtoupper($provider).'-SANDBOX-'.Str::upper(Str::random(12));

            Log::info('Store online payment (sandbox): auto-approved charge', [
                'tenant_user_id' => $tenantUserId,
                'provider' => $provider,
                'amount' => $amount,
                'currency' => $currency,
                'invoice_number' => $invoiceNumber,
                'reference' => $reference,
            ]);

            return [
                'reference' => $reference,
                'provider' => $provider,
            ];
        }

        return match ($provider) {
            'paymob' => $this->chargePaymob($settings, $amount, $currency, $customer, $invoiceNumber),
            'stripe' => $this->chargeStripe($settings, $amount, $currency, $customer, $invoiceNumber),
            default => throw new InvalidArgumentException('مزود الدفع غير مدعوم.'),
        };
    }

    /**
     * @param  array{name:string, phone:string}  $customer
     * @return array{reference:string, provider:string}
     */
    private function chargePaymob(
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): array {
        $secret = trim((string) $settings->online_payment_secret_key);
        if ($secret === '') {
            throw new InvalidArgumentException('مفتاح Paymob السري غير مُعد.');
        }

        // Placeholder for live integration — extend with Paymob accept API when credentials are configured.
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

        return ['reference' => $reference, 'provider' => 'paymob'];
    }

    /**
     * @param  array{name:string, phone:string}  $customer
     * @return array{reference:string, provider:string}
     */
    private function chargeStripe(
        TenantStoreSetting $settings,
        float $amount,
        string $currency,
        array $customer,
        string $invoiceNumber,
    ): array {
        $secret = trim((string) $settings->online_payment_secret_key);
        if ($secret === '') {
            throw new InvalidArgumentException('مفتاح Stripe السري غير مُعد.');
        }

        $response = Http::withToken($secret)
            ->asForm()
            ->timeout(20)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($amount * 100),
                'currency' => strtolower($currency),
                'description' => 'Online store order '.$invoiceNumber,
                'metadata[customer_name]' => $customer['name'],
                'metadata[customer_phone]' => $customer['phone'],
            ]);

        if (! $response->successful()) {
            Log::error('Stripe charge failed', ['body' => $response->body(), 'status' => $response->status()]);
            throw new RuntimeException('تعذر إتمام الدفع عبر Stripe.');
        }

        $reference = (string) ($response->json('id') ?? '');
        if ($reference === '') {
            throw new RuntimeException('استجابة Stripe غير متوقعة.');
        }

        return ['reference' => $reference, 'provider' => 'stripe'];
    }
}
