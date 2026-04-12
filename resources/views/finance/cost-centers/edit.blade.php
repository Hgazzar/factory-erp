@extends('layouts.app')

@section('title', 'تعديل مركز تكلفة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.cost-centers.index') }}" class="text-gray-500 hover:text-blue-600">مراكز التكلفة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل</span>
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
<div dir="rtl" class="mx-auto w-full max-w-3xl space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="mb-5 flex items-center gap-2 text-2xl font-bold text-gray-900">تعديل مركز تكلفة <x-info field="cost_center" /></h1>
        <form method="POST" action="{{ route('finance.cost-centers.update', $center) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-gray-900">المعلومات الأساسية</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">الرمز <x-info field="cost_center_code" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $center->code) }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">الاسم <x-info field="cost_center_name" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $center->name) }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">الفرع <span class="text-red-500">*</span></label>
                        <input type="text" name="branch" value="{{ old('branch', $center->branch) }}" required class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">مركز التكلفة الرئيسي <x-info field="parent_cost_center" /></label>
                        <select name="parent_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">بدون رئيسي (مستوى جذري)</option>
                            @foreach(($parents ?? collect()) as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $center->parent_id) === (string) $parent->id)>
                                    {{ $parent->code }} - {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-gray-900">معلومات الميزانية</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">الميزانية الشهرية <x-info field="monthly_budget" /></label>
                        <div class="flex h-10 overflow-hidden rounded-lg border border-gray-200 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium text-gray-500">SAR</span>
                            <input type="number" inputmode="decimal" min="0" step="any" name="monthly_budget" value="{{ old('monthly_budget', $center->monthly_budget ?? 0) }}" class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">الميزانية السنوية <x-info field="annual_budget" /></label>
                        <div class="flex h-10 overflow-hidden rounded-lg border border-gray-200 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium text-gray-500">SAR</span>
                            <input type="number" inputmode="decimal" min="0" step="any" name="annual_budget" value="{{ old('annual_budget', $center->annual_budget) }}" class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-gray-900">الإعدادات</h2>
                <div class="space-y-5">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">نشط</p>
                                <p class="text-xs text-gray-500">يمكن استخدام مركز التكلفة النشط لتخصيص المصروفات</p>
                            </div>
                            <label class="cc-switch" aria-label="تفعيل مركز التكلفة">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $center->status === 'active')) class="cc-switch-input">
                                <span class="cc-switch-track"></span>
                                <span class="cc-switch-thumb"></span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">الوصف</label>
                        <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $center->description) }}</textarea>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('finance.cost-centers.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
            </div>
        </form>
    </section>
</div>
@endsection
