<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\AuditLog;
use App\Models\PosDevice;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Services\Pos\PosSaleService;
use InvalidArgumentException;

final class StoreCheckoutService
{
    public function __construct(
        private readonly PosSaleService $posSales,
        private readonly StoreCartQuoteService $cartQuote,
        private readonly StoreCouponService $coupons,
    ) {}

    /**
     * @param  array{name:string, phone:string, address:string}  $customer
     * @param  list<array{pos_product_id:int, quantity:float|int|string}>  $lines
     */
    public function placeOnlineOrder(int $tenantUserId, array $customer, array $lines, ?string $couponCode = null): PosSale
    {
        $name = trim((string) ($customer['name'] ?? ''));
        $phone = trim((string) ($customer['phone'] ?? ''));
        $address = trim((string) ($customer['address'] ?? ''));

        if ($name === '' || $phone === '' || $address === '') {
            throw new InvalidArgumentException('الاسم والهاتف والعنوان مطلوبة لإتمام الطلب.');
        }

        if ($lines === []) {
            throw new InvalidArgumentException('السلة فارغة.');
        }

        $settings = TenantStoreSetting::forTenant($tenantUserId);
        if (! $settings->is_store_enabled) {
            throw new InvalidArgumentException('المتجر الإلكتروني غير متاح حالياً.');
        }

        $device = $this->resolveDevice($tenantUserId, $settings);

        $quote = $this->cartQuote->quote($tenantUserId, $lines, $couponCode);

        $sale = $this->posSales->processSale($tenantUserId, [
            'pos_device_id' => $device->id,
            'payment_method' => PosSale::PAYMENT_COD,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'coupon_code' => $quote['coupon']['code'] ?? null,
            'discount_amount' => $quote['discount'],
            'lines' => $quote['sale_lines'],
        ]);

        if ($quote['coupon_model'] instanceof \App\Models\StoreCoupon) {
            $this->coupons->incrementUsage($quote['coupon_model']);
        }

        AuditLog::logModuleEvent('online_store_order_placed', [
            'pos_sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'total_amount' => (string) $sale->total_amount,
        ], $sale);

        return $sale->fresh(['items.product']);
    }

    private function resolveDevice(int $tenantUserId, TenantStoreSetting $settings): PosDevice
    {
        if ($settings->default_pos_device_id) {
            $device = PosDevice::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey((int) $settings->default_pos_device_id)
                ->where('status', PosDevice::STATUS_ACTIVE)
                ->first();

            if ($device !== null) {
                return $device;
            }
        }

        $device = PosDevice::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('status', PosDevice::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();

        if ($device === null) {
            throw new InvalidArgumentException('لا يوجد جهاز نقطة بيع نشط لمعالجة طلبات المتجر.');
        }

        return $device;
    }

}
