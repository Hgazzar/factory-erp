@extends('layouts.app')

@section('title', 'فئة منتجات جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.item-categories.index') }}" class="text-gray-500 hover:text-indigo-600">فئات المنتجات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جديد</span>
@endsection

@section('content')
@php
    $invOpts = collect($invOpts ?? [])->values()->all();
    $revOpts = collect($revOpts ?? [])->values()->all();
    $cogsOpts = collect($cogsOpts ?? [])->values()->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-4xl space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
            <span>فئة منتجات جديدة</span>
            <x-info field="inventory.item_categories_list_intro" />
        </h1>
        <a href="{{ route('inventory.item-categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
    </header>

    <form method="POST" action="{{ route('inventory.item-categories.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_col_name" /> الاسم (معرّف) <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="150" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_col_name_ar" /> الاسم بالعربية</label>
                <input type="text" name="name_ar" value="{{ old('name_ar') }}" maxlength="150" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_ar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_col_active" /> الحالة <span class="text-red-500">*</span></label>
                <select name="is_active" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" @selected(old('is_active', '1') == '1' || old('is_active', '1') === 1)>نشط</option>
                    <option value="0" @selected(old('is_active') == '0' || old('is_active') === 0)>موقوف</option>
                </select>
                @error('is_active')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_form_inventory" /> حساب المخزون <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="inventory_account_id"
                    id="inventory_account_id"
                    :options="$invOpts"
                    :value="old('inventory_account_id')"
                    :required="true"
                    :error="$errors->has('inventory_account_id')"
                    empty-label="اختر حساب مخزون"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('inventory_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_form_sales_income" /> حساب إيرادات المبيعات <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="sales_income_account_id"
                    id="sales_income_account_id"
                    :options="$revOpts"
                    :value="old('sales_income_account_id')"
                    :required="true"
                    :error="$errors->has('sales_income_account_id')"
                    empty-label="اختر حساب إيراد"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('sales_income_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="inventory.item_category_form_cogs" /> حساب تكلفة البضاعة المباعة <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="cogs_account_id"
                    id="cogs_account_id"
                    :options="$cogsOpts"
                    :value="old('cogs_account_id')"
                    :required="true"
                    :error="$errors->has('cogs_account_id')"
                    empty-label="اختر حساب COGS"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('cogs_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('inventory.item-categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
        </div>
    </form>
</div>
@endsection
