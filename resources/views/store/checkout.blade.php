@extends('layouts.store')

@section('title', 'إتمام الشراء — '.$storeName)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8"
     x-data="akCheckoutPage(@js([
         'slug' => $tenantSlug,
         'apiBase' => $apiBase,
         'currency' => $currencyCode,
         'routes' => $routes,
         'paymentMethods' => $paymentMethods ?? [],
         'fulfillmentOptions' => $fulfillmentOptions ?? [],
         'paymentSandbox' => $paymentSandbox ?? true,
     ]))"
     x-init="init()">

    <a href="{{ $routes['home'] }}" class="flex items-center gap-2 text-gray-500 hover:text-store-primary transition-colors mb-6">
        <i class="fas fa-arrow-right"></i>
        <span class="font-bold">العودة للتسوق</span>
    </a>

    <h2 class="text-3xl font-black text-gray-800 mb-8">إتمام <span class="gradient-text">الشراء</span></h2>

    <div class="flex items-center justify-between mb-10 px-4">
        <div class="checkout-step active text-center">
            <div class="step-circle w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center mx-auto mb-2 font-bold text-sm bg-white">1</div>
            <span class="text-xs text-gray-500 font-bold">معلومات التوصيل</span>
        </div>
        <div class="flex-1 h-0.5 bg-gray-200 mx-4"></div>
        <div class="checkout-step text-center">
            <div class="step-circle w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center mx-auto mb-2 font-bold text-sm bg-white">2</div>
            <span class="text-xs text-gray-500 font-bold">الدفع</span>
        </div>
        <div class="flex-1 h-0.5 bg-gray-200 mx-4"></div>
        <div class="checkout-step text-center">
            <div class="step-circle w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center mx-auto mb-2 font-bold text-sm bg-white">3</div>
            <span class="text-xs text-gray-500 font-bold">التأكيد</span>
        </div>
    </div>

    <div class="grid md:grid-cols-5 gap-8">
        <div class="md:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-store-primary"></i>
                    معلومات التوصيل
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-bold text-gray-600 mb-1 block">الاسم الكامل</label>
                        <input type="text" x-model="form.customer_name" required autocomplete="name"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-bold text-gray-600 mb-1 block">رقم الهاتف</label>
                        <input type="tel" x-model="form.customer_phone" dir="ltr" required autocomplete="tel"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all text-sm" placeholder="05xxxxxxxx">
                    </div>
                    <div>
                        <label class="text-sm font-bold text-gray-600 mb-1 block">العنوان</label>
                        <textarea rows="3" x-model="form.customer_address" required placeholder="الحي، الشارع، رقم المبنى"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all text-sm resize-none"></textarea>
                    </div>
                </div>

                <template x-if="fulfillmentOptions.length > 1">
                    <div class="mt-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-truck text-store-primary"></i>
                            طريقة التسليم
                        </h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="option in fulfillmentOptions" :key="option.key">
                                <label class="border-2 rounded-xl p-4 cursor-pointer transition-all text-center"
                                       :class="form.fulfillment_mode === option.key ? 'border-store-primary bg-red-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" class="sr-only" :value="option.key" x-model="form.fulfillment_mode">
                                    <i class="fas text-2xl mb-2 block"
                                       :class="option.key === 'field_delivery' ? 'fa-user-tie text-violet-600' : 'fa-store text-green-600'"></i>
                                    <span class="text-sm font-bold text-gray-700" x-text="option.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <h3 class="text-lg font-bold text-gray-800 mt-8 mb-4 flex items-center gap-2">
                    <i class="fas fa-credit-card text-orange-500"></i>
                    طريقة الدفع
                </h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <template x-for="method in paymentMethods" :key="method.key">
                        <label class="border-2 rounded-xl p-4 cursor-pointer transition-all text-center"
                               :class="form.payment_method === method.key ? 'border-store-primary bg-red-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" class="sr-only" :value="method.key" x-model="form.payment_method">
                            <i class="fas text-2xl mb-2 block"
                               :class="method.key === 'cod' ? 'fa-money-bill-wave text-green-600' : (method.key === 'manual_transfer' ? 'fa-university text-amber-600' : 'fa-credit-card text-indigo-600')"></i>
                            <span class="text-sm font-bold text-gray-700" x-text="method.label"></span>
                        </label>
                    </template>
                </div>

                <div x-show="selectedMethodRequiresReceipt()" x-cloak class="mt-4 space-y-2">
                    <label class="text-sm font-bold text-gray-600 block">صورة إيصال التحويل *</label>
                    <input type="file" accept="image/*" @change="onReceiptSelected($event)"
                           class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-red-50 file:text-store-primary file:font-bold">
                    <p class="text-xs text-gray-400">JPG أو PNG — حتى 5 ميغابايت</p>
                </div>

                <p x-show="paymentMethods.length === 0" class="text-xs text-amber-600 mt-2">
                    لا توجد طرق دفع مفعّلة — راجع إعدادات المتجر.
                </p>

                <div class="flex gap-2 mt-6">
                    <input type="text" x-model="form.coupon_code" placeholder="كود الخصم"
                           class="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:border-red-500 outline-none text-sm">
                    <button type="button" @click="applyCoupon()" class="px-5 py-3 bg-gray-100 rounded-xl font-bold text-sm hover:bg-gray-200">تطبيق</button>
                </div>
                <p x-show="couponMessage" class="text-green-600 text-sm mt-2" x-text="couponMessage"></p>
                <p x-show="couponError" class="text-red-500 text-sm mt-2" x-text="couponError"></p>

                <button type="button" @click="submitOrder()" :disabled="submitting || quoteLoading"
                        class="w-full mt-8 py-4 bg-store-gradient text-white rounded-xl font-bold text-lg hover-shadow-store transition-all hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60">
                    <i class="fas fa-lock ml-2"></i>
                    <span x-show="!submitting">تأكيد الطلب</span>
                    <span x-show="submitting">جاري الإرسال...</span>
                </button>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <h3 class="text-lg font-bold text-gray-800 mb-4">ملخص الطلب</h3>
                <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                    <template x-for="line in cartLines" :key="line.id">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3">
                            <img :src="line.image_url" :alt="line.name" class="w-14 h-14 object-cover rounded-lg">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-xs text-gray-800 truncate" x-text="line.name"></h4>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="'الكمية: ' + line.quantity"></p>
                            </div>
                            <span class="font-bold text-sm text-store-primary whitespace-nowrap" x-text="formatMoney(line.sale_price * line.quantity)"></span>
                        </div>
                    </template>
                </div>
                <hr class="my-4">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>المجموع الفرعي</span>
                        <span x-text="quote ? formatMoney(quote.subtotal) : '—'"></span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>الشحن</span>
                        <span class="text-green-500 font-bold">مجاني</span>
                    </div>
                    <div class="flex justify-between text-gray-500" x-show="quote && quote.discount > 0">
                        <span>الخصم</span>
                        <span class="text-green-600" x-text="'−' + formatMoney(quote.discount)"></span>
                    </div>
                    <hr>
                    <div class="flex justify-between text-lg font-black text-gray-800">
                        <span>الإجمالي</span>
                        <span class="gradient-text" x-text="quote ? formatMoney(quote.total) : '—'"></span>
                    </div>
                </div>
                <div class="mt-4 bg-green-50 rounded-xl p-3 flex items-center gap-2">
                    <i class="fas fa-shield-halved text-green-500"></i>
                    <span class="text-xs text-green-700 font-bold">طلبك آمن ومشفّر بالكامل</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
