<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment;

use App\Core\Payment\PaymobHmacVerifier;
use App\Core\Payment\PaymobWebhookAuthResult;
use App\Core\Payment\PaymobWebhookAuthenticator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PaymobHmacVerifierTest extends TestCase
{
    #[Test]
    public function transaction_hmac_matches_paymob_field_order(): void
    {
        $obj = [
            'amount_cents' => 5000,
            'created_at' => '2026-06-01T12:00:00.000000',
            'currency' => 'SAR',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => 'PAYMOB-WH-123',
            'integration_id' => 12345,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => ['id' => 987654],
            'owner' => 1,
            'pending' => false,
            'source_data' => [
                'pan' => '2346',
                'sub_type' => 'MasterCard',
                'type' => 'card',
            ],
            'success' => true,
        ];

        $secret = 'paymob-test-hmac-secret';
        $verifier = new PaymobHmacVerifier;
        $hmac = $verifier->computeTransactionHmac($obj, $secret);

        $this->assertTrue($verifier->verify([
            'type' => 'TRANSACTION',
            'obj' => $obj,
        ], $hmac, $secret));
        $this->assertFalse($verifier->verify([
            'type' => 'TRANSACTION',
            'obj' => $obj,
        ], 'tampered', $secret));
    }
}
