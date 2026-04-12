@extends('layouts.app')

@section('title', 'تعديل تصنيف مصروف - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.expenses.categories.index') }}" class="text-gray-500 hover:text-blue-600">تصنيفات المصروفات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-4xl space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">تعديل تصنيف مصروف</h1>
        <a href="{{ route('finance.expenses.categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
    </header>

    <form method="POST" action="{{ route('finance.expenses.categories.update', $category) }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    الرمز <x-info field="expense_category_col_code" />
                </label>
                <input type="text" value="{{ $category->code }}" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-600">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    التصنيف الأب <x-info field="expense_category_col_parent" />
                </label>
                <select name="parent_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">بدون</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->code }} - {{ $parent->name_ar }}</option>
                    @endforeach
                </select>
                @error('parent_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    الاسم بالعربية <span class="text-red-500">*</span> <x-info field="expense_category_col_name_ar" />
                </label>
                <input type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_ar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    الاسم <x-info field="expense_category_col_name_en" />
                </label>
                <input type="text" name="name_en" value="{{ old('name_en', $category->name_en) }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name_en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    الحالة <span class="text-red-500">*</span> <x-info field="expense_category_col_status" />
                </label>
                <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="active" @selected(old('status', $category->status) === 'active')>نشط</option>
                    <option value="inactive" @selected(old('status', $category->status) === 'inactive')>غير نشط</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', $category->is_taxable)) class="h-5 w-5 rounded border-gray-300" style="accent-color: #2563eb;">
                    <span>خاضع للضريبة <x-info field="expense_category_col_taxable" /></span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.expenses.categories.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ التغييرات</button>
        </div>
    </form>
</div>
@endsection
