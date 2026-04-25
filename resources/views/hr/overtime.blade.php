@extends('layouts.app')

@section('title', 'العمل الإضافي — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">العمل الإضافي</span>
@endsection

@push('styles')
<style>
    .hr-ot-table thead th.sticky-ot,
    .hr-ot-table tbody td.sticky-ot {
        box-shadow: none !important;
        -webkit-box-shadow: none !important;
        border: 0 !important;
    }
    .hr-ot-table th:nth-last-child(2),
    .hr-ot-table td:nth-last-child(2) {
        border: 0 !important;
    }
    /* تغطية حافة 1px بين «الحالة» والعمود اللّاصق (WebKit/Sticky) */
    .hr-ot-table th.sticky-ot,
    .hr-ot-table td.sticky-ot {
        margin-right: -1px;
    }
</style>
@endpush

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">العمل الإضافي</h1>
            <p class="mt-1 text-sm text-gray-500">تتبع واعتماد طلبات العمل الإضافي للموظفين <x-info field="hr.overtime_intro" /></p>
        </div>
        <a href="{{ route('hr.overtime.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg leading-none font-light" aria-hidden="true">+</span>
            طلب عمل إضافي جديد
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-amber-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-600">الطلبات المعلقة <x-info field="hr.overtime_stat_pending" /></p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-amber-700">{{ number_format($pendingCount) }}</p>
                </div>
                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                </span>
            </div>
        </div>
        <div class="rounded-lg border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-600">إجمالي الساعات (الشهر) <x-info field="hr.overtime_stat_total_hours" /></p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-indigo-900">{{ rtrim(rtrim(number_format($totalHoursMonth, 2), '0'), '.') ?: '0' }}<span class="text-lg font-semibold text-gray-500">h</span></p>
                </div>
                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700" aria-hidden="true">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                </span>
            </div>
        </div>
        <div class="rounded-lg border border-emerald-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-600">الساعات المعتمدة (الشهر) <x-info field="hr.overtime_stat_approved_hours" /></p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-800">{{ rtrim(rtrim(number_format($approvedHoursMonth, 2), '0'), '.') ?: '0' }}<span class="text-lg font-semibold text-gray-500">h</span></p>
                </div>
                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                </span>
            </div>
        </div>
    </div>

    <form method="get" action="{{ route('hr.overtime') }}" class="flex flex-wrap items-end gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="min-w-[12rem]">
            <label for="filter_status" class="mb-1.5 block text-sm font-semibold text-gray-800">الحالة <x-info field="hr.overtime_filter_status" /></label>
            <x-custom-select
                name="status"
                id="filter_status"
                :options="$statusOptions"
                :value="$statusFilter ?? ''"
                :empty-option="false"
                empty-label="كل الحالات"
                :searchable="false"
            />
        </div>
        <div>
            <label for="date_from" class="mb-1.5 block text-sm font-semibold text-gray-800">من تاريخ <x-info field="hr.overtime_filter_from" /></label>
            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label for="date_to" class="mb-1.5 block text-sm font-semibold text-gray-800">إلى تاريخ <x-info field="hr.overtime_filter_to" /></label>
            <input type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">تطبيق</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="hr-ot-table w-full min-w-[64rem] table-auto border-separate border-spacing-0 text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="min-w-[16rem] px-4 py-3 font-semibold text-gray-800 md:min-w-[20rem]"><span class="inline-flex items-center gap-1">الموظف <x-info field="hr.overtime_col_employee" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">التاريخ <x-info field="hr.overtime_col_date" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">الساعات <x-info field="hr.overtime_col_hours" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">النوع <x-info field="hr.overtime_col_kind" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">المعامل <x-info field="hr.overtime_col_multiplier" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">السبب <x-info field="hr.overtime_col_reason" /></span></th>
                        <th class="px-4 py-3 font-semibold text-gray-800"><span class="inline-flex items-center gap-1">الحالة <x-info field="hr.overtime_col_status" /></span></th>
                        <th class="sticky-ot sticky end-0 z-20 min-w-[7.25rem] border-0 bg-gray-50 px-3 py-3 text-center font-semibold text-gray-800 shadow-none ring-0 [background-clip:padding-box]">
                            <span class="inline-flex min-w-0 items-center justify-center gap-1.5 whitespace-nowrap">إجراءات <x-info field="hr.overtime_col_actions" /></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($requests as $req)
                        <tr class="group hover:bg-gray-50/80">
                            <td class="max-w-[24rem] min-w-[16rem] px-4 py-3 font-medium md:min-w-[20rem]">
                                <span class="block truncate" title="{{ $req->employee?->name ?? '' }}">{{ $req->employee?->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $req->work_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $req->hours, 2) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $req->kindLabelAr() }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ rtrim(rtrim(number_format((float) $req->rate_multiplier, 2), '0'), '.') ?: '0' }}×</td>
                            <td class="max-w-[12rem] truncate px-4 py-3 text-gray-600" title="{{ $req->reason }}">{{ $req->reason ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($req->status === \App\Models\OvertimeRequest::STATUS_NEW)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900">جديد</span>
                                @elseif($req->status === \App\Models\OvertimeRequest::STATUS_APPROVED)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900">معتمد</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-900">مرفوض</span>
                                @endif
                            </td>
                            <td class="sticky-ot sticky end-0 z-10 min-w-[7.25rem] border-0 bg-white px-3 py-3 text-center align-middle shadow-none ring-0 group-hover:bg-gray-50/80 [background-clip:padding-box]">
                                @if($req->status === \App\Models\OvertimeRequest::STATUS_NEW)
                                    @php $otMenuId = 'hr-ot-actions-'.$req->id; @endphp
                                    <div class="inline-flex w-full min-w-0 items-center justify-center">
                                    <x-erp-actions-dropdown :menu-id="$otMenuId">
                                        <form method="post" action="{{ route('hr.overtime.approve', $req) }}" class="m-0" onsubmit="return confirm('تأكيد اعتماد هذا الطلب؟ سيُحتسب في مسير الرواتب لاحقاً.');">
                                            @csrf
                                            <button type="submit"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-indigo-800 transition hover:bg-indigo-50"
                                                    role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">اعتماد الطلب</span>
                                            </button>
                                        </form>
                                        <div class="mx-2 my-1 border-t border-gray-100"></div>
                                        <form method="post" action="{{ route('hr.overtime.reject', $req) }}" class="m-0" onsubmit="return confirm('تأكيد رفض الطلب؟');">
                                            @csrf
                                            <button type="submit"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                    role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">رفض الطلب</span>
                                            </button>
                                        </form>
                                    </x-erp-actions-dropdown>
                                    </div>
                                @else
                                    <div class="inline-flex w-full min-w-0 items-center justify-center" title="لا يوجد إجراءات متاحة">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-dashed border-gray-200/90 bg-gray-50/80 text-sm text-gray-400" aria-hidden="true">—</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500">لا توجد طلبات عمل إضافي.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-gray-500">{{ $requests->links() }}</div>
</div>
@endsection
