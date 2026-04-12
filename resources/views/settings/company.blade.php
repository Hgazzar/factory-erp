@extends('layouts.app')

@section('title', 'إعدادات المنشأة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إعدادات المنشأة</span>
@endsection

@section('content')
<div class="max-w-2xl">
    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-6">إعدادات المنشأة</h1>

        <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">اسم المنشأة</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $setting->name) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: MIRADA ERP">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
                    <input type="text" id="tax_number" name="tax_number" value="{{ old('tax_number', $setting->tax_number) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="الرقم الضريبي">
                    @error('tax_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="commercial_register" class="block text-sm font-medium text-gray-700 mb-1">السجل التجاري</label>
                    <input type="text" id="commercial_register" name="commercial_register" value="{{ old('commercial_register', $setting->commercial_register) }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="رقم السجل التجاري">
                    @error('commercial_register')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <textarea id="address" name="address" rows="3" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="عنوان المنشأة">{{ old('address', $setting->address) }}</textarea>
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-1">رابط اللوجو (اختياري)</label>
                    <input type="text" id="logo_url" name="logo_url" value="{{ old('logo_url', $setting->logo_url && !str_starts_with($setting->logo_url, 'company/') ? $setting->logo_url : '') }}" class="w-full px-3 py-2.5 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://... أو اتركه فارغاً واستخدم رفع الملف">
                    @error('logo_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="logo_file" class="block text-sm font-medium text-gray-700 mb-1">رفع لوجو (اختياري)</label>
                    <input type="file" id="logo_file" name="logo_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-right">
                    @if($setting->logo_url && str_starts_with($setting->logo_url, 'company/'))
                        <p class="mt-1 text-sm text-gray-500">اللوجو الحالي: <img src="{{ asset('storage/' . $setting->logo_url) }}" alt="Logo" class="h-10 inline-block align-middle ml-1"></p>
                    @endif
                    @error('logo_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="px-4 py-2.5 rounded-lg text-white text-sm font-medium" style="background: #2563eb;">حفظ</button>
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
