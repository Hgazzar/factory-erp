@extends('layouts.app')

@section('title', 'بيانات العميل - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.customers.index') }}" class="text-gray-500 hover:text-indigo-600">العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">بيانات العميل</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $customer->display_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $customer->code }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.customers.edit', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">تعديل</a>
            <a href="{{ route('sales.customers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 text-gray-800 text-sm font-medium hover:bg-gray-200">رجوع</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">معلومات أساسية</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1">رقم ضريبي (VAT) <x-info field="sales.customer_vat_number" /></span></dt>
                    <dd class="text-gray-900 font-medium text-left">{{ $customer->vat_number ?? $customer->tax_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">البريد</dt>
                    <dd class="text-gray-900 text-left">{{ $customer->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">الهاتف</dt>
                    <dd class="text-gray-900 text-left">{{ $customer->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">الجوال</dt>
                    <dd class="text-gray-900 text-left">{{ $customer->mobile ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500"><span class="inline-flex items-center gap-1">الحد الائتماني <x-info field="credit_limit" /></span></dt>
                    <dd class="text-gray-900 font-medium text-left">{{ $customer->credit_limit !== null ? 'SAR '.number_format((float) $customer->credit_limit, 2) : '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 pb-1">
                    <dt class="text-gray-500"><span class="inline-flex items-center gap-1">أيام السداد <x-info field="sales.customer_payment_terms_days" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ $customer->payment_terms_days !== null ? $customer->payment_terms_days.' يوم' : '—' }}</dd>
                </div>
            </dl>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">العنوان والحالة</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">العنوان</dt>
                    <dd class="text-gray-900 text-left">{{ $customer->address ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">الدولة / المدينة</dt>
                    <dd class="text-gray-900 text-left">{{ trim(implode(' / ', array_filter([$customer->country, $customer->city]))) ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 pb-1">
                    <dt class="text-gray-500">الحالة</dt>
                    <dd class="text-left">
                        @if(($customer->status ?? '') === 'active' || $customer->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">غير نشط</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-5">
        <x-attachment-handler
            hint-field="sales.customer_attachments"
            title="المرفقات"
            :existing="$customer->attachments"
            :uploadable="false"
        />
    </div>
</div>
@endsection
