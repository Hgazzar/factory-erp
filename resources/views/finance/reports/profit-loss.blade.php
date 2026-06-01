@extends('layouts.app')

@section('title', 'تقرير الأرباح والخسائر - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">الأرباح والخسائر</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تقرير الأرباح والخسائر</h1>
                <p class="mt-1 text-sm text-gray-500">قائمة استحقاق من القيود: صافي المبيعات − COGS − رواتب HR − مصاريف تشغيل = صافي الربح.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">لوحة الأدمن</a>
            <button type="button" class="no-print inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" onclick="window.print()">طباعة / PDF</button>
        </div>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.reports.profit-loss') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="finance.pl_period_from" /> من تاريخ</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="finance.pl_period_to" /> إلى تاريخ</label>
                <input type="date" name="to_date" value="{{ $toDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-6 text-sm font-semibold text-white hover:bg-blue-700">عرض</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/80 px-4 py-4 sm:px-6">
            <p class="text-sm font-semibold text-gray-900">قائمة الأرباح والخسائر</p>
            <p class="mt-0.5 text-xs text-gray-500">من {{ $fromDate ?? '—' }} إلى {{ $toDate ?? '—' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <tbody>
                    <tr class="border-b border-gray-100 bg-white">
                        <td class="px-4 py-3 font-semibold text-gray-900" colspan="2"><x-info field="finance.pl_net_sales" /> صافي المبيعات</td>
                        <td class="px-4 py-3 text-left font-bold tabular-nums text-emerald-700">{{ erp_money($report['net_sales']) }}</td>
                    </tr>
                    <tr class="border-b border-gray-50 text-gray-600">
                        <td class="px-4 py-2 ps-8" colspan="2">إجمالي إيرادات المبيعات</td>
                        <td class="px-4 py-2 text-left tabular-nums">{{ erp_money($report['gross_sales']) }}</td>
                    </tr>
                    @if($report['sales_returns'] > 0.0001)
                    <tr class="border-b border-gray-50 text-gray-600">
                        <td class="px-4 py-2 ps-8" colspan="2">مرتجعات المبيعات</td>
                        <td class="px-4 py-2 text-left tabular-nums text-red-600">− {{ erp_money($report['sales_returns']) }}</td>
                    </tr>
                    @endif
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-gray-900" colspan="2"><x-info field="finance.pl_cogs" /> تكلفة البضاعة المباعة (COGS)</td>
                        <td class="px-4 py-3 text-left font-medium tabular-nums text-red-600">− {{ erp_money($report['net_cogs']) }}</td>
                    </tr>
                    @if($report['purchase_returns'] > 0.0001)
                    <tr class="border-b border-gray-50 text-xs text-gray-500">
                        <td class="px-4 py-2 ps-8" colspan="2">مردودات مشتريات (تخفيض COGS)</td>
                        <td class="px-4 py-2 text-left tabular-nums">− {{ erp_money($report['purchase_returns']) }}</td>
                    </tr>
                    @endif
                    <tr class="border-b border-gray-200 bg-emerald-50/40">
                        <td class="px-4 py-3 font-semibold text-gray-900" colspan="2"><x-info field="finance.pl_gross_profit" /> مجمل الربح</td>
                        <td class="px-4 py-3 text-left font-bold tabular-nums {{ $report['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ erp_money($report['gross_profit']) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-gray-900" colspan="2">
                            <x-info field="finance.pl_payroll" /> رواتب الموظفين (HR)
                            @if($report['payroll_account_code'])
                                <span class="text-xs text-gray-400">— حساب {{ $report['payroll_account_code'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-left font-medium tabular-nums text-red-600">− {{ erp_money($report['payroll_expense']) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-gray-900" colspan="2"><x-info field="finance.pl_operating_expenses" /> المصاريف التشغيلية</td>
                        <td class="px-4 py-3 text-left font-medium tabular-nums text-red-600">− {{ erp_money($report['operating_expenses']) }}</td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="px-4 py-4 text-lg font-bold text-gray-900" colspan="2">
                            <x-info field="finance.pl_net_profit" /> صافي الربح / الخسارة
                        </td>
                        <td class="px-4 py-4 text-left">
                            <span class="text-xl font-bold tabular-nums {{ $report['net_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ erp_money($report['net_profit']) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>@media print { .no-print { display: none !important; } }</style>
@endpush
