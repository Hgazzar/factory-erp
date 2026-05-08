@extends('layouts.crm')

@php
    $isEdit = isset($opportunity) && $opportunity !== null;
    $pageTitle = $isEdit ? 'تعديل الفرصة' : 'فرصة جديدة';
    $defaultStage = old('stage', optional($opportunity)->stage ?? 'qualification');
    $probabilityDefault = $isEdit ? (string) (optional($opportunity)->probability ?? '0') : '25';
    $inp = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    $labelBase = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

@section('title', $pageTitle.' — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.opportunities.index') }}" class="text-gray-500 hover:text-indigo-600">الفرص</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $pageTitle }}</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.opportunity_form_intro" /></span>
            </div>
        </div>
        <a href="{{ route('crm.opportunities.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            رجوع للقائمة
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('crm.opportunities.update', $opportunity->id) : route('crm.opportunities.store') }}" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
        @endif

        {{-- معلومات الفرصة --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                <span class="inline-flex items-center gap-1.5 flex-wrap">
                    <x-info field="crm.opportunity_form_section_basic" />
                    معلومات الفرصة
                </span>
            </h2>

            <div class="space-y-4 md:space-y-6">
                <div>
                    <label for="opp_title" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap">
                            <x-info field="crm.opportunity_title_column" />
                            اسم الفرصة
                            <span class="text-red-500 font-semibold">*</span>
                        </span>
                    </label>
                    <input type="text" name="title" id="opp_title" value="{{ old('title', optional($opportunity)->title ?? '') }}" required maxlength="255" class="{{ $inp }}" placeholder="">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div>
                        <label for="opp_customer" class="{{ $labelBase }}">
                            <span class="inline-flex items-center gap-1 flex-wrap">
                                <x-info field="crm.opportunity_customer_column" />
                                العميل
                                <span class="text-red-500 font-semibold">*</span>
                            </span>
                        </label>
                        <x-searchable-select
                            name="customer_id"
                            id="opp_customer"
                            :options="$customerOptions"
                            :value="old('customer_id', optional($opportunity)->customer_id ?? '')"
                            :required="true"
                            :empty-option="false"
                            placeholder="اختر العميل"
                            :searchable="true"
                        />
                    </div>
                    <div>
                        <label for="opp_stage" class="{{ $labelBase }}">
                            <span class="inline-flex items-center gap-1 flex-wrap">
                                <x-info field="crm.opportunity_stage_column" />
                                المرحلة
                                <span class="text-red-500 font-semibold">*</span>
                            </span>
                        </label>
                        <x-searchable-select
                            name="stage"
                            id="opp_stage"
                            :options="$stageOptions"
                            :value="(string) $defaultStage"
                            :required="true"
                            :empty-option="false"
                            placeholder="اختر المرحلة"
                            :searchable="false"
                        />
                    </div>
                </div>

                <div>
                    <label for="opp_description" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap"><x-info field="crm.opportunity_description_field" /> الوصف</span>
                    </label>
                    <textarea name="description" id="opp_description" rows="6" class="{{ $inp }} min-h-[10rem] resize-y">{{ old('description', optional($opportunity)->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- المعلومات المالية --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                <span class="inline-flex items-center gap-1.5 flex-wrap">
                    <x-info field="crm.opportunity_form_section_financial" />
                    المعلومات المالية
                </span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                <div>
                    <label for="opp_estimated" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap">
                            <x-info field="crm.opportunity_estimated_value_column" />
                            القيمة المقدرة
                            <span class="text-red-500 font-semibold">*</span>
                        </span>
                    </label>
                    <input type="number" name="estimated_value" id="opp_estimated" value="{{ old('estimated_value', optional($opportunity)->estimated_value !== null ? $opportunity->estimated_value : '') }}" step="0.01" min="0" required class="{{ $inp }} tabular-nums" placeholder="">
                </div>
                <div>
                    <label for="opp_probability" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap"><x-info field="crm.opportunity_probability_column" /> الاحتمالية (%)</span>
                    </label>
                    <input type="number" name="probability" id="opp_probability" value="{{ old('probability', $probabilityDefault) }}" min="0" max="100" step="1" class="{{ $inp }} tabular-nums" placeholder="0–100">
                </div>
                <div>
                    <label for="opp_close" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap">
                            <x-info field="crm.opportunity_expected_close_column" />
                            تاريخ الإغلاق المتوقع
                            <span class="text-red-500 font-semibold">*</span>
                        </span>
                    </label>
                    <input type="date" name="expected_closing_date" id="opp_close" value="{{ old('expected_closing_date', optional($opportunity)->expected_closing_date?->format('Y-m-d')) }}" required class="{{ $inp }}">
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500 leading-relaxed"><x-info field="crm.opportunity_weighted_auto_hint" /></p>
        </div>

        {{-- معلومات إضافية --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                <span class="inline-flex items-center gap-1.5 flex-wrap">
                    <x-info field="crm.opportunity_form_section_additional" />
                    معلومات إضافية
                </span>
            </h2>

            <div class="space-y-4 md:space-y-6">
                <div class="max-w-md">
                    <label for="opp_next_follow" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap"><x-info field="crm.opportunity_next_follow_up_field" /> تاريخ المتابعة التالية</span>
                    </label>
                    <input type="date" name="next_follow_up_date" id="opp_next_follow" value="{{ old('next_follow_up_date', optional($opportunity)->next_follow_up_date?->format('Y-m-d')) }}" class="{{ $inp }}">
                </div>
                <div>
                    <label for="opp_competitors" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap"><x-info field="crm.opportunity_competitor_notes_field" /> معلومات المنافسين</span>
                    </label>
                    <textarea name="competitor_notes" id="opp_competitors" rows="5" class="{{ $inp }} min-h-[9rem] resize-y" placeholder="ملاحظات حول المنافسين، أسعارهم، نقاط قوتهم…">{{ old('competitor_notes', optional($opportunity)->competitor_notes ?? '') }}</textarea>
                </div>
                <div class="max-w-xl">
                    <label for="opp_assignee" class="{{ $labelBase }}">
                        <span class="inline-flex items-center gap-1 flex-wrap"><x-info field="crm.assignee" /> مسؤول عنها</span>
                    </label>
                    <x-searchable-select
                        name="assigned_user_id"
                        id="opp_assignee"
                        :options="$crmAssigneeFilterOptions"
                        :value="old('assigned_user_id') !== null && old('assigned_user_id') !== '' ? (string) old('assigned_user_id') : (optional($opportunity)->assigned_user_id !== null ? (string) optional($opportunity)->assigned_user_id : '')"
                        empty-label="— بدون —"
                        placeholder="ابحث بالاسم…"
                    />
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="submit" class="inline-flex items-center justify-center min-w-[7.5rem] px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition border-0">
                {{ $isEdit ? 'حفظ التغييرات' : 'حفظ' }}
            </button>
            <a href="{{ route('crm.opportunities.index') }}" class="inline-flex items-center justify-center min-w-[7.5rem] px-6 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
