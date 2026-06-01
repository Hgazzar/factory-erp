@extends('layouts.app')

@section('title', 'فئة أصل جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.fixed-assets.categories.index') }}" class="text-gray-500 hover:text-blue-600">فئات الأصول</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">فئة جديدة</span>
@endsection

@section('content')
@php
    $assetOpts = collect($ledgerOptions['assets'] ?? [])->values()->all();
    $expenseOpts = collect($ledgerOptions['expenses'] ?? [])->values()->all();
    $faCatStatusOpts = [
        ['value' => 'active', 'label' => 'نشط'],
        ['value' => 'inactive', 'label' => 'غير نشط'],
    ];
@endphp
<div dir="rtl" class="mx-auto w-full max-w-4xl space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
            <span>فئة أصل جديدة</span>
            <x-info field="fixed_asset_category_list_intro" />
        </h1>
        <a href="{{ route('finance.fixed-assets.categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
    </header>

    <form method="POST" action="{{ route('finance.fixed-assets.categories.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_code" /> الرمز</label>
                <input type="text" value="{{ $nextCode ?? '' }}" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-600">
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_status" /> الحالة <span class="text-red-500">*</span></label>
                <x-custom-select
                    name="status"
                    class="w-full"
                    :options="$faCatStatusOpts"
                    :selected="old('status', 'active')"
                    :empty-option="false"
                    placeholder="الحالة..."
                    :error="$errors->has('status')"
                />
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_name_ar" /> الاسم بالعربية <span class="text-red-500">*</span></label>
                <input type="text" name="name_ar" value="{{ old('name_ar') }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_ar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_name_en" /> الاسم بالإنجليزية</label>
                <input type="text" name="name_en" value="{{ old('name_en') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_asset" /> حساب الأصل في الدليل <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_asset_account_id"
                    id="ledger_asset_account_id"
                    :options="$assetOpts"
                    :value="old('ledger_asset_account_id')"
                    :required="true"
                    :error="$errors->has('ledger_asset_account_id')"
                    empty-label="اختر حساب أصل"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_asset_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_dep_expense" /> حساب مصروف الإهلاك <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_depreciation_cost_account_id"
                    id="ledger_depreciation_cost_account_id"
                    :options="$expenseOpts"
                    :value="old('ledger_depreciation_cost_account_id')"
                    :required="true"
                    :error="$errors->has('ledger_depreciation_cost_account_id')"
                    empty-label="اختر حساب مصروف"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_depreciation_cost_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="fixed_asset_category_col_acc_dep" /> حساب مجمع الإهلاك <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_accumulated_depreciation_account_id"
                    id="ledger_accumulated_depreciation_account_id"
                    :options="$assetOpts"
                    :value="old('ledger_accumulated_depreciation_account_id')"
                    :required="true"
                    :error="$errors->has('ledger_accumulated_depreciation_account_id')"
                    empty-label="اختر حساب أصل (مجمع)"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_accumulated_depreciation_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.fixed-assets.categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
        </div>
    </form>
</div>
@endsection
