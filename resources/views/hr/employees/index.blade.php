@extends('layouts.app')

@section('title', 'الموظفون - '.config('app.name'))

@php
    $deptFilterValue = (string) request('department_id', '');
@endphp

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الموظفون</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">الموظفون</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة بيانات الموظفين والأقسام والربط المالي.</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('hr.employees.import') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </a>
            <a href="{{ route('hr.employees.export') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <a href="{{ route('hr.employees.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                موظف جديد
            </a>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:p-4">
        <form method="get" action="{{ route('hr.employees.index') }}" class="flex min-w-0 flex-row flex-wrap items-center gap-2 md:flex-nowrap">
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="بحث"
                   autocomplete="off"
                   class="min-w-[10rem] min-h-[2.5rem] flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-right text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25">
            <div class="min-w-[10.5rem] max-w-full shrink-0 basis-[12rem] md:max-w-[20rem]">
                <label for="filter_department_id" class="sr-only">تصفية حسب القسم</label>
                <x-custom-select
                    id="filter_department_id"
                    name="department_id"
                    :options="$departmentSelectOptions"
                    :value="$deptFilterValue"
                    placeholder="ابحث في الأقسام…"
                    empty-label="جميع الأقسام"
                    :empty-option="false"
                    :fixed-panel="true"
                />
            </div>
            <div class="min-w-[9.5rem] shrink-0 sm:min-w-[10.5rem]">
                <label for="filter_employee_status" class="sr-only">حالة الموظف</label>
                <x-custom-select
                    id="filter_employee_status"
                    name="status"
                    :options="$statusSelectOptions"
                    :value="(string) $filterStatus"
                    placeholder="—"
                    :empty-option="false"
                    :fixed-panel="true"
                    :searchable="false"
                />
            </div>
            <button type="submit" class="inline-flex h-10 min-w-[6.5rem] shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 focus:ring-offset-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-95" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L9 8.691V14.5a.5.5 0 0 1-.402.49l-3 1A.5.5 0 0 1 5 15.5V8.691L1.128 3.834A.5.5 0 0 1 1 3.5v-2z"/>
                </svg>
                تطبيق
            </button>
        </form>
    </div>

    <div class="w-full min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="w-full min-w-0 overflow-x-auto">
            <table class="w-full min-w-0 table-auto border-collapse text-right text-sm text-gray-800 divide-y divide-gray-200">
                <thead class="whitespace-nowrap bg-gray-50 text-sm text-gray-600">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">كود الموظف</th>
                        <th scope="col" class="w-full min-w-[14rem] px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">الاسم <x-info field="hr.emp_col_employee" /></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">القسم <x-info field="hr.employee_department" /></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">المسمى الوظيفي <x-info field="hr.employee_position" /></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">الصلاحية <x-info field="hr.emp_col_role" /></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">الحالة <x-info field="hr.employee_status" /></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-right font-semibold">
                            <span class="inline-flex items-center gap-1.5">مركز التكلفة <x-info field="hr.emp_col_cost_center" /></span>
                        </th>
                        <th scope="col" class="min-w-[6.5rem] px-2 py-4 text-end text-gray-600">
                            <span class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">الإجراءات <x-info field="hr.emp_col_actions" /></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white text-sm text-gray-800">
                    @forelse($employees as $employee)
                        <tr class="whitespace-nowrap hover:bg-gray-50/80">
                            @php
                                $deptName = $employee->department?->name ?? $employee->department ?? '—';
                                $jobTitle = $employee->position ?? $employee->job_title ?? '—';
                                $cc = $employee->costCenter;
                                $ccText = '—';
                                if ($cc && (filled($cc->name) || filled($cc->code))) {
                                    if (filled($cc->code) && filled($cc->name)) {
                                        $ccText = $cc->code.' — '.$cc->name;
                                    } elseif (filled($cc->name)) {
                                        $ccText = $cc->name;
                                    } else {
                                        $ccText = (string) $cc->code;
                                    }
                                }
                            @endphp
                            <td class="px-6 py-4 text-right font-mono text-gray-800">
                                <span class="block truncate" title="{{ $employee->code }}">{{ $employee->code ?: '—' }}</span>
                            </td>
                            <td class="w-full min-w-[14rem] max-w-2xl px-6 py-4 text-right font-semibold text-gray-900">
                                <span class="block truncate" title="{{ $employee->name }}">{{ $employee->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-800">
                                <span class="block truncate" title="{{ $deptName }}">{{ $deptName }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-800">
                                <span class="block truncate" title="{{ $jobTitle }}">{{ $jobTitle }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-800">
                                @if($employee->linkedUser)
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">{{ \App\Support\ErpRoles::roleLabelAr($employee->linkedUser->role) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(($employee->status ?? 'active') === 'active')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">نشط</span>
                                @elseif($employee->status === 'on_leave')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900" title="في إجازة">في إجازة</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-800">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-gray-800">
                                <span class="block truncate font-medium" title="{{ $ccText !== '—' ? $ccText : '' }}">
                                    @if($ccText !== '—'){{ $ccText }}@else<span class="text-gray-400">—</span>@endif
                                </span>
                            </td>
                            <td class="min-w-[6.5rem] shrink-0 whitespace-nowrap py-4 pe-4 ps-2 text-end align-middle">
                                @php $empMenuId = 'hr-emp-actions-'.$employee->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$empMenuId">
                                    <a href="{{ route('hr.employees.show', $employee) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 0 1.66 2.043C4.12 11.332 5.88 12.5 8 12.5c2.12 0 3.879-1.168 5.168-2.457A13.133 13.133 0 0 0 14.828 8a13.133 13.133 0 0 0-1.66-2.043C11.88 4.668 10.12 3.5 8 3.5c-2.12 0-3.879 1.168-5.168 2.457A13.133 13.133 0 0 0 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">عرض الموظف</span>
                                    </a>
                                    <a href="{{ route('hr.employees.edit', $employee) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل الموظف</span>
                                    </a>
                                    <div class="mx-2 my-1 border-t border-gray-100"></div>
                                    <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="m-0" onsubmit='return confirm({{ json_encode('حذف الموظف «'.$employee->name.'» نهائياً؟ لا يمكن التراجع.', JSON_UNESCAPED_UNICODE) }});'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11v1z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف موظف</span>
                                        </button>
                                    </form>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                لا يوجد موظفون مطابقون للتصفية.
                                <a href="{{ route('hr.employees.create') }}" class="font-medium text-blue-600 hover:text-blue-800">أضف موظفاً</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($employees->hasPages())
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
