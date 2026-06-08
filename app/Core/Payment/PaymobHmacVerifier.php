<?php

declare(strict_types=1);

namespace App\Core\Payment;

class PaymobHmacVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function verify(array $payload, ?string $incomingHmac, string $secret): bool
    {
        $incomingHmac = trim((string) $incomingHmac);
        if ($incomingHmac === '') {
            return false;
        }

        $obj = $payload['obj'] ?? null;
        if (! is_array($obj)) {
            return false;
        }

        $type = strtoupper(trim((string) ($payload['type'] ?? 'TRANSACTION')));

        $calculated = match ($type) {
            'TOKEN' => $this->computeCardTokenHmac($obj, $secret),
            'DELIVERY_STATUS' => $this->computeDeliveryStatusHmac($obj, $secret),
            default => $this->computeTransactionHmac($obj, $secret),
        };

        return hash_equals($calculated, $incomingHmac);
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    public function computeTransactionHmac(array $obj, string $secret): string
    {
        $order = is_array($obj['order'] ?? null) ? $obj['order'] : [];
        $sourceData = is_array($obj['source_data'] ?? null) ? $obj['source_data'] : [];

        return $this->hashMessage($secret, [
            $obj['amount_cents'] ?? null,
            $obj['created_at'] ?? null,
            $obj['currency'] ?? null,
            $obj['error_occured'] ?? null,
            $obj['has_parent_transaction'] ?? null,
            $obj['id'] ?? null,
            $obj['integration_id'] ?? null,
            $obj['is_3d_secure'] ?? null,
            $obj['is_auth'] ?? null,
            $obj['is_capture'] ?? null,
            $obj['is_refunded'] ?? null,
            $obj['is_standalone_payment'] ?? null,
            $obj['is_voided'] ?? null,
            $order['id'] ?? null,
            $obj['owner'] ?? null,
            $obj['pending'] ?? null,
            $sourceData['pan'] ?? null,
            $sourceData['sub_type'] ?? null,
            $sourceData['type'] ?? null,
            $obj['success'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    public function computeCardTokenHmac(array $obj, string $secret): string
    {
        return $this->hashMessage($secret, [
            $obj['card_subtype'] ?? null,
            $obj['created_at'] ?? null,
            $obj['email'] ?? null,
            $obj['id'] ?? null,
            $obj['masked_pan'] ?? null,
            $obj['merchant_id'] ?? null,
            $obj['order_id'] ?? null,
            $obj['token'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    public function computeDeliveryStatusHmac(array $obj, string $secret): string
    {
        return $this->hashMessage($secret, [
            $obj['order_id'] ?? null,
            $obj['order_delivery_status'] ?? null,
            $obj['merchant_id'] ?? null,
            $obj['merchant_name'] ?? null,
            $obj['updated_at'] ?? null,
        ]);
    }

    /**
     * @param  list<mixed>  $values
     */
    private function hashMessage(string $secret, array $values): string
    {
        $message = '';

        foreach ($values as $value) {
            if (is_bool($value)) {
                $message .= $value ? 'true' : 'false';

                continue;
            }

            if ($value === null) {
                continue;
            }

            $message .= (string) $value;
        }

        return hash_hmac('sha512', $message, $secret);
    }
}
