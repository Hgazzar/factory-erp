@extends('layouts.app')

@section('title', 'ورديات العمل - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">ورديات العمل</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">ورديات العمل</h1>
            <p class="mt-1 text-sm text-gray-500">قوالب أوقات الدوام — البداية، النهاية، ودقائق السماح للحضور والانصراف.</p>
        </div>
        <a href="{{ route('hr.shifts.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            وردية جديدة
        </a>
    </div>

    <div class="flex min-w-0 flex-row flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:flex-nowrap md:p-4">
        <form method="get" action="{{ route('hr.shifts.index') }}" class="flex min-w-0 flex-1 flex-row items-stretch gap-2">
            @if($filterStatus !== 'active')
                <input type="hidden" name="status" value="{{ $filterStatus }}">
            @endif
            <input type="search" name="search" value="{{ request('search') }}" placeholder="بحث بالرمز أو الاسم" autocomplete="off"
                   class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25">
            <button type="submit" class="shrink-0 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">بحث</button>
        </form>
        <div class="inline-flex shrink-0 gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1">
            @foreach(['active' => 'نشط', 'inactive' => 'غير نشط', 'all' => 'الكل'] as $key => $label)
                <a href="{{ route('hr.shifts.index', array_filter(['status' => $key !== 'active' ? $key : null, 'search' => request('search')])) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold {{ $filterStatus === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-right text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">الرمز <x-info field="hr.shift_code" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">الاسم <x-info field="hr.shift_name_ar" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">البداية <x-info field="hr.shift_start_time" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">النهاية <x-info field="hr.shift_end_time" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">السماح (د) <x-info field="hr.shift_grace_minutes" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">الموظفون <x-info field="hr.shift_employees_count" /></span></th>
                        <th class="px-4 py-3 font-semibold"><span class="inline-flex items-center gap-1">الحالة <x-info field="hr.shift_status" /></span></th>
                        <th class="px-4 py-3 font-semibold text-center w-14">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shifts as $shift)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-mono">{{ $shift->code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $shift->name_ar }}</td>
                            <td class="px-4 py-3">{{ optional($shift->start_time)->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ optional($shift->end_time)->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ (int) ($shift->grace_minutes ?? 0) }}</td>
                            <td class="px-4 py-3">{{ $shift->employees_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                @if($shift->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">نشط</span>
                                @else
                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">غير نشط</span>
                                @endif
                                @if($shift->is_night)
                                    <span class="ms-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">ليلية</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $menuId = 'hr-shift-'.$shift->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$menuId">
                                    <x-erp-actions-menu-item :href="route('hr.shifts.show', $shift)" icon="view">عرض</x-erp-actions-menu-item>
                                    <x-erp-actions-menu-item :href="route('hr.shifts.edit', $shift)" icon="edit">تعديل</x-erp-actions-menu-item>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form action="{{ route('hr.shifts.destroy', $shift) }}" method="POST" class="m-0">
                                        @csrf @method('DELETE')
                                        <x-erp-actions-menu-item type="submit" icon="delete" :danger="true" confirm="حذف الوردية؟">حذف</x-erp-actions-menu-item>
                                    </form>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">لا توجد ورديات. <a href="{{ route('hr.shifts.create') }}" class="text-blue-600">أضف وردية</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($shifts->hasPages())
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">{{ $shifts->links() }}</div>
    @endif
</div>
@endsection
