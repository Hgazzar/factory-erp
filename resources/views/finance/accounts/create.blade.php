@extends('layouts.app')

@section('title', 'إضافة حساب - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">لوحة المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.accounts.index') }}" class="text-gray-500 hover:text-blue-600">دليل الحسابات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إضافة حساب</span>
@endsection

@section('content')
@php
    $accountCreateTypeOpts = [
        ['value' => 'asset', 'label' => 'أصل'],
        ['value' => 'liability', 'label' => 'خصم'],
        ['value' => 'equity', 'label' => 'حقوق ملكية'],
        ['value' => 'revenue', 'label' => 'إيراد'],
        ['value' => 'expense', 'label' => 'مصروف'],
    ];
    $accountCreateParentOpts = collect($parentAccounts ?? collect())->map(fn ($p) => [
        'value' => $p->id,
        'label' => trim((string) ($p->code ?? '').' - '.(string) ($p->name_ar ?? '')),
    ])->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-4xl">

    <header class="flex w-full items-center gap-3 border-b border-gray-100 pb-4 mb-6">
        <a href="{{ route('finance.accounts.index') }}" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="العودة">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">إضافة حساب</h1>
    </header>

    <form action="{{ route('finance.accounts.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- المعلومات الأساسية — ترتيب وحجم السكرين: صفان بعمودين ثم حقول بعرض كامل --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4">المعلومات الأساسية</h2>
            <div class="grid gap-4" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));">
                {{-- الصف 1: رمز الحساب (يمين) — مع النص التوجيهي تحت الحقل --}}
                <div class="space-y-1">
                    <label for="code" class="block text-sm font-bold text-gray-700">رمز الحساب</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode ?? '') }}" readonly
                        class="h-10 w-full cursor-not-allowed rounded-md border border-gray-200 px-3 text-sm text-gray-600 @error('code') border-red-500 @enderror"
                        style="background-color: #f3f4f6;" title="يُحدَّد تلقائياً حسب الحساب الرئيسي">
                    @error('code')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                {{-- الصف 1: نوع الحساب (يسار) — نفس الارتفاع والعرض --}}
                <div class="space-y-1">
                    <label for="type" class="block text-sm font-bold text-gray-700">نوع الحساب <span class="text-red-500">*</span></label>
                    <x-custom-select
                        id="type"
                        name="type"
                        class="w-full"
                        :options="$accountCreateTypeOpts"
                        :selected="old('type')"
                        :required="true"
                        :error="$errors->has('type')"
                        empty-label="اختر نوع الحساب"
                        placeholder="ابحث عن نوع الحساب..."
                    />
                    @error('type')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                {{-- الصف 2: اسم الحساب (يمين) — مع النص التوجيهي تحت الحقل --}}
                <div class="space-y-1">
                    <label for="name_ar" class="block text-sm font-bold text-gray-700">اسم الحساب <span class="text-red-500">*</span></label>
                    <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" placeholder="أدخل اسم الحساب..." required
                        class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 @error('name_ar') border-red-500 @enderror"
                        style="background-color: #f9fafb;">
                    @error('name_ar')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                {{-- الصف 2: الاسم بالعربية (يسار) — نفس الارتفاع والعرض --}}
                <div class="space-y-1">
                    <label for="name_en" class="block text-sm font-bold text-gray-700">الاسم بالعربية</label>
                    <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}"
                        class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                        style="background-color: #f9fafb;">
                    @error('name_en')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                {{-- الحساب الرئيسي — نفس عرض الحقول السابقة --}}
                <div class="space-y-1">
                    <label for="parent_id" class="block text-sm font-bold text-gray-700">الحساب الرئيسي</label>
                    <p class="text-xs text-gray-500">اختر حساب رئيسي (اختياري)</p>
                    <x-custom-select
                        id="parent_id"
                        name="parent_id"
                        class="mt-0.5 w-full"
                        :options="$accountCreateParentOpts"
                        :selected="old('parent_id')"
                        :error="$errors->has('parent_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث بالرمز أو اسم الحساب الرئيسي..."
                    />
                    @error('parent_id')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                {{-- الوصف — عرض كامل، نفس أسلوب الحقول --}}
                <div class="space-y-1" style="grid-column: 1 / -1;">
                    <label for="description" class="block text-sm font-bold text-gray-700">الوصف</label>
                    <textarea id="description" name="description" rows="3"
                        class="min-h-[80px] w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                        style="background-color: #f9fafb;"></textarea>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4">الأرصدة</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-gray-700">الرصيد الحالي <x-info field="finance.account_current_balance" /></label>
                    <input type="text" readonly value="SAR 0.0000"
                        class="h-10 w-full cursor-not-allowed rounded-md border border-gray-200 px-3 text-sm tabular-nums text-gray-600"
                        style="background-color: #f3f4f6;">
                    <p class="text-xs text-gray-500">يُحدَّث تلقائياً عند ترحيل القيود المحاسبية.</p>
                </div>
            </div>
        </div>

        {{-- إعدادات الحساب — سويتشات on/off مع السويتش يمين والنص شمال --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4">إعدادات الحساب</h2>
            <div class="space-y-3">
                {{-- حساب بنكي --}}
                <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3"
                     x-data="{ on: {{ old('is_bank') ? 'true' : 'false' }} }">
                    <div @click="on = !on" role="switch" :aria-checked="on"
                         class="relative shrink-0 cursor-pointer rounded-full transition-colors duration-200"
                         style="width:44px; height:24px;"
                         :style="{ backgroundColor: on ? '#2563eb' : '#d1d5db' }">
                        <span class="absolute top-[2px] h-5 w-5 rounded-full bg-white shadow-md transition-all duration-200"
                              :style="{ right: on ? '2px' : 'auto', left: on ? 'auto' : '2px' }"></span>
                    </div>
                    <input type="hidden" name="is_bank" :value="on ? '1' : '0'">
                    <div class="min-w-0 text-right">
                        <p class="text-sm font-bold text-gray-900">حساب بنكي</p>
                        <p class="mt-0.5 text-xs text-gray-500">تحديد كحساب بنكي</p>
                    </div>
                </div>
                {{-- حالة الحساب --}}
                <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3"
                     x-data="{ on: {{ old('is_active', true) ? 'true' : 'false' }} }">
                    <div @click="on = !on" role="switch" :aria-checked="on"
                         class="relative shrink-0 cursor-pointer rounded-full transition-colors duration-200"
                         style="width:44px; height:24px;"
                         :style="{ backgroundColor: on ? '#16a34a' : '#d1d5db' }">
                        <span class="absolute top-[2px] h-5 w-5 rounded-full bg-white shadow-md transition-all duration-200"
                              :style="{ right: on ? '2px' : 'auto', left: on ? 'auto' : '2px' }"></span>
                    </div>
                    <input type="hidden" name="is_active" :value="on ? '1' : '0'">
                    <div class="min-w-0 text-right">
                        <p class="text-sm font-bold text-gray-900">حالة الحساب</p>
                        <p class="mt-0.5 text-xs text-gray-500">الحساب نشط</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- الأزرار في يسار الصفحة؛ ترتيب من اليمين لليسار: إلغاء ثم إنشاء (في RTL الأول يظهر يميناً) --}}
        <div class="flex w-full justify-end">
            <div class="flex gap-3" dir="rtl">
                <a href="{{ route('finance.accounts.index') }}" class="rounded-md border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50" style="background-color: #f9fafb;">
                    إلغاء
                </a>
                <button type="submit" class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    إنشاء
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
