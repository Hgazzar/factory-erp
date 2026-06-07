<?php

declare(strict_types=1);

namespace App\Services\Store\Payment;

use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;

final class StorePaymentMethodResolver
{
    /**
     * @return list<array{key:string, label:string, requires_receipt:bool}>
     */
    public function availableMethods(int $tenantUserId, TenantStoreSetting $settings): array
    {
        $country = strtoupper(trim((string) (
            CompanySetting::forTenant($tenantUserId)?->country_code
            ?: config('store.payment.default_country_code', 'SA')
        )));

        $allowedByCountry = config('store.payment.methods_by_country.'.$country)
            ?? config('store.payment.methods_by_country.default', ['cod', 'manual_transfer']);

        $methods = [];

        if (($settings->cod_enabled ?? true) && in_array('cod', $allowedByCountry, true)) {
            $methods[] = [
                'key' => PosSale::PAYMENT_COD,
                'label' => 'الدفع عند الاستلام',
                'requires_receipt' => false,
            ];
        }

        if ($settings->manual_transfer_enabled && in_array('manual_transfer', $allowedByCountry, true)) {
            $methods[] = [
                'key' => PosSale::PAYMENT_MANUAL_TRANSFER,
                'label' => 'تحويل بنكي (رفع إيصال)',
                'requires_receipt' => true,
            ];
        }

        if ($settings->acceptsOnlineCardPayments() && in_array('card', $allowedByCountry, true)) {
            $methods[] = [
                'key' => PosSale::PAYMENT_CARD,
                'label' => $settings->paymentProviderLabel(),
                'requires_receipt' => false,
            ];
        }

        if ($settings->tamara_enabled && in_array('tamara', $allowedByCountry, true)) {
            $methods[] = [
                'key' => 'tamara',
                'label' => 'Tamara — ادفع لاحقاً',
                'requires_receipt' => false,
            ];
        }

        if ($settings->tabby_enabled && in_array('tabby', $allowedByCountry, true)) {
            $methods[] = [
                'key' => 'tabby',
                'label' => 'Tabby — تقسيط',
                'requires_receipt' => false,
            ];
        }

        return $methods;
    }

    public function assertAllowed(int $tenantUserId, TenantStoreSetting $settings, string $paymentMethod): void
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        $allowed = array_column($this->availableMethods($tenantUserId, $settings), 'key');

        if (! in_array($paymentMethod, $allowed, true)) {
            throw new \InvalidArgumentException('طريقة الدفع غير متاحة لمتجرك أو لبلدك.');
        }
    }

    public function requiresReceipt(string $paymentMethod): bool
    {
        return strtolower(trim($paymentMethod)) === PosSale::PAYMENT_MANUAL_TRANSFER;
    }
}
