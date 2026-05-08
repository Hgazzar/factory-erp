@extends('layouts.crm')

@section('title', 'المواعيد — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">المواعيد</span>
@endsection

@section('content')
@php
    $typeFilterOptions = array_merge([['value' => '', 'label' => 'الكل']], $typeOptions ?? []);
    $statusFilterOptions = array_merge([['value' => '', 'label' => 'الكل']], $statusOptions ?? []);
@endphp
<div class="space-y-6" dir="rtl" x-data="{ calendar: {{ request('view') === 'calendar' ? 'true' : 'false' }} }">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">المواعيد</h1>
                <span class="inline-flex items-center shrink-0"><x-info field="crm.appointments_list_intro" /></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm" @click="calendar = !calendar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 6v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6z"/></svg>
                عرض التقويم
            </button>
            <a href="{{ route('crm.appointments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm border-0 no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                موعد جديد
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.appointments.index') }}" class="bg-white rounded-lg border border-slate-200 shadow-sm px-4 pt-5 pb-5 sm:px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="appointments-type-filter" class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <x-searchable-select
                    name="type"
                    id="appointments-type-filter"
                    :options="$typeFilterOptions"
                    :value="request('type', '')"
                    :empty-option="false"
                    placeholder="الكل"
                    :searchable="false"
                />
            </div>
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="appointments-status-filter" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <x-searchable-select
                    name="status"
                    id="appointments-status-filter"
                    :options="$statusFilterOptions"
                    :value="request('status', '')"
                    :empty-option="false"
                    placeholder="الكل"
                    :searchable="false"
                />
            </div>
            <div class="min-w-[12rem] flex-1 max-w-md">
                <label for="appointments-search-q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <input type="search" name="q" id="appointments-search-q" value="{{ request('q') }}" class="w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400" placeholder="بحث برقم الموعد أو العنوان أو العميل">
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden" x-show="!calendar">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse text-sm text-right">
                <colgroup>
                    <col class="w-[13%]">
                    <col class="w-[20%]">
                    <col class="w-[11%]">
                    <col class="w-[12%]">
                    <col class="w-[18%]">
                    <col class="w-[14%]">
                    <col class="w-[12%]">
                </colgroup>
                <thead class="bg-gray-50 text-gray-600 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">رقم الموعد</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">العميل</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">النوع</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">الحالة</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">التاريخ والوقت</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">الموقع</th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap">مسؤول عنه</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($appointments as $row)
                        @php
                            $typeLabel = \App\Models\CrmAppointment::typeLabels()[$row->type] ?? $row->type;
                            $statusLabel = \App\Models\CrmAppointment::statusLabels()[$row->status] ?? $row->status;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/60">
                            <td class="py-3 px-3 text-gray-900 font-mono">{{ $row->appointment_number ?: '—' }}</td>
                            <td class="py-3 px-3 text-gray-900 font-medium">{{ $row->customer?->display_name ?? '—' }}</td>
                            <td class="py-3 px-3 text-gray-700">{{ $typeLabel }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border {{ $row->status === 'planned' ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : ($row->status === 'done' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($row->status === 'late' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-100 text-gray-700 border-gray-200')) }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 tabular-nums whitespace-nowrap">
                                {{ optional($row->start_at)->format('Y/m/d H:i') ?? '—' }}
                                @if($row->end_at)
                                    <span class="text-gray-400">→</span>
                                    {{ optional($row->end_at)->format('H:i') }}
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-700 truncate">{{ $row->location ?: '—' }}</td>
                            <td class="py-3 px-3 text-gray-700">{{ $row->assignee?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center text-gray-500">لا توجد مواعيد مسجّلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $appointments->links() }}</div>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-4" x-show="calendar" x-cloak>
        <div id="crmAppointmentsCalendar" class="min-h-[28rem]"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('crmAppointmentsCalendar');
    if (calendarEl && typeof FullCalendar !== 'undefined') {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'ar',
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                right: 'prev,next today',
                center: 'title',
                left: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: @json($calendarEvents ?? []),
        });
        calendar.render();
    }
});
</script>
@endpush
