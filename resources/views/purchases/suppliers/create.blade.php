@extends('layouts.app')

@section('title', 'مورد جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.suppliers.index') }}" class="text-gray-500 hover:text-indigo-600">الموردين</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مورد جديد</span>
@endsection

@push('styles')
<style>
    .sup-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .sup-card-title { font-weight: 600; color: #1f2937; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .sup-fixed-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #e5e7eb; padding: 0.75rem 1.5rem; box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.05); z-index: 40; display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; justify-content: flex-end; }
    .sup-fixed-bar-spacer { height: 4rem; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">مورد جديد</h1>
        </div>
        <a href="{{ route('purchases.suppliers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM5.5 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.5 7.5a.5.5 0 0 1 0 1h5.793l-2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L10.293 7.5H4.5z"/></svg>
            رجوع
        </a>
    </div>

    <form id="supplier-create-form" method="POST" action="{{ route('purchases.suppliers.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="space-y-6">
            {{-- 1. قسم البيانات الأساسية --}}
            <div class="sup-card p-5">
                <h2 class="sup-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg></span>
                    البيانات الأساسية
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اسم المورد <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="اسم المورد">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الرمز</label>
                        <input type="text" value="{{ $nextCode ?? '' }}" readonly class="w-full px-3 py-2.5 rounded-2xl border border-gray-200 bg-gray-100 text-gray-600 text-sm cursor-not-allowed" title="يُولَّد تلقائياً عند الحفظ">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع المورد</label>
                        <select name="supplier_type" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— اختر —</option>
                            <option value="محلي" {{ old('supplier_type', 'محلي') == 'محلي' ? 'selected' : '' }}>محلي</option>
                            <option value="دولي" {{ old('supplier_type') == 'دولي' ? 'selected' : '' }}>دولي</option>
                            <option value="مصنع" {{ old('supplier_type') == 'مصنع' ? 'selected' : '' }}>مصنع</option>
                            <option value="موزع" {{ old('supplier_type') == 'موزع' ? 'selected' : '' }}>موزع</option>
                            <option value="مزود خدمات" {{ old('supplier_type') == 'مزود خدمات' ? 'selected' : '' }}>مزود خدمات</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الاسم (بالعربية)</label>
                        <input type="text" name="name_ar" value="{{ old('name_ar') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="الاسم بالعربية">
                    </div>
                </div>
            </div>

            {{-- 2. قسم معلومات الاتصال --}}
            <div class="sup-card p-5">
                <h2 class="sup-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/></svg></span>
                    معلومات الاتصال
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الجوال</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رقم الجوال">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror" placeholder="البريد الإلكتروني">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الموقع الإلكتروني</label>
                        <input type="url" name="website" value="{{ old('website') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="الموقع الإلكتروني">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رقم الهاتف">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="عنوان المورد">
                    </div>
                </div>
            </div>

            {{-- 3. قسم البيانات المالية --}}
            <div class="sup-card p-5">
                <h2 class="sup-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5 10V7H4v3H1v1h3v3h1v-3h3v-1H5z"/><path d="M3 0a1 1 0 0 0-1 1v2H1a1 1 0 0 0-1 1v2.5a1 1 0 0 0 .571.94l1.957 1.02A1 1 0 0 0 3 9.5v.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-.5a1 1 0 0 0 .472-.834l1.957-1.02A1 1 0 0 0 9 6.5V4.5a1 1 0 0 0-1-1H7V1a1 1 0 0 0-1-1H3zm6 3v2h.5a1 1 0 0 1 .5.5v2.5l.5-.5 1 2V7a1 1 0 0 0-1-1h-1z"/></svg></span>
                    البيانات المالية
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 @error('tax_number') border-red-500 @enderror" placeholder="الرقم الضريبي">
                        @error('tax_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">السجل التجاري</label>
                        <input type="text" name="commercial_register" value="{{ old('commercial_register') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="السجل التجاري">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العملة الافتراضية</label>
                        <select name="currency" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="SAR" {{ old('currency', 'SAR') == 'SAR' ? 'selected' : '' }}>SAR</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شروط الدفع (أيام)</label>
                        <input type="number" inputmode="decimal" name="payment_terms_days" value="{{ old('payment_terms_days', 0) }}" min="0" max="365" step="any" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="٠">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الحد الائتماني</label>
                        <input type="number" inputmode="decimal" name="credit_limit" value="{{ old('credit_limit') }}" min="0" step="any" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="٠">
                    </div>
                </div>
            </div>

            {{-- 4. قسم التفاصيل البنكية --}}
            <div class="sup-card p-5">
                <h2 class="sup-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H15v2a1 1 0 0 1 1 1v3.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V15H3v3.5a.5.5 0 0 1-.5.5h-1A.5.5 0 0 1 1 15v-3.5a1 1 0 0 1 1-1V5h-.5A.5.5 0 0 1 1 4.5v-1A.5.5 0 0 1 1.5 3H2a2 2 0 0 1-2-2zm1 1v.5h13V4H2a1 1 0 0 0-1 1zm13 2H2a1 1 0 0 0-1 1v.5h15V7a1 1 0 0 0-1-1z"/></svg></span>
                    التفاصيل البنكية
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اسم البنك</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="اسم البنك">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رقم الحساب البنكي</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رقم الحساب البنكي">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الآيبان</label>
                        <input type="text" name="iban" value="{{ old('iban') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="الآيبان (IBAN)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رمز السويفت</label>
                        <input type="text" name="swift_code" value="{{ old('swift_code') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رمز السويفت (SWIFT)">
                    </div>
                </div>
            </div>

            {{-- 5. المرفقات (نظام موحد) --}}
            <div class="sup-card p-5">
                <h2 class="sup-card-title">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124, 58, 237, 0.15);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg></span>
                    المرفقات
                </h2>
                <x-attachment-handler
                    hint-field="procurement.supplier_attachments"
                    title="رفع الملفات"
                    :existing="[]"
                    :show-existing="false"
                    :allow-delete="true"
                    help-text="اختياري — صور، PDF، Excel، Word وغيرها (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
                />
            </div>
        </div>

        <div class="sup-fixed-bar-spacer"></div>
    </form>

    {{-- شريط سفلي ثابت --}}
    <div class="sup-fixed-bar">
        <a href="{{ route('purchases.suppliers.index') }}" class="px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
        <button type="submit" form="supplier-create-form" class="px-5 py-2.5 rounded-2xl text-white text-sm font-semibold transition bg-blue-600 hover:bg-blue-700 shadow-sm">حفظ المورد</button>
    </div>
</div>

@endsection
