@extends('layouts.app')

@section('title', 'الأقسام - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الأقسام</span>
@endsection

@php
    $filterLinkParams = fn (string $status) => array_filter([
        'status' => $status,
        'search' => request('search'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">الأقسام</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة الأقسام التنظيمية</p>
        </div>
        <a href="{{ route('hr.departments.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            قسم جديد
        </a>
    </div>

    <div class="flex min-w-0 flex-row flex-nowrap items-center gap-4 overflow-x-auto rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:p-5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        {{-- في RTL: النموذج أولاً يظهر يميناً (بحث)، مجموعة التصفية بجواره على نفس السطر --}}
        <form method="get" action="{{ route('hr.departments.index') }}" class="flex min-w-0 flex-1 flex-row items-stretch gap-3">
            @if($filterStatus !== 'active')
                <input type="hidden" name="status" value="{{ $filterStatus }}">
            @endif
            <input type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="بحث"
                   autocomplete="off"
                   class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-right text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25">
            <button type="submit" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50">
                بحث
            </button>
        </form>
        <div role="group" aria-label="تصفية حالة القسم" class="inline-flex shrink-0 flex-nowrap items-stretch gap-1 rounded-xl border border-slate-200/90 bg-slate-100/90 p-1 shadow-sm">
            @php
                $segBase = 'inline-flex min-w-[4.75rem] items-center justify-center whitespace-nowrap rounded-lg px-3 py-2.5 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1 sm:min-w-[5.5rem] sm:px-4';
                $segActive = 'bg-blue-600 text-white shadow-sm hover:bg-blue-700';
                $segIdle = 'border border-gray-200/90 bg-white text-gray-800 shadow-sm hover:bg-gray-50';
            @endphp
            <a href="{{ route('hr.departments.index', $filterLinkParams('active')) }}"
               class="{{ $segBase }} {{ $filterStatus === 'active' ? $segActive : $segIdle }}">
                نشط
            </a>
            <a href="{{ route('hr.departments.index', $filterLinkParams('inactive')) }}"
               class="{{ $segBase }} {{ $filterStatus === 'inactive' ? $segActive : $segIdle }}">
                غير نشط
            </a>
            <a href="{{ route('hr.departments.index', $filterLinkParams('all')) }}"
               class="{{ $segBase }} {{ $filterStatus === 'all' ? $segActive : $segIdle }}">
                الكل
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-right text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-gray-600">
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                            <span class="inline-flex items-center gap-1">رمز القسم <x-info field="hr.dept_code" /></span>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                            <span class="inline-flex items-center gap-1">اسم القسم <x-info field="hr.dept_name" /></span>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                            <span class="inline-flex items-center gap-1">القسم الأب <x-info field="hr.dept_parent" /></span>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                            <span class="inline-flex items-center gap-1">مدير القسم <x-info field="hr.dept_manager" /></span>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold text-center">
                            <span class="inline-flex items-center justify-center gap-1">الموظفون <x-info field="hr.dept_employees_count" /></span>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">
                            <span class="inline-flex items-center gap-1">الحالة <x-info field="hr.dept_status" /></span>
                        </th>
                        <th scope="col" class="w-px whitespace-nowrap px-4 py-3 font-semibold text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($departments as $department)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-mono text-gray-800">{{ $department->code ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $department->name }}</div>
                                @if(filled($department->name_en))
                                    <div class="mt-0.5 text-xs text-gray-500">{{ $department->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $department->parent?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $department->manager?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center tabular-nums text-gray-800">{{ $department->employees_count }}</td>
                            <td class="px-4 py-3">
                                @if($department->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">نشط</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center align-middle">
                                @php $deptMenuId = 'department-actions-'.$department->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$deptMenuId">
                                    <a href="{{ route('hr.departments.edit', $department) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل القسم</span>
                                    </a>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form method="POST" action="{{ route('hr.departments.destroy', $department) }}" class="m-0" onsubmit="return confirm('حذف القسم؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف القسم</span>
                                        </button>
                                    </form>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                لا توجد أقسام
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $departments->links() }}</div>
        @endif
    </div>
</div>
@endsection
