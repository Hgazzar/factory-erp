@extends('layouts.crm')

@section('title', $opportunity->title.' — الفرص — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.opportunities.index') }}" class="text-gray-500 hover:text-indigo-600">الفرص</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">{{ $opportunity->opportunity_number }}</span>
@endsection

@section('content')
@php
    $stageLabel = \App\Models\CrmOpportunity::labelForStage($opportunity->stage);
    $stageBadge = \App\Models\CrmOpportunity::badgeClassesForStage($opportunity->stage);
    $closeStr = $opportunity->expected_closing_date
        ? $opportunity->expected_closing_date->timezone(config('app.timezone'))->format('Y/m/d')
        : '—';
    $nextFollowStr = $opportunity->next_follow_up_date
        ? $opportunity->next_follow_up_date->timezone(config('app.timezone'))->format('Y/m/d')
        : '—';
@endphp
<div class="space-y-6" dir="rtl">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-mono text-gray-500 mb-1">{{ $opportunity->opportunity_number }}</p>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900 break-words">{{ $opportunity->title }}</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.opportunity_detail_page" /></span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-medium {{ $stageBadge }}">{{ $stageLabel }}</span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('crm.opportunities.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">العودة للقائمة</a>
            <a href="{{ route('crm.opportunities.edit', $opportunity->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm border-0">تعديل</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100"><span class="inline-flex items-center gap-1">القيم والاحتمالية <x-info field="crm.opportunity_financial_block" /></span></h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_estimated_value_column" /></span></dt>
                    <dd class="text-gray-900 font-medium text-left tabular-nums">{{ number_format((float) $opportunity->estimated_value, 2) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_probability_column" /></span></dt>
                    <dd class="text-gray-900 text-left tabular-nums">{{ $opportunity->probability }}%</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_weighted_value_column" /></span></dt>
                    <dd class="text-gray-900 font-semibold text-left tabular-nums">{{ number_format((float) $opportunity->weighted_value, 2) }}</dd>
                </div>
                <div class="flex justify-between gap-4 pb-1">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_expected_close_column" /></span></dt>
                    <dd class="text-gray-900 text-left tabular-nums">{{ $closeStr }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100"><span class="inline-flex items-center gap-1">العميل والمتابعة <x-info field="crm.opportunity_relations_block" /></span></h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_customer_column" /></span></dt>
                    <dd class="text-left min-w-0 flex-1">
                        @if($opportunity->customer)
                            <a href="{{ route('crm.customers.show', $opportunity->customer) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline break-words">{{ $opportunity->customer->display_name }}</a>
                            <span class="block text-sm text-gray-500 mt-0.5 font-mono">{{ $opportunity->customer->code }}</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.assignee" /></span></dt>
                    <dd class="text-gray-900 text-left">{{ $opportunity->assignedUser?->name ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                    <dt class="text-gray-500 shrink-0">تاريخ الإنشاء</dt>
                    <dd class="text-gray-700 text-left tabular-nums">{{ $opportunity->created_at?->timezone(config('app.timezone'))->format('Y/m/d H:i') ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 pb-1">
                    <dt class="text-gray-500 shrink-0">آخر تحديث</dt>
                    <dd class="text-gray-700 text-left tabular-nums">{{ $opportunity->updated_at?->timezone(config('app.timezone'))->format('Y/m/d H:i') ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 md:p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
            <span class="inline-flex items-center gap-1">الوصف والمعلومات الإضافية <x-info field="crm.opportunity_form_section_additional" /></span>
        </h2>
        <dl class="space-y-4 text-sm">
            <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6 border-b border-gray-100 pb-4">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_description_field" /></span></dt>
                <dd class="text-gray-900 text-left whitespace-pre-wrap flex-1 min-w-0">{{ filled($opportunity->description) ? $opportunity->description : '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-4">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_next_follow_up_field" /></span></dt>
                <dd class="text-gray-900 text-left tabular-nums">{{ $nextFollowStr }}</dd>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6 pb-1">
                <dt class="text-gray-500 shrink-0"><span class="inline-flex items-center gap-1"><x-info field="crm.opportunity_competitor_notes_field" /></span></dt>
                <dd class="text-gray-900 text-left whitespace-pre-wrap flex-1 min-w-0">{{ filled($opportunity->competitor_notes) ? $opportunity->competitor_notes : '—' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
