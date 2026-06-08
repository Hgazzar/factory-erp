<?php

declare(strict_types=1);

namespace App\Contracts\Core\Checkout;

use App\Models\PosSale;
use Illuminate\Http\UploadedFile;

/**
 * نقطة الدخول الوحيدة لـ checkout المتجر العام (كل النيشات).
 *
 * الدفع الإلكتروني (Paymob) والتحويل البنكي يمرّان عبر Store فقط —
 * Clinic/Nursery لا يكرّران بوابات الدفع (انظر CheckoutBoundaryCatalog).
 */
interface OnlineStoreCheckoutInterface
{
    /**
     * @param  array{name:string, phone:string, address:string}  $customer
     * @param  list<array{pos_product_id:int, quantity:float|int|string}>  $lines
     */
    public function placeOnlineOrder(
        int $tenantUserId,
        array $customer,
        array $lines,
        ?string $couponCode = null,
        string $paymentMethod = PosSale::PAYMENT_COD,
        ?UploadedFile $paymentReceipt = null,
    ): PosSale;

    /**
     * @return list<array{key:string, label:string, requires_receipt:bool}>
     */
    public function availablePaymentMethods(int $tenantUserId): array;
}
