<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosProduct;
use App\Models\StoreCoupon;
use InvalidArgumentException;

final class StoreCartQuoteService
{
    public function __construct(
        private readonly StoreCouponService $coupons,
    ) {}

    /**
     * @param  list<array{pos_product_id:int, quantity:float|int|string}>  $lines
     * @return array{
     *   lines:list<array{pos_product_id:int, quantity:float, unit_price:float, name:string, image_url:?string, line_subtotal:float, line_vat:float, line_total:float}>,
     *   subtotal:float,
     *   vat:float,
     *   discount:float,
     *   total:float,
     *   coupon:?array{code:string, type:string, value:float}
     * }
     */
    public function quote(int $tenantUserId, array $lines, ?string $couponCode = null): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('السلة فارغة.');
        }

        $prepared = [];
        $subtotal = 0.0;
        $vat = 0.0;

        foreach ($lines as $line) {
            $productId = (int) ($line['pos_product_id'] ?? 0);
            $qty = round((float) ($line['quantity'] ?? 0), 4);
            if ($productId < 1 || $qty <= 0) {
                throw new InvalidArgumentException('بيانات السلة غير صالحة.');
            }

            $product = PosProduct::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($productId)
                ->where('is_active', true)
                ->where('is_published_online', true)
                ->first();

            if ($product === null) {
                throw new InvalidArgumentException("المنتج #{$productId} غير متاح.");
            }

            if ((float) $product->current_quantity + 0.0001 < $qty) {
                throw new InvalidArgumentException("الكمية غير متوفرة للمنتج {$product->name}.");
            }

            $unit = round((float) $product->sale_price, 4);
            $lineSub = round($qty * $unit, 4);
            $lineVat = round($lineSub * ((float) $product->vat_percent / 100), 4);
            $lineTotal = round($lineSub + $lineVat, 4);

            $prepared[] = [
                'pos_product_id' => $productId,
                'quantity' => $qty,
                'unit_price' => $unit,
                'name' => (string) $product->name,
                'image_url' => $product->image_url,
                'line_subtotal' => $lineSub,
                'line_vat' => $lineVat,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineSub;
            $vat += $lineVat;
        }

        $subtotal = round($subtotal, 4);
        $vat = round($vat, 4);
        $grossBeforeDiscount = round($subtotal + $vat, 4);

        $discount = 0.0;
        $couponPayload = null;
        $coupon = null;

        if ($couponCode !== null && trim($couponCode) !== '') {
            $coupon = $this->coupons->findValid($tenantUserId, $couponCode);
            $discount = $this->coupons->calculateDiscount($coupon, $subtotal);
            $couponPayload = [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
            ];
        }

        $discount = round($discount, 4);
        $ratio = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 1.0;
        $adjustedVat = round($vat * $ratio, 4);
        $total = round(max(0, $subtotal - $discount + $adjustedVat), 4);

        $saleLines = [];
        foreach ($prepared as $row) {
            $lineDiscountShare = $subtotal > 0
                ? round($discount * ($row['line_subtotal'] / $subtotal), 4)
                : 0.0;
            $netSub = round($row['line_subtotal'] - $lineDiscountShare, 4);
            $netUnit = $row['quantity'] > 0 ? round($netSub / $row['quantity'], 4) : 0.0;

            $saleLines[] = [
                'pos_product_id' => $row['pos_product_id'],
                'quantity' => $row['quantity'],
                'unit_price' => $netUnit,
            ];
        }

        return [
            'display_lines' => $prepared,
            'sale_lines' => $saleLines,
            'subtotal' => $subtotal,
            'vat' => $adjustedVat,
            'discount' => $discount,
            'total' => $total,
            'coupon' => $couponPayload,
            'coupon_model' => $coupon,
        ];
    }
}
