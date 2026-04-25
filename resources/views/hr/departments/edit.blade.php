@extends('layouts.app')

@section('title', 'تعديل قسم - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.departments.index') }}" class="text-gray-500 hover:text-indigo-600">الأقسام</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">تعديل</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل القسم</h1>
            <p class="mt-1 text-sm text-gray-500">تحديث بيانات القسم التنظيمي</p>
        </div>
        <a href="{{ route('hr.departments.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">رجوع</a>
    </div>

    <form method="POST" action="{{ route('hr.departments.update', $department) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-6 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">المعلومات الأساسية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالعربية <span class="text-red-600">*</span> <x-info field="hr.dept_name" /></label>
                    <input type="text" name="name" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm @error('name') border-red-500 ring-1 ring-red-200 @enderror" value="{{ old('name', $department->name) }}" required maxlength="255" placeholder="مثال: الموارد البشرية">
                    @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالإنجليزية <x-info field="hr.dept_name_en" /></label>
                    <input type="text" name="name_en" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm @error('name_en') border-red-500 ring-1 ring-red-200 @enderror" value="{{ old('name_en', $department->name_en) }}" maxlength="255" placeholder="e.g. Human Resources">
                    @error('name_en')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الرمز <x-info field="hr.dept_code" /></label>
                    <input type="text" name="code" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 font-mono text-sm text-gray-900 shadow-sm @error('code') border-red-500 ring-1 ring-red-200 @enderror" value="{{ old('code', $department->code) }}" maxlength="64" placeholder="HR, FIN, IT">
                    <p class="mt-1.5 text-xs text-gray-500">رمز فريد للقسم ضمن منشأتك (اختياري)</p>
                    @error('code')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div
                    class="flex min-h-[3.5rem] w-full flex-nowrap items-center gap-x-3 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50/90 px-4 py-3 shadow-inner"
                    x-data="{ active: @js((bool) old('is_active', $department->is_active)) }"
                >
                    <span
                        class="shrink-0 text-sm font-bold transition-colors"
                        :class="active ? 'text-emerald-800' : 'text-gray-700'"
                        x-text="active ? 'القسم نشط' : 'القسم غير نشط'"
                    ></span>
                    <input type="hidden" name="is_active" value="{{ filter_var(old('is_active', $department->is_active), FILTER_VALIDATE_BOOLEAN) ? '1' : '0' }}" x-bind:value="active ? 1 : 0">
                    <label class="ms-auto inline-flex shrink-0 cursor-pointer items-center gap-2">
                        <input type="checkbox" x-model.boolean="active" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/30">
                        <span class="text-sm font-medium text-gray-700">تفعيل القسم</span>
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">الوصف <x-info field="hr.dept_description" /></label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm @error('description') border-red-500 ring-1 ring-red-200 @enderror" maxlength="10000" placeholder="أدخل وصف القسم">{{ old('description', $department->description) }}</textarea>
                    @error('description')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-6 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">التسلسل الهرمي</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">القسم الرئيسي / الأعلى <x-info field="hr.dept_parent" /></label>
                    <x-custom-select
                        name="parent_id"
                        :options="$parentSelectOptions"
                        :value="old('parent_id', $department->parent_id)"
                        placeholder="ابحث عن قسم..."
                        empty-label="لا شيء"
                        :error="$errors->has('parent_id')"
                    />
                    <p class="mt-1.5 text-xs text-gray-500">لا يظهر القسم الحالي لمنع التكرار الدائري</p>
                    @error('parent_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">مدير القسم <x-info field="hr.dept_manager" /></label>
                    <x-custom-select
                        name="manager_id"
                        :options="$employeeSelectOptions"
                        :value="old('manager_id', $department->manager_id)"
                        placeholder="ابحث بالاسم أو الكود..."
                        empty-label="لا شيء"
                        :error="$errors->has('manager_id')"
                    />
                    @error('manager_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
            <a href="{{ route('hr.departments.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">تحديث</button>
        </div>
    </form>
</div>
@endsection
