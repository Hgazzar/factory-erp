@extends('layouts.app')

@section('title', 'عميل جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.customers.index') }}" class="text-gray-500 hover:text-indigo-600">العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عميل جديد</span>
@endsection

@section('content')
<div class="max-w-full">
    {{-- عنوان الصفحة وزر الرجوع --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.customers.index') }}" class="w-10 h-10 rounded-full border border-gray-300 bg-white flex items-center justify-center text-gray-600 hover:bg-gray-50 transition" title="الرجوع">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">عميل جديد</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.customers.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- المعلومات الأساسية --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">المعلومات الأساسية</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الرمز <x-info field="sales.customer_code" /></label>
                    <input type="text" value="{{ $nextCode }}" readonly class="w-full h-10 px-3 pr-4 text-right bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500">تلقائي</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror" placeholder="الاسم">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم (بالعربية)</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar') }}" maxlength="255" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name_ar') border-red-500 @enderror" placeholder="الاسم بالعربية">
                    @error('name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm3.5 4.5a.5.5 0 0 1 0-1h5a.5.5 0 0 1 0 1h-5z"/></svg>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror" placeholder="example@domain.com">
                    </div>
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الهاتف</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/></svg>
                        </span>
                        <input type="text" name="phone" value="{{ old('phone') }}" maxlength="50" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phone') border-red-500 @enderror" placeholder="05xxxxxxxx">
                    </div>
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">رقم ضريبي (VAT) <x-info field="sales.customer_vat_number" /></label>
                    <input type="text" name="vat_number" value="{{ old('vat_number') }}" maxlength="50" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('vat_number') border-red-500 @enderror" placeholder="الرقم الضريبي">
                    @error('vat_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الجوال</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" maxlength="50" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('mobile') border-red-500 @enderror" placeholder="05xxxxxxxx">
                    @error('mobile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الحد الائتماني <x-info field="credit_limit" /></label>
                    <input type="number" inputmode="decimal" name="credit_limit" value="{{ old('credit_limit') }}" min="0" step="any" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('credit_limit') border-red-500 @enderror" placeholder="0.00">
                    @error('credit_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">أيام السداد <x-info field="sales.customer_payment_terms_days" /></label>
                    <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days') }}" min="0" max="3650" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('payment_terms_days') border-red-500 @enderror" placeholder="30">
                    @error('payment_terms_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- العنوان --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">العنوان</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address') }}" maxlength="500" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('address') border-red-500 @enderror" placeholder="الشارع / الحي">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                    <input type="text" name="country" value="{{ old('country') }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('country') border-red-500 @enderror" placeholder="الدولة">
                    @error('country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المدينة</label>
                    <input type="text" name="city" value="{{ old('city') }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('city') border-red-500 @enderror" placeholder="المدينة">
                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المنطقة</label>
                    <input type="text" name="region" value="{{ old('region') }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('region') border-red-500 @enderror" placeholder="المنطقة">
                    @error('region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الرمز البريدي</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" class="w-full md:max-w-xs px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('postal_code') border-red-500 @enderror" placeholder="الرمز البريدي">
                    @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <x-attachment-handler
                hint-field="sales.customer_attachments"
                title="مرفقات العميل"
                :existing="[]"
                :show-existing="false"
                :allow-delete="true"
                help-text="عقود، مستندات تعريف، ملفات PDF أو صور (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
            />
        </div>

        {{-- الحالة والأزرار --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div class="md:col-span-1">
                <span class="block text-sm font-medium text-gray-700 mb-2">الحالة</span>
                <input type="hidden" name="status" value="inactive">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="form-check form-switch form-switch-lg ps-0 mb-0">
                        <input class="form-check-input customer-status-switch m-0" type="checkbox" role="switch"
                               id="customer_status_switch" name="status" value="active"
                               @checked(old('status', 'active') === 'active')>
                    </div>
                    <label for="customer_status_switch" class="mb-0 text-sm text-gray-600 cursor-pointer">نشط عند التفعيل، غير نشط عند الإيقاف</label>
                </div>
                @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap items-center justify-end gap-3 md:col-span-1 md:justify-end">
                <a href="{{ route('sales.customers.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ</button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .customer-status-switch:checked {
        background-color: #16a34a !important;
        border-color: #16a34a !important;
    }
    .customer-status-switch:not(:checked) {
        background-color: #d1d5db !important;
        border-color: #9ca3af !important;
    }
    .customer-status-switch:focus {
        box-shadow: 0 0 0 0.2rem rgba(22, 163, 74, 0.25);
    }
</style>
@endpush
@endsection
