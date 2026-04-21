@extends('layouts.app')

@section('title', 'تعديل حساب بنكي - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.bank-accounts.index') }}" class="text-gray-500 hover:text-blue-600">الحسابات البنكية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل حساب بنكي</span>
@endsection

@push('styles')
<style>
    .cc-switch {
        position: relative;
        display: inline-flex;
        width: 44px;
        height: 24px;
        align-items: center;
        cursor: pointer;
    }
    .cc-switch-input {
        position: absolute;
        opacity: 0;
        width: 1px;
        height: 1px;
    }
    .cc-switch-track {
        width: 44px;
        height: 24px;
        border-radius: 9999px;
        background: #d1d5db;
        transition: background-color .2s ease;
    }
    .cc-switch-thumb {
        position: absolute;
        top: 2px;
        right: 22px;
        width: 20px;
        height: 20px;
        border-radius: 9999px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
        transition: right .2s ease;
    }
    .cc-switch-input:checked + .cc-switch-track {
        background: #2563eb;
    }
    .cc-switch-input:checked + .cc-switch-track + .cc-switch-thumb {
        right: 2px;
    }
</style>
@endpush

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <h1 class="text-3xl font-bold text-gray-900">تعديل حساب بنكي</h1>
    </header>

    <form method="POST" action="{{ route('finance.bank-accounts.update', $account) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">تفاصيل البنك</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="bank_name" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>اسم البنك <span class="text-red-500">*</span></span>
                        <x-info field="bank_name" />
                    </label>
                    <input id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $account->bank_name) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('bank_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="bank_name_ar" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>اسم البنك بالعربية</span>
                        <x-info field="bank_name_ar" />
                    </label>
                    <input id="bank_name_ar" name="bank_name_ar" type="text" value="{{ old('bank_name_ar') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="branch_name" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>اسم الفرع</span>
                        <x-info field="branch_name" />
                    </label>
                    <input id="branch_name" name="branch_name" type="text" value="{{ old('branch_name', $account->branch_name) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="swift_code" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>رمز السويفت</span>
                        <x-info field="swift_code" />
                    </label>
                    <input id="swift_code" name="swift_code" type="text" value="{{ old('swift_code') }}" placeholder="XXXXEGCAXXX" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm uppercase focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">تفاصيل الحساب البنكي</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="account_number" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>رقم الحساب <span class="text-red-500">*</span></span>
                        <x-info field="account_number" />
                    </label>
                    <input id="account_number" name="account_number" type="text" value="{{ old('account_number', $account->account_number) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('account_number') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="iban" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الآيبان</span>
                        <x-info field="iban" />
                    </label>
                    <input id="iban" name="iban" type="text" value="{{ old('iban', $account->iban) }}" placeholder="SA..." class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm uppercase focus:border-blue-500 focus:ring-blue-500">
                    @error('iban') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="account_name_ar" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>اسم الحساب بالعربية</span>
                        <x-info field="account_name_ar" />
                    </label>
                    <input id="account_name_ar" name="account_name_ar" type="text" value="{{ old('account_name_ar') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="currency" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>العملة <span class="text-red-500">*</span></span>
                        <x-info field="currency" />
                    </label>
                    @php
                        $currencyOpts = [
                            ['value' => 'SAR', 'label' => 'SAR'],
                            ['value' => 'USD', 'label' => 'USD'],
                            ['value' => 'EUR', 'label' => 'EUR'],
                            ['value' => 'AED', 'label' => 'AED'],
                            ['value' => 'EGP', 'label' => 'EGP'],
                        ];
                    @endphp
                    <x-custom-select
                        id="currency"
                        name="currency"
                        class="w-full"
                        :options="$currencyOpts"
                        :selected="old('currency', $account->currency)"
                        :empty-option="false"
                        placeholder="اختر العملة..."
                        :error="$errors->has('currency')"
                    />
                    @error('currency') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2 md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="inline-flex items-center gap-1 text-sm font-semibold text-gray-900">
                        <span>حساب الدليل المرتبط</span>
                        <x-info field="bank_ledger_linked_readonly" />
                    </p>
                    @if($account->ledgerAccount)
                        <p class="text-sm text-gray-800">
                            <span class="font-mono text-gray-600">{{ $account->ledgerAccount->code }}</span>
                            — {{ $account->ledgerAccount->name_ar }}
                        </p>
                        <p class="text-xs text-gray-500">
                            الرصيد في قائمة البنوك = الرصيد الافتتاحي لحساب الدليل + مجموع (مدين − دائن) من القيود.
                        </p>
                    @else
                        <p class="text-xs text-amber-800">لا يوجد حساب دليل مرتبط بهذا السجل؛ راجع المسؤول لإصلاح الربط إن كان السجل قديماً.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">جهة الاتصال</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="contact_person" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>جهة الاتصال</span>
                        <x-info field="contact_person" />
                    </label>
                    <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-1">
                    <label for="contact_phone" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>هاتف الاتصال</span>
                        <x-info field="contact_phone" />
                    </label>
                    <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">الإعدادات</h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            نشط
                            <x-info field="bank_status" />
                        </p>
                        <p class="text-xs text-gray-500">الحساب نشط ويمكن استخدامه</p>
                    </div>
                    <label class="cc-switch" aria-label="تفعيل الحساب البنكي">
                        <input type="hidden" name="status" value="inactive">
                        <input type="checkbox" name="status" value="active" class="cc-switch-input" @checked(old('status', $account->status) === 'active')>
                        <span class="cc-switch-track"></span>
                        <span class="cc-switch-thumb"></span>
                    </label>
                </div>
                @error('status') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            افتراضي
                            <x-info field="default_account" />
                        </p>
                        <p class="text-xs text-gray-500">حساب بنكي افتراضي</p>
                    </div>
                    <label class="cc-switch" aria-label="تعيين حساب افتراضي">
                        <input type="hidden" name="default_account" value="0">
                        <input type="checkbox" name="default_account" value="1" class="cc-switch-input" @checked(old('default_account') == '1')>
                        <span class="cc-switch-track"></span>
                        <span class="cc-switch-thumb"></span>
                    </label>
                </div>

                <div class="space-y-1">
                    <label for="notes" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>ملاحظات</span>
                        <x-info field="notes" />
                    </label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.bank-accounts.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection
