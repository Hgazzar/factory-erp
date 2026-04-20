@extends('layouts.app')

@section('title', 'تقرير الأرباح والخسائر - ' . config('app.name'))

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
                <p class="mt-1 text-sm text-gray-500">صافي الربح = تحصيل المبيعات − تكلفة البضاعة المباعة (COGS) − مصاريف إدارية (سندات صرف مصروف).</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('finance.dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                لوحة المحاسبة
            </a>
            <button type="button" class="no-print inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" onclick="window.print()">
                طباعة / PDF
            </button>
        </div>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.reports.profit-loss') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600">من تاريخ</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600">إلى تاريخ</label>
                <input type="date" name="to_date" value="{{ $toDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-6 text-sm font-semibold text-white hover:bg-blue-700">عرض</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/80 px-4 py-4 sm:px-6">
            <p class="text-sm font-semibold text-gray-900">ملخص الفترة</p>
            @if($fromDate || $toDate)
                <p class="mt-0.5 text-xs text-gray-500">من {{ $fromDate ?? '—' }} إلى {{ $toDate ?? '—' }}</p>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-4 py-3 text-right font-semibold">البند</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right font-semibold">التفاصيل</th>
                        <th class="w-[10rem] border-b border-gray-200 px-4 py-3 text-right font-semibold tabular-nums">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="px-4 py-3 text-gray-900"><x-info field="finance.pl_sales_collections" /> إجمالي تحصيل المبيعات</td>
                        <td class="px-4 py-3 text-right text-xs leading-relaxed text-gray-500">
                            سندات قبض: {{ erp_money($receiptsTotal) }}
                            + دفعات عملاء: {{ erp_money($salesPaymentsTotal) }}
                            ({{ $receiptsCount + $salesPaymentsCount }} عملية)
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">{{ erp_money($salesCollections) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="px-4 py-3 text-gray-900"><x-info field="finance.pl_cogs" /> تكلفة البضاعة المباعة (COGS)</td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">حساب {{ $cogsCode }} — حركات القيود</td>
                        <td class="px-4 py-3 text-right font-medium tabular-nums text-red-600">− {{ erp_money($cogs) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="px-4 py-3 text-gray-900"><x-info field="finance.pl_admin_expenses" /> مصاريف إدارية (سند صرف مصروف)</td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $expensePaymentsCount }} سند</td>
                        <td class="px-4 py-3 text-right font-medium tabular-nums text-red-600">− {{ erp_money($adminExpenses) }}</td>
                    </tr>
                    <tr class="bg-gray-50/90">
                        <td class="px-4 py-4 font-bold text-gray-900" colspan="2">
                            <span class="inline-flex flex-wrap items-center gap-1"><x-info field="finance.pl_net_profit" /> صافي الربح / الخسارة</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-lg font-bold tabular-nums {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ erp_money($netProfit) }}
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
<style>
    @media print {
        .no-print { display: none !important; }
        td.tabular-nums {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
    }
</style>
@endpush
