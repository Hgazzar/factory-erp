@extends('layouts.crm')

@section('title', 'الأنشطة — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الأنشطة</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 flex flex-wrap items-center gap-2">
                الأنشطة
                <span class="inline-flex items-center shrink-0"><x-info field="crm.activities_placeholder_intro" /></span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">متابعة كل تفاعلات العملاء المسجلة في النظام.</p>
        </div>
        <a href="{{ route('crm.activities.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V7.5H11.5a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V8.5H4.5a.5.5 0 0 1 0-1H7.5V4.5A.5.5 0 0 1 8 4z"/></svg>
            إضافة نشاط
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500">إجمالي الأنشطة</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format((int) ($stats['total'] ?? 0)) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500">أنشطة اليوم</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">{{ number_format((int) ($stats['today'] ?? 0)) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500">مكتملة</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 tabular-nums">{{ number_format((int) ($stats['completed'] ?? 0)) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500">قيد المتابعة</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 tabular-nums">{{ number_format((int) ($stats['pending'] ?? 0)) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.activities.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 items-end gap-4">
            <div class="md:col-span-2 min-w-0">
                <label for="activities-q" class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">البحث <x-info field="crm.contacts_search_label" /></span>
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-gray-400 z-[1]" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input id="activities-q" name="q" type="search" value="{{ request('q') }}" placeholder="ابحث بالعميل أو الموظف أو الملاحظة…" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 ps-3 pe-10 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="min-w-0">
                <label for="activities-type" class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">النوع <x-info field="crm.leads_type_field" /></span>
                </label>
                <x-searchable-select
                    name="type"
                    id="activities-type"
                    :options="$activityTypeOptions ?? []"
                    :value="request('type', '')"
                    empty-label="الكل"
                    placeholder="كل الأنواع…"
                    :searchable="false"
                />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تطبيق</button>
                <a href="{{ route('crm.activities.index') }}" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">مسح</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-fixed w-full min-w-[56rem] border-collapse text-sm text-right">
                <colgroup>
                    <col class="w-[20%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[24%]">
                </colgroup>
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="sales.customers_table_name" /> العميل</span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.leads_type_field" /> النوع</span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.crm_status" /> النتيجة</span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.assignee" /> المسؤول</span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.follow_up_date_label" /> التاريخ</span></th>
                        <th scope="col" class="py-3 px-3 font-medium min-w-[18rem]"><span class="inline-flex items-center gap-1"><x-info field="crm.crm_notes_field" /> الملاحظة</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse(($activities ?? collect()) as $activity)
                        <tr class="hover:bg-gray-50/80 transition-colors group">
                            <td class="py-3 px-3 align-middle whitespace-nowrap">
                                <a href="{{ route('crm.customers.show', $activity->customer_id) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $activity->customer?->display_name ?? '—' }}
                                </a>
                            </td>
                            <td class="py-3 px-3 align-middle whitespace-nowrap text-gray-800">{{ \App\Models\CrmActivity::labelForType((string) $activity->type) }}</td>
                            <td class="py-3 px-3 align-middle whitespace-nowrap">
                                @if(filled($activity->result))
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-1 text-xs font-medium">{{ $activity->result }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 border border-amber-100 px-2.5 py-1 text-xs font-medium">قيد المتابعة</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 align-middle whitespace-nowrap text-gray-700">{{ $activity->user?->name ?? '—' }}</td>
                            <td class="py-3 px-3 align-middle whitespace-nowrap text-gray-700 tabular-nums">{{ optional($activity->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-3 px-3 align-middle text-gray-700 leading-6">{{ filled($activity->note) ? $activity->note : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">لا توجد أنشطة مطابقة للفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($activities ?? null) && $activities->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
