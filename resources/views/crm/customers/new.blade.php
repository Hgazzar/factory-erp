@extends('layouts.crm')

@section('title', 'عميل جديد — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.customers.index') }}" class="text-gray-500 hover:text-indigo-600">جهات الاتصال</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">عميل جديد</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('crm.customers.index') }}" class="shrink-0 w-10 h-10 rounded-full border border-gray-300 bg-white flex items-center justify-center text-gray-600 hover:bg-gray-50 transition" title="الرجوع إلى جهات الاتصال">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
            </a>
            <div class="min-w-0 flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900 m-0">عميل جديد</h1>
                <span class="inline-flex shrink-0"><x-info field="crm.customer_new_page_intro" /></span>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('crm.customers.store-new') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 sm:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4"><span class="inline-flex items-center gap-1">المعلومات الأساسية <x-info field="sales.customers_basic_block" /></span></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 gap-y-6">
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الرمز <x-info field="sales.customer_code" /></label>
                    <input type="text" value="{{ $nextCode }}" readonly class="w-full h-11 px-3 text-right bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 cursor-not-allowed" dir="ltr">
                    <p class="mt-1 text-xs text-gray-500 mb-0">تلقائي</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" placeholder="الاسم">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الاسم (بالعربية) <x-info field="crm.customer_form_name_ar" /></span></label>
                    <input type="text" name="name_ar" value="{{ old('name_ar') }}" maxlength="255" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name_ar') border-red-500 @enderror" placeholder="الاسم بالعربية">
                    @error('name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">البريد الإلكتروني <x-info field="crm.contacts_email_column" /></span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror" placeholder="example@domain.com" dir="ltr">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الهاتف <x-info field="crm.contacts_phone_column" /></span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" maxlength="50" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror" placeholder="05xxxxxxxx" dir="ltr">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الرقم الضريبي (VAT) <x-info field="sales.customer_vat_number" /></span></label>
                    <input type="text" name="vat_number" value="{{ old('vat_number') }}" maxlength="50" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('vat_number') border-red-500 @enderror" placeholder="الرقم الضريبي" dir="ltr">
                    @error('vat_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1">
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">الحد الائتماني <x-info field="credit_limit" /></label>
                    <input type="number" inputmode="decimal" name="credit_limit" value="{{ old('credit_limit') }}" min="0" step="any" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('credit_limit') border-red-500 @enderror" placeholder="0.00">
                    @error('credit_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1">
                    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">أيام السداد <x-info field="sales.customer_payment_terms_days" /></label>
                    <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days') }}" min="0" max="3650" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('payment_terms_days') border-red-500 @enderror" placeholder="30">
                    @error('payment_terms_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الجوال</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" maxlength="50" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('mobile') border-red-500 @enderror" placeholder="05xxxxxxxx" dir="ltr">
                    @error('mobile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 sm:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">العنوان</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 gap-y-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address') }}" maxlength="500" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror" placeholder="الشارع / الحي">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المدينة</label>
                    <input type="text" name="city" value="{{ old('city') }}" maxlength="100" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('city') border-red-500 @enderror" placeholder="المدينة">
                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                    <input type="text" name="country" value="{{ old('country') }}" maxlength="100" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('country') border-red-500 @enderror" placeholder="الدولة">
                    @error('country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المنطقة</label>
                    <input type="text" name="region" value="{{ old('region') }}" maxlength="100" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('region') border-red-500 @enderror" placeholder="المنطقة">
                    @error('region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الرمز البريدي</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('postal_code') border-red-500 @enderror" placeholder="الرمز البريدي" dir="ltr">
                    @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 sm:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4"><span class="inline-flex items-center gap-1">مسار CRM <x-info field="crm.crm_status" /></span></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 gap-y-6">
                <div>
                    <label for="crm_new_crm_status" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحالة التسويقية <span class="text-red-500">*</span> <x-info field="crm.crm_status" /></span></label>
                    <x-searchable-select
                        name="crm_status"
                        id="crm_new_crm_status"
                        :options="$crmStatusFilterOptions"
                        :value="old('crm_status', 'potential')"
                        :empty-option="false"
                        placeholder="اختر الحالة…"
                        :searchable="false"
                    />
                    @error('crm_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="crm_new_lead_priority" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">أولوية المتابعة <x-info field="crm.lead_priority_field" /></span></label>
                    <x-searchable-select
                        name="lead_priority"
                        id="crm_new_lead_priority"
                        :options="$crmLeadPriorityOptions"
                        :value="old('lead_priority', '')"
                        empty-label="—"
                        placeholder="اختر…"
                        :searchable="false"
                    />
                    @error('lead_priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المصدر <x-info field="crm.crm_source" /></span></label>
                    <input type="text" name="source" value="{{ old('source') }}" maxlength="120" class="w-full px-3 py-2.5 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('source') border-red-500 @enderror" placeholder="إعلان، إحالة، معرض…">
                    @error('source')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="crm_new_assigned_user_id" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">مسؤول المتابعة <x-info field="crm.assignee" /></span></label>
                    <x-searchable-select
                        name="assigned_user_id"
                        id="crm_new_assigned_user_id"
                        :options="$crmAssigneeFilterOptions"
                        :value="old('assigned_user_id') !== null && old('assigned_user_id') !== '' ? (string) old('assigned_user_id') : ''"
                        empty-label="— بدون —"
                        placeholder="ابحث بالاسم…"
                    />
                    @error('assigned_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 sm:p-6">
            <x-attachment-handler
                hint-field="sales.customer_attachments"
                title="مرفقات العميل"
                :existing="[]"
                :show-existing="false"
                :allow-delete="true"
                help-text="عقود، مستندات تعريف، ملفات PDF أو صور (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
            />
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 sm:p-6">
            <span class="block text-sm font-medium text-gray-700 mb-2"><span class="inline-flex items-center gap-1">الحالة التشغيلية (المبيعات) <x-info field="sales.customers_table_status" /></span></span>
            <input type="hidden" name="status" value="inactive">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="form-check form-switch form-switch-lg ps-0 mb-0">
                    <input class="form-check-input crm-customer-status-switch m-0" type="checkbox" role="switch"
                           id="crm_customer_status_switch" name="status" value="active"
                           @checked(old('status', 'active') === 'active')>
                </div>
                <label for="crm_customer_status_switch" class="mb-0 text-sm text-gray-600 cursor-pointer">نشط في المبيعات عند التفعيل، موقف عند الإيقاف</label>
            </div>
            @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-3 justify-end w-full px-1">
            <a href="{{ route('crm.customers.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-semibold hover:bg-gray-50 transition no-underline min-h-[2.75rem] inline-flex items-center justify-center">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition border-0 shadow-sm min-h-[2.75rem] inline-flex items-center justify-center">حفظ</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .crm-customer-status-switch:checked {
        background-color: #16a34a !important;
        border-color: #16a34a !important;
    }
    .crm-customer-status-switch:not(:checked) {
        background-color: #d1d5db !important;
        border-color: #9ca3af !important;
    }
    .crm-customer-status-switch:focus {
        box-shadow: 0 0 0 0.2rem rgba(22, 163, 74, 0.25);
    }
</style>
@endpush
