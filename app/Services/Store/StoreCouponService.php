<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\StoreCoupon;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class StoreCouponService
{
    public function findValid(int $tenantUserId, string $code): StoreCoupon
    {
        $code = Str::upper(trim($code));
        if ($code === '') {
            throw new InvalidArgumentException('أدخل كود الخصم.');
        }

        $coupon = StoreCoupon::query()
            ->where('tenant_user_id', $tenantUserId)
            ->where('code', $code)
            ->first();

        if ($coupon === null || ! $coupon->is_active) {
            throw new InvalidArgumentException('كود الخصم غير صالح.');
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw new InvalidArgumentException('كود الخصم لم يبدأ بعد.');
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            throw new InvalidArgumentException('انتهت صلاحية كود الخصم.');
        }

        if ($coupon->max_uses !== null && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            throw new InvalidArgumentException('تم استنفاد استخدامات هذا الكود.');
        }

        return $coupon;
    }

    public function calculateDiscount(StoreCoupon $coupon, float $cartSubtotal): float
    {
        $cartSubtotal = round($cartSubtotal, 4);

        if ($cartSubtotal + 0.0001 < (float) $coupon->min_cart_subtotal) {
            throw new InvalidArgumentException(
                'الحد الأدنى للسلة '.number_format((float) $coupon->min_cart_subtotal, 2).' ر.س'
            );
        }

        $discount = match ($coupon->type) {
            StoreCoupon::TYPE_PERCENT => round($cartSubtotal * ((float) $coupon->value / 100), 4),
            StoreCoupon::TYPE_FIXED => round((float) $coupon->value, 4),
            default => 0.0,
        };

        return min($discount, $cartSubtotal);
    }

    public function incrementUsage(StoreCoupon $coupon): void
    {
        $coupon->increment('used_count');
    }
}
