@extends('layouts.app')

@section('title', 'طلبات الإجازة — الموارد البشرية')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">طلبات الإجازة</span>
@endsection

@section('content')
@php
    $statusBadge = fn (string $s) => $s === \App\Models\Leave::STATUS_APPROVED
        ? 'bg-emerald-100 text-emerald-800'
        : ($s === \App\Models\Leave::STATUS_REJECTED
            ? 'bg-red-100 text-red-800'
            : 'bg-amber-100 text-amber-800');
@endphp
<div class="max-w-full space-y-6" dir="rtl" x-data="{
    actionModalOpen: false,
    actionUrl: '',
    actionMessage: '',
    openActionModal(url, message) { this.actionUrl = url; this.actionMessage = message; this.actionModalOpen = true; }
}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">إدارة الإجازات</h1>
            <p class="mt-1 text-sm text-gray-500"><x-info field="hr.leaves_page_intro" /></p>
        </div>
        <a href="{{ route('hr.leave-requests.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            طلب إجازة جديد
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="get" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-800">الحالة <x-info field="hr.leaves_filter_status" /></label>
                <x-custom-select name="status" :options="$statusOptions" :value="request('status', '')" :empty-option="false" empty-label="كل الحالات" :searchable="false" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-800">القسم <x-info field="hr.leaves_filter_department" /></label>
                <x-searchable-select name="department_id" id="department_id" :options="$departmentOptions" :value="request('department_id', '')" :empty-option="false" empty-label="كل الأقسام" placeholder="ابحث بالقسم..." />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-800">الموظف <x-info field="hr.leaves_filter_employee" /></label>
                <x-searchable-select name="employee_id" id="employee_id" :options="$employeeOptions" :value="request('employee_id', '')" :empty-option="false" empty-label="كل الموظفين" placeholder="ابحث بالاسم أو الكود..." />
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">تطبيق</button>
                <a href="{{ route('hr.leave-requests') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إعادة ضبط</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[62rem] table-auto text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold">الموظف <x-info field="hr.leaves_col_employee" /></th>
                        <th class="px-4 py-3 font-semibold">القسم <x-info field="hr.leaves_col_department" /></th>
                        <th class="px-4 py-3 font-semibold">نوع الإجازة <x-info field="hr.leaves_col_type" /></th>
                        <th class="px-4 py-3 font-semibold">من <x-info field="hr.leaves_col_start" /></th>
                        <th class="px-4 py-3 font-semibold">إلى <x-info field="hr.leaves_col_end" /></th>
                        <th class="px-4 py-3 font-semibold">أيام العمل <x-info field="hr.leaves_col_days" /></th>
                        <th class="px-4 py-3 font-semibold">الحالة <x-info field="hr.leaves_col_status" /></th>
                        <th class="px-4 py-3 font-semibold">إجراء <x-info field="hr.leaves_col_actions" /></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($leaves as $leave)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-medium">{{ $leave->employee?->name ?? '—' }}<div class="text-xs text-gray-500">{{ $leave->employee?->code }}</div></td>
                            <td class="px-4 py-3">{{ $leave->employee?->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $leave->leave_type === 'annual' ? 'سنوي' : ($leave->leave_type === 'casual' ? 'عارضة' : ($leave->leave_type === 'sick' ? 'مرضي' : 'استثنائي')) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $leave->start_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $leave->end_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $leave->days_count }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadge($leave->status) }}">
                                    {{ \App\Models\Leave::statusLabelAr($leave->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === \App\Models\Leave::STATUS_NEW)
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                            @click="openActionModal('{{ route('hr.leave-requests.approve', $leave) }}', 'تأكيد اعتماد الطلب؟ سيتم خصم رصيد الإجازة السنوية (إن وُجد) وتسجيل أيام العمل في الحضور بلا خصم راتب.')">اعتماد</button>
                                        <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                            @click="openActionModal('{{ route('hr.leave-requests.reject', $leave) }}', 'تأكيد رفض هذا الطلب؟')">رفض</button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">لا توجد طلبات إجازة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 p-3">{{ $leaves->links() }}</div>
    </div>

    <div
        x-show="actionModalOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" @click="actionModalOpen = false" aria-hidden="true"></div>
        <div class="relative z-[1060] w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-2xl" @click.outside="actionModalOpen = false">
            <h2 class="text-lg font-bold text-gray-900">تأكيد الإجراء</h2>
            <p class="mt-2 text-sm text-gray-600" x-text="actionMessage"></p>
            <form :action="actionUrl" method="post" class="mt-6 flex flex-wrap items-center justify-end gap-2">
                @csrf
                <button type="button" @click="actionModalOpen = false" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">تأكيد</button>
            </form>
        </div>
    </div>
</div>
@endsection
