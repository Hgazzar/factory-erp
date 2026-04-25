@extends('layouts.app')

@section('title', 'قسائم الرواتب - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('hr.dashboard') }}" class="text-gray-500 hover:text-indigo-600">الموارد البشرية</a>
    <span>›</span>
    <a href="{{ route('hr.payrolls.index') }}" class="text-gray-500 hover:text-indigo-600">الرواتب</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">قسائم الرواتب</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">قسائم الرواتب</h1>
            <p class="mt-1 text-sm text-gray-500"><x-info field="hr.payroll_payslips_list_intro" /></p>
        </div>
        <a href="{{ route('hr.payrolls.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">دورات الرواتب</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] table-auto text-right text-sm text-gray-800">
                <thead class="bg-gray-50 text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">رقم الدورة <x-info field="hr.payroll_payslips_col_cycle" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الفترة <x-info field="hr.payroll_payslips_col_period" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الموظف <x-info field="hr.payroll_payslips_col_employee" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">صافي الراتب <x-info field="hr.payroll_payslips_col_net" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">الحالة <x-info field="hr.payroll_payslips_col_status" /></span>
                        </th>
                        <th class="px-4 py-3 font-semibold text-gray-800">
                            <span class="inline-flex items-center gap-1">إجراء <x-info field="hr.payroll_col_action" /></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($slips as $slip)
                        @php $c = $slip->payrollCycle; @endphp
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-mono">#{{ $c?->id ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $c?->periodLabelAr() ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $slip->employee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold">{{ number_format((float) $slip->net_salary, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($c && $c->status === 'draft')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">مسودة</span>
                                @elseif($c && $c->status === 'approved')
                                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-900">معتمد</span>
                                @elseif($c && $c->status === 'paid')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">مدفوع</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($c && in_array($c->status, ['approved', 'paid'], true))
                                    <a href="{{ route('hr.payroll-slips.payslip', ['payroll' => $c, 'slip' => $slip]) }}" target="_blank" rel="noopener" class="font-semibold text-indigo-600 hover:text-indigo-800">قسيمة</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">لا توجد قسائم مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-gray-500">{{ $slips->links() }}</div>
</div>
@endsection
