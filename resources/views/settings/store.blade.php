@extends('layouts.app')

@section('title', 'إعدادات المتجر الإلكتروني — '.config('app.name'))

@section('content')
<div dir="rtl" class="max-w-5xl space-y-6">
    <x-flash-messages />

    @isset($merchantMetrics)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_sales_today" /> مبيعات اليوم</div>
            <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($merchantMetrics['sales_today'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_orders_today" /> طلبات اليوم</div>
            <div class="text-2xl font-bold text-indigo-600 tabular-nums">{{ $merchantMetrics['orders_today'] }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 mb-1"><x-info field="store.metrics_revenue_month" /> إيراد الشهر</div>
            <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($merchantMetrics['revenue_month'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="text-xs font-medium text-amber-800 mb-1"><x-info field="store.metrics_pending_collection" /> بانتظار التحصيل / التحقق</div>
            <div class="text-2xl font-bold text-amber-900 tabular-nums">{{ $merchantMetrics['pending_collection'] ?? 0 }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3">أفضل المنتجات (30 يوم)</h2>
            @forelse($merchantMetrics['top_products'] as $row)
                <div class="flex justify-between text-sm py-2 border-b border-gray-100 last:border-0">
                    <span>{{ $row['name'] }}</span>
                    <span class="text-gray-500 tabular-nums">{{ $row['qty'] }} — {{ number_format($row['revenue'], 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد مبيعات أونلاين بعد.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3">آخر الطلبات</h2>
            @forelse($merchantMetrics['recent_orders'] as $order)
                <div class="flex justify-between text-sm py-2 border-b border-gray-100 last:border-0">
                    <span>{{ $order['invoice_number'] }} — {{ $order['customer'] ?? 'زائر' }}</span>
                    <span class="text-gray-500 tabular-nums">{{ number_format($order['total'], 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا طلبات حديثة.</p>
            @endforelse
        </div>
    </div>
    @endisset

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-gray-900 mb-2">إعدادات المتجر الإلكتروني</h1>
        @if($storeUrl)
            <p class="text-sm text-gray-600 mb-4">
                رابط المتجر:
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener" class="text-indigo-600 font-semibold">{{ $storeUrl }}</a>
            </p>
        @else
            <p class="text-sm text-amber-700 mb-4">أكمل ملف النيش (slug) لتفعيل رابط المتجر العام.</p>
        @endif

        <form method="POST" action="{{ route('settings.store.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_store_enabled" value="0">
                <input type="checkbox" name="is_store_enabled" value="1" id="is_store_enabled" class="rounded border-gray-300"
                       @checked(old('is_store_enabled', $settings->is_store_enabled))>
                <label for="is_store_enabled" class="text-sm font-medium text-gray-800 inline-flex items-center gap-1">
                    <x-info field="store.is_enabled" /> تفعيل المتجر الإلكتروني للجمهور
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.hero_title" /> عنوان البانر</label>
                    <input type="text" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.hero_subtitle" /> وصف البانر</label>
                    <input type="text" name="hero_subtitle" value="{{ old('hero_subtitle', $settings->hero_subtitle) }}" class="w-full rounded-lg border-gray-300">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.hero_offer" /> نص العرض (Badge)</label>
                <input type="text" name="hero_offer_text" value="{{ old('hero_offer_text', $settings->hero_offer_text) }}" class="w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.default_device" /> جهاز POS لطلبات الأونلاين</label>
                <x-searchable-select name="default_pos_device_id" :options="collect($devices)->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->all()"
                    :value="old('default_pos_device_id', $settings->default_pos_device_id)" empty-label="أول جهاز نشط" :searchable="count($devices) > 6" />
            </div>

            <h2 class="text-base font-bold text-gray-900 pt-2">طرق الدفع</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="cod_enabled" value="0">
                    <input type="checkbox" name="cod_enabled" value="1" class="rounded border-gray-300" @checked(old('cod_enabled', $settings->cod_enabled ?? true))>
                    <x-info field="store.payment_cod" /> الدفع عند الاستلام (COD)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="manual_transfer_enabled" value="0">
                    <input type="checkbox" name="manual_transfer_enabled" value="1" class="rounded border-gray-300" @checked(old('manual_transfer_enabled', $settings->manual_transfer_enabled))>
                    <x-info field="store.payment_manual_transfer" /> تحويل بنكي + إيصال
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="online_payment_enabled" value="0">
                    <input type="checkbox" name="online_payment_enabled" value="1" class="rounded border-gray-300" @checked(old('online_payment_enabled', $settings->online_payment_enabled))>
                    <x-info field="pos.online_payment_enabled" /> Paymob (بطاقة/محفظة)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="tamara_enabled" value="0">
                    <input type="checkbox" name="tamara_enabled" value="1" class="rounded border-gray-300" @checked(old('tamara_enabled', $settings->tamara_enabled))>
                    <x-info field="store.payment_tamara" /> Tamara (السعودية)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="tabby_enabled" value="0">
                    <input type="checkbox" name="tabby_enabled" value="1" class="rounded border-gray-300" @checked(old('tabby_enabled', $settings->tabby_enabled))>
                    <x-info field="store.payment_tabby" /> Tabby
                </label>
            </div>

            <h2 class="text-base font-bold text-gray-900 pt-4">Paymob</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="pos.online_payment_provider" /> مزود الدفع</label>
                    <x-searchable-select name="online_payment_provider" :searchable="false"
                        :options="[['value' => 'paymob', 'label' => 'Paymob'], ['value' => 'stripe', 'label' => 'Stripe']]"
                        :value="old('online_payment_provider', $settings->online_payment_provider ?? 'paymob')"
                        empty-label="Paymob" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="pos.online_payment_mode" /> الوضع</label>
                    <x-searchable-select name="online_payment_mode" :searchable="false"
                        :options="[['value' => 'sandbox', 'label' => 'Sandbox (اختبار)'], ['value' => 'live', 'label' => 'Live (إنتاج)']]"
                        :value="old('online_payment_mode', $settings->online_payment_mode ?? 'sandbox')"
                        empty-option="false" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">المفتاح العام</label>
                    <input type="text" name="online_payment_public_key" value="{{ old('online_payment_public_key', $settings->online_payment_public_key) }}" class="w-full rounded-lg border-gray-300" dir="ltr" autocomplete="off">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">المفتاح السري</label>
                    <input type="password" name="online_payment_secret_key" value="{{ old('online_payment_secret_key', $settings->online_payment_secret_key) }}" class="w-full rounded-lg border-gray-300" dir="ltr" autocomplete="off">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Integration ID</label>
                    <input type="text" name="paymob_integration_id" value="{{ old('paymob_integration_id', $settings->paymob_integration_id) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">HMAC Secret (Webhooks)</label>
                    <input type="password" name="paymob_hmac_secret" value="{{ old('paymob_hmac_secret', $settings->paymob_hmac_secret) }}" class="w-full rounded-lg border-gray-300" dir="ltr" autocomplete="off">
                </div>
                <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50/60 p-4">
                    <p class="text-sm font-semibold text-indigo-900 mb-1">رابط Webhook (Processed Callback) — ضعه في لوحة Paymob</p>
                    <code class="block text-xs sm:text-sm text-indigo-800 break-all dir-ltr" dir="ltr">{{ $paymobWebhookUrl ?? store_paymob_webhook_url() }}</code>
                    <p class="text-xs text-indigo-700/80 mt-2">POST فقط · يجب أن يطابق <code class="text-xs">APP_URL</code> في الإنتاج · Paymob يرسل التوقيع في <code class="text-xs">?hmac=</code></p>
                </div>
            </div>

            <h2 class="text-base font-bold text-gray-900 pt-4">Tamara / Tabby</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tamara API Token</label>
                    <input type="password" name="tamara_api_token" value="{{ old('tamara_api_token', $settings->tamara_api_token) }}" class="w-full rounded-lg border-gray-300" dir="ltr" autocomplete="off">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tabby Public Key</label>
                    <input type="text" name="tabby_public_key" value="{{ old('tabby_public_key', $settings->tabby_public_key) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tabby Secret Key</label>
                    <input type="password" name="tabby_secret_key" value="{{ old('tabby_secret_key', $settings->tabby_secret_key) }}" class="w-full rounded-lg border-gray-300" dir="ltr" autocomplete="off">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.about_us" /> من نحن</label>
                <textarea name="about_us" rows="4" class="w-full rounded-lg border-gray-300">{{ old('about_us', $settings->about_us) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.contact_us" /> اتصل بنا</label>
                <textarea name="contact_us" rows="4" class="w-full rounded-lg border-gray-300">{{ old('contact_us', $settings->contact_us) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.faq" /> الأسئلة الشائعة</label>
                <textarea name="faq" rows="4" class="w-full rounded-lg border-gray-300">{{ old('faq', $settings->faq) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.shipping_policy" /> سياسة الشحن</label>
                <textarea name="shipping_policy" rows="4" class="w-full rounded-lg border-gray-300">{{ old('shipping_policy', $settings->shipping_policy) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.return_policy" /> سياسة الإرجاع</label>
                <textarea name="return_policy" rows="4" class="w-full rounded-lg border-gray-300">{{ old('return_policy', $settings->return_policy) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.track_order_help" /> تعليمات تتبع الطلب</label>
                <textarea name="track_order_help" rows="3" class="w-full rounded-lg border-gray-300">{{ old('track_order_help', $settings->track_order_help) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.privacy_policy" /> سياسة الخصوصية</label>
                <textarea name="privacy_policy" rows="4" class="w-full rounded-lg border-gray-300">{{ old('privacy_policy', $settings->privacy_policy) }}</textarea>
            </div>

            <h2 class="text-base font-bold text-gray-900 pt-2">روابط التواصل</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.social_facebook" /> فيسبوك</label>
                    <input type="url" name="social_facebook" value="{{ old('social_facebook', $settings->social_facebook) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.social_instagram" /> إنستغرام</label>
                    <input type="url" name="social_instagram" value="{{ old('social_instagram', $settings->social_instagram) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.social_twitter" /> X / تويتر</label>
                    <input type="url" name="social_twitter" value="{{ old('social_twitter', $settings->social_twitter) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="store.social_whatsapp" /> واتساب (رقم)</label>
                    <input type="text" name="social_whatsapp" value="{{ old('social_whatsapp', $settings->social_whatsapp) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">حفظ الإعدادات</button>
        </form>
    </div>
</div>
@endsection
