@extends('layouts.app')

@section('title', 'إعدادات الفوترة الإلكترونية - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">الفوترة الإلكترونية</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">إعدادات الفوترة الإلكترونية</h1>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('sales.einvoice.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- بطاقة الإعدادات العامة --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" class="text-indigo-600"><path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/></svg>
                الإعدادات العامة
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المزود</label>
                    <select name="provider" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="zatca" {{ old('provider', $setting->provider) === 'zatca' ? 'selected' : '' }}>ZATCA (Saudi Arabia)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البيئة</label>
                    <select name="environment" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="sandbox" {{ old('environment', $setting->environment) === 'sandbox' ? 'selected' : '' }}>تجريبية (Sandbox)</option>
                        <option value="production" {{ old('environment', $setting->environment) === 'production' ? 'selected' : '' }}>إنتاجية (Production)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عدد محاولات الإعادة</label>
                    <input type="number" inputmode="decimal" name="retry_attempts" min="0" max="10" step="any" value="{{ old('retry_attempts', $setting->retry_attempts) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="3">
                    @error('retry_attempts')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تأخير الإعادة (دقائق)</label>
                    <input type="number" inputmode="decimal" name="retry_delay_minutes" min="0" max="60" step="any" value="{{ old('retry_delay_minutes', $setting->retry_delay_minutes) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="0">
                    @error('retry_delay_minutes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-5 space-y-5">
                <div class="flex items-start gap-3">
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" name="enabled" value="1" {{ old('enabled', $setting->enabled) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <div>
                        <span class="text-sm font-medium text-gray-900">تفعيل الفوترة الإلكترونية</span>
                        <p class="text-xs text-gray-500 mt-0.5">تفعيل الإرسال التلقائي للفواتير إلى الهيئة الضريبية.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" name="auto_send_on_issue" value="1" {{ old('auto_send_on_issue', $setting->auto_send_on_issue) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <div>
                        <span class="text-sm font-medium text-gray-900">إرسال تلقائي عند الإصدار</span>
                        <p class="text-xs text-gray-500 mt-0.5">إرسال الفواتير تلقائياً عند إصدارها.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- بطاقة إعدادات هيئة الزكاة والضريبة --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" class="text-indigo-600"><path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zM6.5 2a.5.5 0 0 1 .5.5V3h3V2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V3h.5A.5.5 0 0 1 15 3.5v8a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-8A.5.5 0 0 1 1.5 3H2v-.5a.5.5 0 0 1 .5-.5h3z"/></svg>
                إعدادات هيئة الزكاة والضريبة والجمارك (السعودية)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
                    <input type="text" name="zatca_tax_number" maxlength="15" value="{{ old('zatca_tax_number', $setting->zatca_tax_number) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="3XXXXXXXX00003">
                    <p class="text-xs text-gray-500 mt-1">15 رقمًا</p>
                    @error('zatca_tax_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 md:col-start-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم البائع</label>
                    <input type="text" name="zatca_seller_name" value="{{ old('zatca_seller_name', $setting->zatca_seller_name) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="اسم البائع">
                    @error('zatca_seller_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم البائع (بالعربية)</label>
                    <input type="text" name="zatca_seller_name_ar" value="{{ old('zatca_seller_name_ar', $setting->zatca_seller_name_ar) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="اسم البائع بالعربية">
                    @error('zatca_seller_name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ الإعدادات</button>
        </div>
    </form>

    {{-- ربط ZATCA (Onboarding) — نموذج منفصل حتى لا يُفسَّر OTP كجزء من حفظ الإعدادات العامة --}}
    <div class="mt-8 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2 flex flex-wrap items-center gap-2">
            ربط الجهاز مع منصة فاتورة (ZATCA Onboarding)
            <x-info field="sales.einvoice_onboarding_section" />
        </h2>
        <p class="text-sm text-gray-600 mb-4">بعد توليد CSR من النظام (مثلاً عبر <code class="text-xs bg-gray-100 px-1 rounded">php artisan zatca:generate-csr</code>)، أدخل OTP من منصة فاتورة ثم أرسل الطلب لاستلام شهادة الامتثال وحفظها.</p>
        @if ($setting->certificate)
            <p class="text-sm text-emerald-800 mb-4 rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2">تم حفظ شهادة الامتثال مسبقاً. يمكنك إعادة الإرسال بـ OTP جديد لتحديث الشهادة عند الحاجة.</p>
        @endif
        <form method="POST" action="{{ route('sales.einvoice.settings.onboarding') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end sm:flex-wrap">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">رمز OTP <x-info field="sales.einvoice_onboarding_otp" /></span>
                </label>
                <input type="text" name="onboarding_otp" value="{{ old('onboarding_otp') }}" autocomplete="one-time-code" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="أدخل OTP من منصة فاتورة">
                @error('onboarding_otp')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition shrink-0">إرسال طلب الربط والحصول على CSID</button>
        </form>
    </div>
</div>
@endsection
