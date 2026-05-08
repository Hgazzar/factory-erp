@extends('layouts.crm')

@section('title', 'سجل المشتركين — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">سجل المشتركين</span>
@endsection

@section('content')
@php
    $statusSelectOptions = array_merge(
        [['value' => '', 'label' => 'الكل']],
        $statusOptions ?? []
    );
@endphp
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">سجل المشتركين</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.memberships_intro" /></span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('crm.memberships.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm bg-blue-600 hover:bg-blue-700 transition border-0 no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                + اشتراك جديد
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.memberships.index') }}" class="bg-white rounded-lg border border-slate-200 shadow-sm px-4 pt-5 pb-5 sm:px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="membership-status" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الحالة <x-info field="crm.membership_status" /></span></label>
                <x-searchable-select
                    name="status"
                    id="membership-status"
                    :options="$statusSelectOptions"
                    :value="request('status', '')"
                    :empty-option="false"
                    placeholder="اختر الحالة…"
                    :searchable="false"
                />
            </div>
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="membership-search-q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="search" name="q" id="membership-search-q" value="{{ request('q') }}" autocomplete="off" placeholder="بحث بالكود أو الاسم" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full table-fixed border-collapse text-sm text-right">
            <colgroup>
                <col class="w-[12%]">
                <col class="w-[24%]">
                <col class="w-[10%]">
                <col class="w-[14%]">
                <col class="w-[16%]">
                <col class="w-[12%]">
                <col class="w-[12%]">
            </colgroup>
            <thead class="bg-gray-50 text-gray-600 border-b border-slate-200">
                <tr>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">الرمز</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">اسم العضوية</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">المستوى</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">الخصم</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">الحد الأدنى للإنفاق</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">الحالة</th>
                    <th class="py-3 px-3 font-medium whitespace-nowrap">تاريخ الإنشاء</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($memberships as $membership)
                    @php
                        $discountTypeLabel = \App\Models\CrmMembership::discountTypeLabels()[$membership->discount_type] ?? $membership->discount_type;
                        $statusLabel = \App\Models\CrmMembership::statusLabels()[$membership->status] ?? $membership->status;
                    @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50/60">
                        <td class="py-3 px-3 font-mono text-gray-900">{{ $membership->code }}</td>
                        <td class="py-3 px-3 font-medium text-gray-900">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $membership->color ?: '#3B82F6' }}"></span>
                                <span>{{ $membership->name }}</span>
                            </span>
                        </td>
                        <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((int) $membership->level) }}</td>
                        <td class="py-3 px-3 text-gray-700">
                            @if($membership->discount_type === 'percentage')
                                <span class="tabular-nums">{{ number_format((float) $membership->discount_value, 2) }}%</span>
                            @else
                                <span class="tabular-nums">{{ number_format((float) $membership->discount_value, 2) }}</span>
                            @endif
                            <span class="text-gray-500">({{ $discountTypeLabel }})</span>
                        </td>
                        <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((float) $membership->min_spending, 2) }}</td>
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border {{ $membership->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-gray-700 tabular-nums">{{ optional($membership->created_at)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-14 text-center text-gray-500">لا توجد بيانات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($memberships->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $memberships->links() }}</div>
        @endif
    </div>
</div>
@endsection

