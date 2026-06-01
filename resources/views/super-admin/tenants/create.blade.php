@extends('layouts.app')

@section('title', 'إنشاء مستأجر — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('super-admin.dashboard') }}" class="text-gray-500 hover:text-indigo-600">لوحة المالك</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إنشاء مستأجر</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">إنشاء مستأجر جديد</h1>
        <p class="mt-1 text-sm text-gray-500">أدخل بيانات العميل واختر النيش — تُفعَّل الموديولات والقاموس تلقائياً.</p>
    </div>

    <form method="POST" action="{{ route('super-admin.tenants.store') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
        @csrf

        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="super_admin_tenant_company_name" /> اسم الشركة / العميل
            </label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('company_name') border-red-500 @enderror">
            @error('company_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="super_admin_tenant_owner_name" /> اسم مالك الحساب
            </label>
            <input type="text" name="owner_name" id="owner_name" value="{{ old('owner_name') }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('owner_name') border-red-500 @enderror">
            @error('owner_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="super_admin_tenant_email" /> البريد الإلكتروني
            </label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required dir="ltr"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="super_admin_tenant_slug" /> Slug المتجر <span class="text-red-600">*</span>
            </label>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-400 shrink-0" dir="ltr">/s/</span>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required dir="ltr"
                       placeholder="retail-store"
                       pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                       autocomplete="off"
                       class="flex-1 min-w-[12rem] rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('slug') border-red-500 @enderror">
            </div>
            <p class="mt-1 text-xs text-gray-500">اسم فريد يدوياً — حروف إنجليزية صغيرة وأرقام وشرطات فقط. لا يُولَّد تلقائياً.</p>
            @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="niche_key" class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="super_admin_tenant_niche" /> النيش
            </label>
            <x-searchable-select
                name="niche_key"
                id="niche_key"
                :options="$nicheOptions"
                :value="old('niche_key')"
                :required="true"
                :searchable="false"
                empty-label="— اختر النيش —"
                placeholder="اختر النيش..."
                :error="$errors->has('niche_key')"
            />
            @error('niche_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                إنشاء المستأجر
            </button>
            <a href="{{ route('super-admin.tenants.index') }}" class="text-sm text-gray-600 hover:text-indigo-600">إلغاء</a>
        </div>
    </form>
</div>
@endsection
