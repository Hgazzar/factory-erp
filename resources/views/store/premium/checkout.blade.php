@extends('layouts.store-premium')

@section('title', 'إتمام الطلب — '.$storeName)

@section('content')
<div class="ak-container ak-section"
     x-data="akCheckoutPage(@js([
         'slug' => $tenantSlug,
         'apiBase' => $apiBase,
         'currency' => $currencyCode,
         'routes' => $routes,
     ]))"
     x-init="init()">

    <div style="margin-bottom:var(--ak-10)">
        <p class="ak-eyebrow">الدفع</p>
        <h1 class="ak-section-title">إتمام الطلب</h1>
    </div>

    <div class="ak-checkout">
        <div style="display:flex;flex-direction:column;gap:var(--ak-6)">
            <section class="ak-panel">
                <h2 style="font-size:1rem;font-weight:600;margin:0 0 var(--ak-6)">بيانات العميل</h2>
                <div style="display:flex;flex-direction:column;gap:var(--ak-4)">
                    <div>
                        <label class="ak-caption" style="display:block;margin-bottom:var(--ak-2)">الاسم الكامل</label>
                        <input type="text" class="ak-input" x-model="form.customer_name" required autocomplete="name">
                    </div>
                    <div>
                        <label class="ak-caption" style="display:block;margin-bottom:var(--ak-2)">رقم الجوال</label>
                        <input type="tel" class="ak-input" x-model="form.customer_phone" dir="ltr" required autocomplete="tel">
                    </div>
                </div>
            </section>

            <section class="ak-panel">
                <h2 style="font-size:1rem;font-weight:600;margin:0 0 var(--ak-6)">عنوان الشحن</h2>
                <textarea class="ak-input" rows="4" x-model="form.customer_address" required placeholder="المدينة — الحي — الشارع — تفاصيل إضافية"></textarea>
            </section>

            <section class="ak-panel">
                <h2 style="font-size:1rem;font-weight:600;margin:0 0 var(--ak-4)">طريقة الدفع</h2>
                <div style="padding:var(--ak-4);background:var(--ak-gold-soft);border-radius:var(--ak-r-md);font-weight:500">
                    الدفع عند الاستلام (COD)
                </div>
                <div class="ak-trust">
                    <span>🔒 بياناتك محمية</span>
                    <span>✓ طلب آمن</span>
                </div>
            </section>
        </div>

        <aside class="ak-panel" style="position:sticky;top:calc(var(--ak-header-h) + var(--ak-6));align-self:start">
            <h2 style="font-size:1rem;font-weight:600;margin:0 0 var(--ak-6)">ملخص الطلب</h2>

            <template x-for="line in (quote?.lines || cartLines)" :key="line.pos_product_id || line.id">
                <div style="display:flex;justify-content:space-between;gap:var(--ak-4);padding:var(--ak-3) 0;border-bottom:1px solid var(--ak-line);font-size:0.875rem">
                    <span x-text="line.name" style="flex:1"></span>
                    <span class="ak-caption" x-text="line.quantity + ' × ' + formatMoney(line.unit_price ?? line.sale_price)"></span>
                </div>
            </template>

            <div style="display:flex;gap:var(--ak-2);margin:var(--ak-6) 0 var(--ak-4)">
                <input type="text" class="ak-input" style="flex:1" placeholder="كود الخصم" x-model="form.coupon_code">
                <button type="button" class="ak-btn ak-btn--ghost ak-btn--sm" @click="applyCoupon()">تطبيق</button>
            </div>
            <p x-show="couponMessage" style="color:#059669;font-size:0.8125rem;margin-bottom:var(--ak-4)" x-text="couponMessage"></p>
            <p x-show="couponError" style="color:#DC2626;font-size:0.8125rem;margin-bottom:var(--ak-4)" x-text="couponError"></p>

            <div style="border-top:1px solid var(--ak-line);padding-top:var(--ak-4);font-size:0.875rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:var(--ak-2)">
                    <span class="ak-caption">المجموع</span>
                    <span x-text="quote ? formatMoney(quote.subtotal) : '—'"></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:var(--ak-2)" x-show="quote && quote.discount > 0">
                    <span class="ak-caption">الخصم</span>
                    <span style="color:#059669" x-text="'−' + formatMoney(quote.discount)"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:600;font-size:1.125rem;margin-top:var(--ak-4)">
                    <span>الإجمالي</span>
                    <span x-text="quote ? formatMoney(quote.total) : '—'"></span>
                </div>
            </div>

            <button type="button" class="ak-btn ak-btn--primary ak-btn--block" style="margin-top:var(--ak-8)"
                    @click="submitOrder()" :disabled="submitting || quoteLoading">
                <span x-show="!submitting">تأكيد الطلب</span>
                <span x-show="submitting">جاري الإرسال...</span>
            </button>
        </aside>
    </div>
</div>
@endsection
