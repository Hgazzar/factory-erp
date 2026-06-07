<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Services\Pos\PosSaleService;
use App\Services\Store\Payment\PaymentChargeResult;
use App\Services\Store\Payment\PaymentGatewayRegistry;
use App\Services\Store\Payment\PaymobGateway;
use App\Services\Store\Payment\StorePaymentMethodResolver;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class StoreCheckoutService
{
    public function __construct(
        private readonly PosSaleService $posSales,
        private readonly StoreCartQuoteService $cartQuote,
        private readonly StoreCouponService $coupons,
        private readonly PaymentGatewayRegistry $gateways,
        private readonly StorePaymentMethodResolver $paymentMethods,
        private readonly StorePaymentReceiptService $receipts,
        private readonly StoreOrderWhatsAppNotifier $whatsappNotifier,
    ) {}

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
    ): PosSale {
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

        $paymentMethod = strtolower(trim($paymentMethod));
        if ($paymentMethod === 'card') {
            $paymentMethod = PosSale::PAYMENT_CARD;
        }

        $this->paymentMethods->assertAllowed($tenantUserId, $settings, $paymentMethod);

        if ($this->paymentMethods->requiresReceipt($paymentMethod) && $paymentReceipt === null) {
            throw new InvalidArgumentException('يرجى رفع صورة إيصال التحويل البنكي.');
        }

        $device = app(StoreOnlinePosDeviceService::class)->resolveOrCreate($tenantUserId, $settings);
        $quote = $this->cartQuote->quote($tenantUserId, $lines, $couponCode);

        $gatewayReference = null;
        $initialStatus = null;
        $receiptPath = null;

        if ($paymentMethod === PosSale::PAYMENT_CARD) {
            $currency = CompanySetting::resolvedCurrencyCode($tenantUserId);
            $draftInvoice = PosSale::nextInvoiceNumber($tenantUserId);
            /** @var PaymobGateway $paymob */
            $paymob = $this->gateways->get('paymob');
            $charge = $paymob->charge(
                $tenantUserId,
                $settings,
                (float) $quote['total'],
                $currency,
                ['name' => $name, 'phone' => $phone],
                $draftInvoice,
            );
            $gatewayReference = $charge->reference;
            $initialStatus = $charge->isCompleted()
                ? PosSale::STATUS_COMPLETED
                : PosSale::STATUS_PENDING;
        } elseif ($paymentMethod === PosSale::PAYMENT_MANUAL_TRANSFER) {
            $receiptPath = $this->receipts->store($tenantUserId, $paymentReceipt);
            $gatewayReference = 'MANUAL-'.PosSale::nextInvoiceNumber($tenantUserId);
            $initialStatus = PosSale::STATUS_PENDING_VERIFICATION;
        }

        $sale = $this->posSales->processSale($tenantUserId, [
            'pos_device_id' => $device->id,
            'payment_method' => $paymentMethod,
            'sale_channel' => PosSale::CHANNEL_ONLINE_STORE,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'coupon_code' => $quote['coupon']['code'] ?? null,
            'discount_amount' => $quote['discount'],
            'payment_gateway_reference' => $gatewayReference,
            'payment_receipt_path' => $receiptPath,
            'initial_status' => $initialStatus,
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
            'payment_method' => $paymentMethod,
            'status' => $sale->status,
            'total_amount' => (string) $sale->total_amount,
        ], $sale);

        $this->whatsappNotifier->dispatchOrderReceived($tenantUserId, (int) $sale->id);

        if ($sale->status === PosSale::STATUS_COMPLETED) {
            $this->whatsappNotifier->dispatchInvoiceNotification($tenantUserId, (int) $sale->id);
        }

        return $sale->fresh(['items.product']);
    }

    /**
     * @return list<array{key:string, label:string, requires_receipt:bool}>
     */
    public function availablePaymentMethods(int $tenantUserId): array
    {
        $settings = TenantStoreSetting::forTenant($tenantUserId);

        return $this->paymentMethods->availableMethods($tenantUserId, $settings);
    }
}
