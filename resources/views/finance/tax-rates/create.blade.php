@extends('layouts.app')

@section('title', 'ضريبة جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span>›</span>
    <a href="{{ route('finance.tax-rates.index') }}" class="text-gray-500 hover:text-indigo-600">ضرائب الدليل</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جديد</span>
@endsection

@section('content')
@php
    $liabilityOpts = collect($liabilityOptions ?? [])->values()->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-3xl space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
            <span>ضريبة جديدة</span>
            <x-info field="finance.tax_rates_list_intro" />
        </h1>
        <a href="{{ route('finance.tax-rates.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
    </header>

    <form method="POST" action="{{ route('finance.tax-rates.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_code" /> الرمز <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="32" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_rate" /> النسبة % <span class="text-red-500">*</span></label>
                <input type="number" name="rate_percent" value="{{ old('rate_percent') }}" required min="0" max="100" step="0.0001" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm tabular-nums focus:border-blue-500 focus:ring-blue-500">
                @error('rate_percent')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_name_ar" /> الاسم بالعربية <span class="text-red-500">*</span></label>
                <input type="text" name="name_ar" value="{{ old('name_ar') }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_ar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_name_en" /> الاسم بالإنجليزية</label>
                <input type="text" name="name_en" value="{{ old('name_en') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_ledger" /> حساب الخصوم في الدليل <span class="text-red-500">*</span></label>
                <p class="mb-2 text-xs text-gray-500"><x-info field="finance.tax_rate_form_ledger_hint" /></p>
                <x-searchable-select
                    name="ledger_account_id"
                    id="ledger_account_id"
                    :options="$liabilityOpts"
                    :value="old('ledger_account_id')"
                    :required="true"
                    :error="$errors->has('ledger_account_id')"
                    empty-label="اختر حساب خصوم تفصيلي"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.tax_rate_col_active" /> الحالة <span class="text-red-500">*</span></label>
                <select name="is_active" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" @selected(old('is_active', '1') == '1' || old('is_active', '1') === 1)>نشط</option>
                    <option value="0" @selected(old('is_active') == '0' || old('is_active') === 0)>موقوف</option>
                </select>
                @error('is_active')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.tax-rates.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
        </div>
    </form>
</div>
@endsection
