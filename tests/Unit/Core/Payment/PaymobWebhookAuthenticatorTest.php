<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment;

use App\Core\Payment\PaymobHmacVerifier;
use App\Core\Payment\PaymobWebhookAuthResult;
use App\Core\Payment\PaymobWebhookAuthenticator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PaymobWebhookAuthenticatorTest extends TestCase
{
    private PaymobWebhookAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticator = new PaymobWebhookAuthenticator(new PaymobHmacVerifier);
    }

    #[Test]
    public function sandbox_allows_missing_hmac_secret(): void
    {
        $result = $this->authenticator->authenticate(
            ['type' => 'TRANSACTION', 'obj' => ['id' => '1', 'success' => true]],
            null,
            '',
            liveMode: false,
        );

        $this->assertTrue($result->allowed);
    }

    #[Test]
    public function live_mode_rejects_missing_hmac_secret(): void
    {
        $result = $this->authenticator->authenticate(
            ['type' => 'TRANSACTION', 'obj' => ['id' => '1', 'success' => true]],
            null,
            '',
            liveMode: true,
        );

        $this->assertFalse($result->allowed);
        $this->assertSame(
            PaymobWebhookAuthResult::REASON_SECRET_NOT_CONFIGURED,
            $result->failureReason,
        );
    }

    #[Test]
    public function live_mode_rejects_invalid_signature(): void
    {
        $result = $this->authenticator->authenticate(
            ['type' => 'TRANSACTION', 'obj' => ['id' => '1', 'success' => true]],
            'bad-signature',
            'secret-key',
            liveMode: true,
        );

        $this->assertFalse($result->allowed);
        $this->assertSame(
            PaymobWebhookAuthResult::REASON_INVALID_SIGNATURE,
            $result->failureReason,
        );
    }

    #[Test]
    public function live_mode_accepts_valid_signature(): void
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

        $result = $this->authenticator->authenticate(
            ['type' => 'TRANSACTION', 'obj' => $obj],
            $hmac,
            $secret,
            liveMode: true,
        );

        $this->assertTrue($result->allowed);
    }
}
