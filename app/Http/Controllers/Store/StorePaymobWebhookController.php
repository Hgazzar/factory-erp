<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\TenantStoreSetting;
use App\Services\Store\Payment\PaymobGateway;
use App\Services\Store\Payment\PaymobHmacVerifier;
use App\Services\Store\StorePaymobWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paymob "Transaction Processed" webhook (POST).
 *
 * Merchant setup (Paymob Dashboard → Payment Integrations → your Integration ID):
 *   Processed callback URL: {APP_URL}/webhooks/store/paymob
 *   Example production:     https://your-domain.com/webhooks/store/paymob
 *
 * Copy the HMAC secret from the same integration page into:
 *   ERP → إعدادات المتجر → Paymob → HMAC Secret (Webhooks)
 *
 * Paymob sends the signature as query param ?hmac=… (SHA-512). Requests without a valid
 * HMAC are rejected in live mode. Route name: store.webhooks.paymob
 *
 * @see store_paymob_webhook_url()
 * @see PaymobHmacVerifier
 */
final class StorePaymobWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StorePaymobWebhookService $webhooks,
        PaymobGateway $paymob,
        PaymobHmacVerifier $hmacVerifier,
    ): JsonResponse {
        $payload = $request->all();
        $reference = (string) ($payload['obj']['id'] ?? $payload['id'] ?? $payload['transaction_id'] ?? '');

        if ($reference === '') {
            return response()->json(['ok' => false, 'message' => 'missing reference'], 422);
        }

        $tenantUserId = $webhooks->findTenantByGatewayReference($reference);
        if ($tenantUserId === null) {
            Log::warning('Paymob webhook: sale not found', ['reference' => $reference]);

            return response()->json(['ok' => true, 'message' => 'ignored']);
        }

        $settings = TenantStoreSetting::forTenant($tenantUserId);

        if ($response = $this->ensureAuthenticPaymobRequest($request, $settings, $payload, $hmacVerifier)) {
            return $response;
        }

        $result = $paymob->handleWebhook($tenantUserId, $settings, $payload);

        if ($result === null) {
            return response()->json(['ok' => false, 'message' => 'unhandled'], 422);
        }

        $webhooks->applyPaymentResult($tenantUserId, $result);

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureAuthenticPaymobRequest(
        Request $request,
        TenantStoreSetting $settings,
        array $payload,
        PaymobHmacVerifier $hmacVerifier,
    ): ?JsonResponse {
        $secret = trim((string) $settings->paymob_hmac_secret);
        $liveMode = $this->isLivePaymobMode($settings);

        if ($secret === '') {
            if ($liveMode) {
                Log::warning('Paymob webhook rejected: HMAC secret not configured', [
                    'tenant_user_id' => $settings->tenant_user_id,
                ]);

                return response()->json(['ok' => false, 'message' => 'hmac secret not configured'], 401);
            }

            return null;
        }

        $incomingHmac = $request->query('hmac') ?? $request->input('hmac');

        if (! $hmacVerifier->verify($payload, is_string($incomingHmac) ? $incomingHmac : null, $secret)) {
            Log::warning('Paymob webhook rejected: invalid HMAC signature', [
                'tenant_user_id' => $settings->tenant_user_id,
                'reference' => $payload['obj']['id'] ?? $payload['id'] ?? null,
            ]);

            return response()->json(['ok' => false, 'message' => 'invalid signature'], 401);
        }

        return null;
    }

    private function isLivePaymobMode(TenantStoreSetting $settings): bool
    {
        $mode = strtolower(trim((string) ($settings->online_payment_mode ?: 'sandbox')));

        return $mode === 'live' && ! (bool) config('store.payment.sandbox', true);
    }
}
