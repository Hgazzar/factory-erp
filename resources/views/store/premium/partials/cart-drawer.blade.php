<div class="ak-drawer-backdrop" :class="{ 'is-open': cartOpen }" @click="closeCart()" x-cloak></div>
<aside class="ak-drawer" :class="{ 'is-open': cartOpen }" role="dialog" aria-label="سلة التسوق" x-cloak>
    <div class="ak-drawer__head">
        <div>
            <p class="ak-eyebrow" style="margin:0">سلتك</p>
            <h2 class="ak-section-title" style="font-size:1.125rem" x-text="cartCount + ' منتج'"></h2>
        </div>
        <button type="button" class="ak-icon-btn" @click="closeCart()" aria-label="إغلاق">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="ak-drawer__body">
        <template x-if="!cartLines.length">
            <div style="text-align:center;padding:var(--ak-16) 0">
                <p class="ak-body-lg" style="margin-bottom:var(--ak-6)">سلتك فارغة</p>
                <a href="{{ $routes['shop'] }}" class="ak-btn ak-btn--primary" @click="closeCart()">تسوق الآن</a>
            </div>
        </template>

        <template x-for="line in cartLines" :key="line.id">
            <div class="ak-cart-line">
                <img class="ak-cart-line__img" :src="line.image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=200&q=80'" alt="">
                <div style="flex:1">
                    <div style="font-weight:500;font-size:0.9375rem;margin-bottom:var(--ak-2)" x-text="line.name"></div>
                    <div class="ak-price" style="color:var(--ak-gold)" x-text="formatMoney(line.sale_price)"></div>
                    <div class="ak-qty" style="margin-top:var(--ak-3)">
                        <button type="button" @click="updateQty(line.id, line.quantity - 1)">−</button>
                        <input type="number" min="1" :value="line.quantity" @change="updateQty(line.id, $event.target.value)">
                        <button type="button" @click="updateQty(line.id, line.quantity + 1)">+</button>
                    </div>
                </div>
                <button type="button" class="ak-icon-btn" style="align-self:flex-start" @click="removeLine(line.id)" aria-label="حذف">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </button>
            </div>
        </template>

        <div x-show="quoteLoading" class="ak-skeleton" style="height:4rem;margin-top:var(--ak-4)"></div>
    </div>

    <div class="ak-drawer__foot" x-show="cartLines.length">
        <div style="display:flex;justify-content:space-between;margin-bottom:var(--ak-2);font-size:0.875rem">
            <span class="ak-caption">المجموع الفرعي</span>
            <span x-text="quote ? formatMoney(quote.subtotal) : '—'"></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:var(--ak-4);font-size:0.875rem" x-show="quote && quote.discount > 0">
            <span class="ak-caption">الخصم</span>
            <span style="color:#059669" x-text="'−' + formatMoney(quote.discount)"></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:600;font-size:1.0625rem;margin-bottom:var(--ak-6)">
            <span>الإجمالي</span>
            <span x-text="quote ? formatMoney(quote.total) : '—'"></span>
        </div>
        <a :href="'{{ $routes['checkout'] }}'" class="ak-btn ak-btn--primary ak-btn--block" @click="closeCart()">إتمام الطلب</a>
    </div>
</aside>
