@extends('layouts.crm')

@section('title', 'الفرص — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الفرص</span>
@endsection

@section('content')
@php
    $stageSelectOptions = array_merge(
        [['value' => '', 'label' => 'الكل']],
        $stageFilterOptions ?? []
    );
@endphp
<div class="space-y-6" dir="rtl">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">الفرص</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.opportunities_page_help" /></span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('crm.opportunities.pipeline') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.917 3.013h-.971L14 5.29V7.5a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h2.291zm-1.5 0V2h-1.5v4h3.5z"/></svg>
                عرض خط الأنابيب
            </a>
            <a href="{{ route('crm.opportunities.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm bg-blue-600 hover:bg-blue-700 transition border-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                + فرصة جديدة
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.opportunities.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm px-4 pt-5 pb-5 sm:px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="opp_stage_filter" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المرحلة <x-info field="crm.opportunity_stage_column" /></span></label>
                <x-searchable-select
                    name="stage"
                    id="opp_stage_filter"
                    :options="$stageSelectOptions"
                    :value="request('stage', '')"
                    :empty-option="false"
                    placeholder="اختر المرحلة…"
                    :searchable="false"
                />
            </div>
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="opp-search-q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="search" name="q" id="opp-search-q" value="{{ request('q') }}" autocomplete="off" placeholder="بحث" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
    </form>

    {{-- جدول مضغوط بدون تمرير أفقي؛ التفاصيل المالية والتواريخ من عمود الإجراءات → صفحة العرض --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full table-fixed border-collapse text-sm text-right">
            <colgroup>
                <col class="w-[10%]">
                <col class="w-[22%]">
                <col class="w-[22%]">
                <col class="w-[13%]">
                <col class="w-[11%]">
                <col class="w-[14%]">
                <col class="w-[8%]">
            </colgroup>
            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                <tr>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">رقم الفرصة</th>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">الفرصة</th>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">العميل</th>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">المرحلة</th>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">القيمة المقدرة</th>
                    <th scope="col" class="py-3 px-3 font-medium align-middle whitespace-nowrap">مسؤول عنها</th>
                    <th scope="col" class="py-3 px-3 font-medium text-center align-middle whitespace-nowrap">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($opportunities as $opportunity)
                    @php
                        $stageLabel = \App\Models\CrmOpportunity::labelForStage($opportunity->stage);
                        $stageBadges = \App\Models\CrmOpportunity::badgeClassesForStage($opportunity->stage);
                        $est = number_format((float) $opportunity->estimated_value, 2);
                    @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50/60 group">
                        <td class="py-3 px-3 align-middle font-mono text-sm font-semibold text-gray-900 tabular-nums">
                            <span class="block truncate" title="{{ $opportunity->opportunity_number }}">{{ $opportunity->opportunity_number }}</span>
                        </td>
                        <td class="py-3 px-3 align-middle">
                            <span class="block truncate font-medium text-gray-900" title="{{ $opportunity->title }}">{{ $opportunity->title }}</span>
                        </td>
                        <td class="py-3 px-3 align-middle min-w-0">
                            @if($opportunity->customer)
                                <a href="{{ route('crm.customers.show', $opportunity->customer) }}" class="block truncate text-blue-600 hover:text-blue-800 font-medium hover:underline" title="{{ $opportunity->customer->display_name }}">{{ $opportunity->customer->display_name }}</a>
                                <span class="block truncate text-sm text-gray-500 mt-0.5 font-mono">{{ $opportunity->customer->code }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-3 align-middle">
                            <span class="inline-flex max-w-full px-2 py-1 rounded-lg text-sm font-medium leading-snug {{ $stageBadges }}">{{ $stageLabel }}</span>
                        </td>
                        <td class="py-3 px-3 align-middle tabular-nums text-gray-800">
                            <span class="block truncate" title="{{ $est }}">{{ $est }}</span>
                        </td>
                        <td class="py-3 px-3 align-middle text-gray-700 min-w-0">
                            <span class="block truncate" title="{{ $opportunity->assignedUser?->name }}">{{ $opportunity->assignedUser?->name ?: '—' }}</span>
                        </td>
                        <td class="py-3 px-1 sm:px-2 align-middle text-center">
                            <div class="relative inline-flex items-center justify-center">
                                <button type="button"
                                        class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                        data-actions-menu="crm-opp-actions-{{ $opportunity->id }}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        title="المزيد من الإجراءات"
                                        aria-label="المزيد من الإجراءات">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                    </svg>
                                </button>
                                <div id="crm-opp-actions-{{ $opportunity->id }}"
                                     class="erp-actions-menu hidden min-w-[13.5rem] max-w-[min(18rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                     style="list-style: none;"
                                     role="menu"
                                     dir="rtl">
                                    <a href="{{ route('crm.opportunities.show', $opportunity->id) }}"
                                       class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 no-underline"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13.1 13.1 0 0 1 1.66-2.043C4.12 4.668 5.588 4 8 4s3.879.668 4.84 1.957c.581.916 1.083 1.96 1.66 2.043q-.282.093-.573.137c-.527.073-1.06.137-1.527.138q-.533 0-1.063-.138-.286-.044-.57-.137m3.019 5.302q-.742.722-1.689 1.147-.939.419-1.856.419-.917 0-1.856-.419-.945-.424-1.689-1.147q-.274-.277-.536-.638h6.316q-.262.361-.536.638"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">عرض التفاصيل</span>
                                    </a>
                                    @if($opportunity->customer)
                                        <a href="{{ route('crm.customers.show', $opportunity->customer) }}"
                                           class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 no-underline"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m4.5 0a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5M12.5 9a4.5 4.5 0 0 1 3.5 1.93V16H9v-1.07A4.5 4.5 0 0 1 12.5 9"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">عرض العميل</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('crm.opportunities.edit', $opportunity->id) }}"
                                       class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 no-underline"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5v-.5H3.5a.5.5 0 0 1-.5-.5v-.207l6.5-6.5 4 4z"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">تعديل الفرصة</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-14 text-center text-gray-500">لا توجد بيانات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($opportunities->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $opportunities->links() }}</div>
        @endif
    </div>
</div>
@endsection
