<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Contracts\Core\Payment\PaymentCredentialsProvider;
use App\Core\Payment\PaymobGateway;
use App\Core\Payment\PaymobWebhookAuthenticator;
use App\Http\Controllers\Controller;
use App\Models\TenantStoreSetting;
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
 * @see \App\Core\Payment\PaymobHmacVerifier
 */
final class StorePaymobWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StorePaymobWebhookService $webhooks,
        PaymobGateway $paymob,
        PaymobWebhookAuthenticator $authenticator,
        PaymentCredentialsProvider $credentials,
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

        if ($response = $this->ensureAuthenticPaymobRequest($request, $settings, $payload, $authenticator, $credentials)) {
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
        PaymobWebhookAuthenticator $authenticator,
        PaymentCredentialsProvider $credentials,
    ): ?JsonResponse {
        $webhookSettings = $credentials->paymobWebhookSettings($settings);
        $incomingHmac = $request->query('hmac') ?? $request->input('hmac');

        $auth = $authenticator->authenticate(
            $payload,
            is_string($incomingHmac) ? $incomingHmac : null,
            $webhookSettings['hmac_secret'],
            $webhookSettings['live_mode'],
        );

        if ($auth->allowed) {
            return null;
        }

        Log::warning('Paymob webhook rejected', [
            'tenant_user_id' => $settings->tenant_user_id,
            'reason' => $auth->failureReason,
            'reference' => $payload['obj']['id'] ?? $payload['id'] ?? null,
        ]);

        return response()->json(['ok' => false, 'message' => $auth->failureReason], $auth->httpStatus);
    }
}
