@extends('layouts.app')

@section('title', 'الرواتب - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">الرواتب</span>
@endsection

@section('content')
@php
    $arMonths = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
@endphp
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">الرواتب</h1>
            <p class="mt-1 text-sm text-gray-500"><x-info field="hr.payroll_list_intro" /></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('hr.payrolls.payslips') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/></svg>
                قسائم الرواتب
            </a>
            <a href="{{ route('settings.company.edit') }}#payroll-accounts" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.023l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.023l-.1-.34zM8 5.933a2.909 2.909 0 1 1 0 5.818 2.909 2.909 0 0 1 0-5.818z"/></svg>
                الإعدادات
            </a>
            <a href="{{ route('hr.payrolls.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <span class="text-lg leading-none font-light">+</span>
                دورة رواتب جديدة
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form method="get" action="{{ route('hr.payrolls.index') }}" class="flex flex-wrap items-end gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div>
            <label for="filter_year" class="mb-1.5 block text-sm font-semibold text-gray-800"><x-info field="hr.payroll_filter_year" /></label>
            <select name="filter_year" id="filter_year" class="min-w-[7rem] rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
                @for($y = now()->year + 1; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" @selected((int) $filterYear === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="filter_month" class="mb-1.5 block text-sm font-semibold text-gray-800"><x-info field="hr.payroll_filter_month" /></label>
            <select name="filter_month" id="filter_month" class="min-w-[10rem] rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
                <option value="all" @selected($filterMonth === 'all')>الكل</option>
                @foreach($arMonths as $num => $label)
                    <option value="{{ $num }}" @selected((string) $filterMonth === (string) $num)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">تطبيق</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[64rem] table-auto whitespace-nowrap text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">رقم الدورة <x-info field="hr.payroll_col_cycle_no" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الاسم <x-info field="hr.payroll_col_name" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الفترة <x-info field="hr.payroll_col_period" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">تاريخ الدفع <x-info field="hr.payroll_col_payment_date" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الموظفين <x-info field="hr.payroll_col_employees" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">إجمالي الراتب <x-info field="hr.payroll_col_gross" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الخصومات <x-info field="hr.payroll_col_deductions" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">صافي الراتب <x-info field="hr.payroll_col_net" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الحالة <x-info field="hr.payroll_col_status" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">إجراء <x-info field="hr.payroll_col_action" /></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payrolls as $p)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-mono tabular-nums font-medium text-indigo-800">
                                <a href="{{ route('hr.payrolls.show', $p) }}" class="hover:underline">#{{ $p->id }}</a>
                            </td>
                            <td class="max-w-[14rem] truncate px-4 py-3 text-sm text-gray-800" title="{{ $p->name }}">{{ $p->name ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium">{{ $arMonths[(int) $p->month] ?? $p->month }} {{ $p->year }}</td>
                            <td class="px-4 py-3 tabular-nums text-gray-700">{{ $p->payment_date ? $p->payment_date->format('Y-m-d') : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ (int) $p->employees_count }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $p->total_gross, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-900">{{ number_format((float) $p->total_deductions, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold text-gray-900">{{ number_format((float) $p->total_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($p->status === 'draft')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900">مسودة</span>
                                @elseif($p->status === 'approved')
                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-900">معتمد</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900">مدفوع</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('hr.payrolls.show', $p) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-gray-500">لا توجد دورات رواتب.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-gray-500">
        {{ $payrolls->links() }}
    </div>
</div>
@endsection
