@extends('layouts.app')

@php
    $isEdit = isset($cheque);
@endphp

@section('title', ($isEdit ? 'تعديل شيك وارد' : 'استلام شيك') . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.cheques.index') }}" class="text-gray-500 hover:text-blue-600">الشيكات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">{{ $isEdit ? 'تعديل شيك وارد' : 'استلام شيك' }}</span>
@endsection

@section('content')
@php
    $incomingPartyOpts = collect($customers ?? collect())->map(function ($customer) {
        $customerName = data_get($customer, 'name_ar') ?: data_get($customer, 'name');
        $optionValue = trim((string) (data_get($customer, 'code').' - '.$customerName), ' -');

        return ['value' => $optionValue, 'label' => $optionValue];
    })->filter(fn ($o) => $o['value'] !== '')->values()->all();
    $incomingCurrencyOpts = [['value' => 'SAR', 'label' => 'SAR']];
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900">{{ $isEdit ? 'تعديل شيك وارد' : 'استلام شيك' }}</h1>
        <a href="{{ route('finance.cheques.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="العودة">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
    </header>

    <form method="POST" action="{{ $isEdit ? route('finance.cheques.update', $cheque) : route('finance.cheques.store') }}" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="type" value="incoming">
        <input type="hidden" name="status" value="{{ old('status', $cheque->status ?? 'pending') }}">

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 flex items-center gap-2 text-2xl font-bold text-gray-900">
                تفاصيل الشيك
                <x-info field="cheque_number" />
            </h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="cheque_number" class="block text-sm font-medium text-gray-700">رقم الشيك <x-info field="cheque_number" /> <span class="text-red-500">*</span></label>
                    <input id="cheque_number" name="cheque_number" type="text" value="{{ old('cheque_number', $cheque->cheque_number ?? '') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="party_name" class="block text-sm font-medium text-gray-700">العملاء <span class="text-red-500">*</span></label>
                    <x-custom-select
                        id="party_name"
                        name="party_name"
                        class="w-full"
                        :options="$incomingPartyOpts"
                        :selected="old('party_name', $cheque->party_name ?? '')"
                        empty-label="اختر العميل"
                        placeholder="ابحث عن العميل..."
                    />
                </div>
                <div class="space-y-1">
                    <label for="drawer_name" class="block text-sm font-medium text-gray-700">اسم الساحب</label>
                    <input id="drawer_name" name="drawer_name" type="text" value="{{ old('drawer_name') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="bank_name" class="block text-sm font-medium text-gray-700">البنك <x-info field="cheque_bank" /> <span class="text-red-500">*</span></label>
                    <input id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $cheque->bank_name ?? '') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="beneficiary_name" class="block text-sm font-medium text-gray-700">فرع البنك</label>
                    <input id="beneficiary_name" name="beneficiary_name" type="text" value="{{ old('beneficiary_name', $cheque->beneficiary_name ?? '') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">المبلغ والتواريخ</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700">المبلغ <x-info field="cheque_amount" /> <span class="text-red-500">*</span></label>
                    <input id="amount" name="amount" type="number" inputmode="decimal" min="0" step="any" value="{{ old('amount', $cheque->amount ?? '') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="currency_2" class="block text-sm font-medium text-gray-700">العملة <span class="text-red-500">*</span></label>
                    <x-custom-select
                        id="currency_2"
                        name="currency_2"
                        class="w-full"
                        :options="$incomingCurrencyOpts"
                        selected="SAR"
                        :empty-option="false"
                        placeholder="العملة..."
                    />
                </div>
                <div class="space-y-1">
                    <label for="issue_date" class="block text-sm font-medium text-gray-700">تاريخ الإصدار <span class="text-red-500">*</span></label>
                    <input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', isset($cheque) && $cheque->issue_date ? $cheque->issue_date->format('Y-m-d') : now()->toDateString()) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="due_date" class="block text-sm font-medium text-gray-700">تاريخ الاستحقاق <x-info field="due_date" /> <span class="text-red-500">*</span></label>
                    <input id="due_date" name="due_date" type="date" required value="{{ old('due_date', isset($cheque) && $cheque->due_date ? $cheque->due_date->format('Y-m-d') : now()->toDateString()) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1 md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">الوصف</label>
                    <input id="description" name="description" type="text" value="{{ old('description') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1 md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700">ملاحظات</label>
                    <textarea id="notes" name="notes" rows="4" class="min-h-[110px] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $cheque->notes ?? '') }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.cheques.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                {{ $isEdit ? 'حفظ التعديلات' : 'استلام شيك' }}
            </button>
        </div>
    </form>
</div>
@endsection
