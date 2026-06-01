@extends('layouts.app')

@section('title', 'تعديل عميل - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.customers.index') }}" class="text-gray-500 hover:text-indigo-600">العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تعديل عميل</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل بيانات العميل</h1>
            <p class="text-sm text-gray-500 mt-1">تحديث بيانات العميل المستخدمة في المبيعات.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">عرض الملف</a>
            <a href="{{ route('sales.customers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 text-gray-800 text-sm font-medium hover:bg-gray-200">الرجوع لقائمة العملاء</a>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.customers.update', $customer) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">المعلومات الأساسية</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الرمز <x-info field="sales.customer_code" /></label>
                    <input type="text" value="{{ $customer->code }}" readonly maxlength="30" class="w-full h-10 px-3 pr-4 text-right bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500">تلقائي — لا يمكن تعديله</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('name') border-red-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم (بالعربية)</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $customer->name_ar) }}" maxlength="255" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('name_ar') border-red-500 @enderror">
                    @error('name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم مسؤول التواصل</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $customer->contact_name) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('contact_name') border-red-500 @enderror">
                    @error('contact_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('email') border-red-500 @enderror">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="50" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('phone') border-red-500 @enderror">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الجوال</label>
                    <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}" maxlength="50" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('mobile') border-red-500 @enderror">
                    @error('mobile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">رقم ضريبي (VAT) <x-info field="sales.customer_vat_number" /></label>
                    <input type="text" name="vat_number" value="{{ old('vat_number', $customer->vat_number ?? $customer->tax_number) }}" maxlength="50" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('vat_number') border-red-500 @enderror">
                    @error('vat_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الحد الائتماني <x-info field="credit_limit" /></label>
                    <input type="number" inputmode="decimal" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" min="0" step="any" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('credit_limit') border-red-500 @enderror">
                    @error('credit_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">أيام السداد <x-info field="sales.customer_payment_terms_days" /></label>
                    <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days', $customer->payment_terms_days) }}" min="0" max="3650" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('payment_terms_days') border-red-500 @enderror">
                    @error('payment_terms_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">العنوان</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address', $customer->address) }}" maxlength="500" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('address') border-red-500 @enderror">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                    <input type="text" name="country" value="{{ old('country', $customer->country) }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('country') border-red-500 @enderror">
                    @error('country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المدينة</label>
                    <input type="text" name="city" value="{{ old('city', $customer->city) }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('city') border-red-500 @enderror">
                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المنطقة</label>
                    <input type="text" name="region" value="{{ old('region', $customer->region) }}" maxlength="100" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('region') border-red-500 @enderror">
                    @error('region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الرمز البريدي</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}" maxlength="20" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm @error('postal_code') border-red-500 @enderror">
                    @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <span class="block text-sm font-medium text-gray-700 mb-2">الحالة <span class="text-red-500">*</span></span>
            <input type="hidden" name="status" value="inactive">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="form-check form-switch form-switch-lg ps-0 mb-0">
                    <input class="form-check-input customer-status-switch m-0 @error('status') is-invalid @enderror" type="checkbox" role="switch"
                           id="customer_status_switch" name="status" value="active"
                           @checked(old('status', $customer->status ?? ($customer->is_active ? 'active' : 'inactive')) === 'active')>
                </div>
                <label for="customer_status_switch" class="form-label fw-normal mb-0 small text-muted">نشط عند التفعيل، غير نشط عند الإيقاف</label>
            </div>
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <x-attachment-handler
                hint-field="sales.customer_attachments"
                title="مرفقات العميل"
                :existing="$customer->attachments"
                :allow-delete="true"
                help-text="إضافة ملفات جديدة دون حذف المرفقات الحالية."
            />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">تحديث العميل</button>
            <a href="{{ route('sales.customers.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
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
