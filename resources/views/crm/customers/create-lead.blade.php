@extends('layouts.crm')

@section('title', 'عميل محتمل جديد — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.customers.index', ['crm_status' => 'potential']) }}" class="text-gray-500 hover:text-indigo-600">العملاء المحتملين</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">عميل محتمل جديد</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">عميل محتمل جديد</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.lead_create_intro" /></span>
            </div>
        </div>
        <a href="{{ route('crm.customers.index', ['crm_status' => 'potential']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">رجوع للقائمة</a>
    </div>

    <form method="POST" action="{{ route('crm.customers.lead.store') }}" class="space-y-6">
        @csrf

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        {{-- معلومات الاتصال --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100"><span class="inline-flex items-center gap-1">معلومات الاتصال <x-info field="crm.lead_form_contact_section" /></span></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">الاسم الأول <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required maxlength="120" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">الاسم الأخير <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required maxlength="120" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الشركة <x-info field="crm.lead_form_company" /></span></label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المسمى الوظيفي <x-info field="crm.lead_form_job_title" /></span></label>
                    <input type="text" name="job_title" id="job_title" value="{{ old('job_title') }}" maxlength="160" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">البريد الإلكتروني <x-info field="crm.leads_email_column" /></span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الهاتف <x-info field="crm.leads_phone_column" /></span></label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" maxlength="50" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الجوال <x-info field="crm.lead_form_mobile" /></span></label>
                    <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}" maxlength="50" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الموقع الإلكتروني <x-info field="crm.lead_form_website" /></span></label>
                    <input type="text" name="website" id="website" value="{{ old('website') }}" maxlength="500" placeholder="example.com" dir="ltr" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        {{-- العنوان --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100"><span class="inline-flex items-center gap-1">العنوان <x-info field="crm.lead_form_address_section" /></span></h2>
            <div class="space-y-4">
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}" maxlength="500" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">المدينة</label>
                        <input type="text" name="city" id="city" value="{{ old('city') }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-1">المنطقة / المحافظة</label>
                        <input type="text" name="region" id="region" value="{{ old('region') }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">الرمز البريدي</label>
                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                        <input type="text" name="country" id="country" value="{{ old('country') }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- تفاصيل العميل المحتمل --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100"><span class="inline-flex items-center gap-1">تفاصيل العميل المحتمل <x-info field="crm.lead_form_details_section" /></span></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                <div>
                    <label for="lead_source" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المصدر <span class="text-red-500">*</span> <x-info field="crm.crm_source" /></span></label>
                    <x-searchable-select
                        name="source"
                        id="lead_source"
                        :options="$leadSourceOptions"
                        :value="old('source', '')"
                        :required="true"
                        :empty-option="false"
                        placeholder="اختر المصدر…"
                        :searchable="true"
                    />
                </div>
                <div>
                    <label for="lead_priority_field" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الأولوية <span class="text-red-500">*</span> <x-info field="crm.lead_priority_field" /></span></label>
                    <x-searchable-select
                        name="lead_priority"
                        id="lead_priority_field"
                        :options="$crmLeadPriorityOptions"
                        :value="old('lead_priority', 'medium')"
                        :required="true"
                        :empty-option="false"
                        placeholder="اختر الأولوية…"
                        :searchable="false"
                    />
                </div>
                <div class="md:col-span-1">
                    <label for="source_details" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">تفاصيل المصدر <x-info field="crm.lead_form_source_details" /></span></label>
                    <input type="text" name="source_details" id="source_details" value="{{ old('source_details') }}" maxlength="500" placeholder="مثال: اسم الحملة، القناة…" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mt-4">
                <div>
                    <label for="lead_sector_sel" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">القطاع <x-info field="crm.lead_form_sector" /></span></label>
                    <x-searchable-select
                        name="lead_sector"
                        id="lead_sector_sel"
                        :options="$leadSectorOptions"
                        :value="old('lead_sector', '')"
                        :empty-option="false"
                        placeholder="ابحث…"
                        :searchable="true"
                    />
                </div>
                <div>
                    <label for="lead_company_size_sel" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">حجم الشركة <x-info field="crm.lead_form_company_size" /></span></label>
                    <x-searchable-select
                        name="lead_company_size"
                        id="lead_company_size_sel"
                        :options="$leadCompanySizeOptions"
                        :value="old('lead_company_size', '')"
                        :empty-option="false"
                        placeholder="ابحث…"
                        :searchable="true"
                    />
                </div>
                <div>
                    <label for="lead_budget" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الميزانية <x-info field="crm.lead_form_budget" /></span></label>
                    <input type="number" name="lead_budget" id="lead_budget" value="{{ old('lead_budget') }}" step="0.01" min="0" placeholder="0.00" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-4">
                <label for="lead_description" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الوصف <x-info field="crm.lead_form_description" /></span></label>
                <textarea name="lead_description" id="lead_description" rows="4" maxlength="10000" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('lead_description') }}</textarea>
            </div>
            <div class="mt-4">
                <label for="lead_requirements" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المتطلبات <x-info field="crm.lead_form_requirements" /></span></label>
                <textarea name="lead_requirements" id="lead_requirements" rows="4" maxlength="10000" placeholder="صف متطلبات العميل المحتمل…" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('lead_requirements') }}</textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-4">
                <div>
                    <label for="modal_assigned_user_lead" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">مسؤول المتابعة <x-info field="crm.assignee" /></span></label>
                    <x-searchable-select
                        name="assigned_user_id"
                        id="modal_assigned_user_lead"
                        :options="$crmAssigneeFilterOptions"
                        :value="old('assigned_user_id') !== null && old('assigned_user_id') !== '' ? (string) old('assigned_user_id') : ''"
                        empty-label="— بدون —"
                        placeholder="ابحث بالاسم…"
                    />
                </div>
                <div>
                    <label for="lead_rating_sel" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">التقييم <x-info field="crm.leads_rating_column" /></span></label>
                    <x-searchable-select
                        name="lead_rating"
                        id="lead_rating_sel"
                        :options="$crmLeadRatingModalOptions"
                        :value="old('lead_rating') !== null && old('lead_rating') !== '' ? (string) old('lead_rating') : ''"
                        empty-label="—"
                        placeholder="اختر…"
                        :searchable="false"
                    />
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('crm.customers.index', ['crm_status' => 'potential']) }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition border-0">حفظ</button>
        </div>
    </form>
</div>
@endsection
