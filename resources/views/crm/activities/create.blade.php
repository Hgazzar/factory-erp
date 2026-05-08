@extends('layouts.crm')

@section('title', 'إضافة نشاط — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.activities.index') }}" class="text-gray-500 hover:text-indigo-600">الأنشطة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">إضافة نشاط</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 flex flex-wrap items-center gap-2">
                إضافة نشاط جديد
                <span class="inline-flex items-center shrink-0"><x-info field="crm.activities_placeholder_intro" /></span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">سجل النشاط بدقة لسهولة المتابعة وإعداد التقارير.</p>
        </div>
        <a href="{{ route('crm.activities.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            رجوع للأنشطة
        </a>
    </div>

    <form method="POST" action="{{ route('crm.activities.store') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="rounded-lg border border-gray-200 p-5 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900">البيانات الأساسية</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="activity-customer-id" class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="inline-flex items-center gap-1">العميل <x-info field="sales.customers_table_name" /></span>
                            </label>
                            <x-searchable-select
                                name="customer_id"
                                id="activity-customer-id"
                                :options="$customerOptions ?? []"
                                :value="old('customer_id', '')"
                                empty-label="اختر العميل"
                                placeholder="ابحث باسم العميل…"
                            />
                            @error('customer_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="activity-type" class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="inline-flex items-center gap-1">نوع النشاط <x-info field="crm.leads_type_field" /></span>
                            </label>
                            <x-searchable-select
                                name="type"
                                id="activity-type"
                                :options="$activityTypeOptions ?? []"
                                :value="old('type', '')"
                                empty-label="اختر النوع"
                                placeholder="اختر النوع…"
                                :searchable="false"
                            />
                            @error('type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="activity-result" class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="inline-flex items-center gap-1">النتيجة <x-info field="crm.crm_status" /></span>
                            </label>
                            <input id="activity-result" name="result" type="text" value="{{ old('result') }}" placeholder="مثال: تم التواصل، بانتظار الرد…" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('result')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-5 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900">تفاصيل النشاط</h2>
                    <div>
                        <label for="activity-note" class="block text-sm font-medium text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">الملاحظة <x-info field="crm.crm_notes_field" /></span>
                        </label>
                        <textarea id="activity-note" name="note" rows="6" placeholder="أضف تفاصيل واضحة تساعد فريق المبيعات في المتابعة..." class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">إرشادات التسجيل</h3>
                    <ul class="text-xs text-blue-800 space-y-2 pr-4 list-disc">
                        <li>اكتب نتيجة مختصرة قابلة للقياس.</li>
                        <li>أضف خطوات المتابعة المقبلة داخل الملاحظة.</li>
                        <li>استخدم نوع النشاط الصحيح لضمان دقة التقارير.</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <button type="submit" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">حفظ النشاط</button>
                    <a href="{{ route('crm.activities.index') }}" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">إلغاء</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
