@extends('layouts.app')

@section('title', 'تعديل مورد - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('purchases.suppliers.index') }}" class="text-gray-500 hover:text-indigo-600">الموردين</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">تعديل</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل بيانات المورد</h1>
            <p class="mt-1 text-sm text-gray-500">تحديث بيانات المورد المستخدمة في المشتريات.</p>
        </div>
        <a href="{{ route('purchases.suppliers.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
            الرجوع لقائمة الموردين
        </a>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('purchases.suppliers.update', $supplier) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">كود المورد</label>
                    <input type="text" class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-600" value="{{ $supplier->code }}" readonly maxlength="30" title="لا يمكن تعديل الرمز">
                </div>
                <div class="md:col-span-8">
                    <label class="mb-1 block text-sm font-medium text-gray-700">اسم المورد <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required
                           class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">اسم مسؤول التواصل</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('contact_name') border-red-500 @enderror">
                    @error('contact_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email', $supplier->email) }}"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-12">
                    <label class="mb-1 block text-sm font-medium text-gray-700">العنوان</label>
                    <input type="text" name="address" value="{{ old('address', $supplier->address) }}"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('address') border-red-500 @enderror">
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-12">
                    <label class="inline-flex cursor-pointer items-center gap-3 text-sm text-gray-800">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                        <span>مورد نشط</span>
                    </label>
                </div>

                <div class="md:col-span-12 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <x-attachment-handler
                        hint-field="procurement.supplier_attachments"
                        title="المرفقات"
                        :existing="$supplier->attachments"
                        :allow-delete="true"
                        help-text="حتى 20 ملفاً، 10 ميجابايت لكل ملف."
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-6">
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">تحديث المورد</button>
                <a href="{{ route('purchases.suppliers.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
