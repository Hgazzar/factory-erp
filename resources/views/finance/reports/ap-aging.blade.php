@extends('layouts.app')

@section('title', 'أعمار الذمم الدائنة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">أعمار الذمم الدائنة</span>
@endsection

@section('content')
<div dir="rtl" class="finance-aging-print-report mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">أعمار الذمم الدائنة</h1>
            <p class="mt-1 text-sm text-gray-500">تحليل مستحقات الموردين حسب العمر</p>
        </div>
        <div class="no-print flex flex-wrap items-center gap-2">
            <a href="{{ route('finance.reports.ap-aging', ['as_of_date' => $asOfDate, 'export' => 'excel']) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                </svg>
                تصدير
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5m-1 7H7a2 2 0 01-2-2V9a2 2 0 012-2h10a2 2 0 012 2v5a2 2 0 01-2 2z" />
                </svg>
                طباعة
            </button>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الذمم الدائنة <x-info field="ap_aging_total_payables" /></p>
            <p class="mt-2 text-3xl font-bold text-gray-900">SAR {{ erp_money((float) $stats['total_payables']) }}</p>
        </article>
        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">المستحق الحالي <x-info field="ap_aging_current_amount" /></p>
            <p class="mt-2 text-3xl font-bold text-gray-900">SAR {{ erp_money((float) $stats['current_amount']) }}</p>
        </article>
        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">المبلغ المتأخر <x-info field="ap_aging_overdue_amount" /></p>
            <p class="mt-2 text-3xl font-bold text-gray-900">SAR {{ erp_money((float) $stats['overdue_amount']) }}</p>
        </article>
        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">إجمالي الموردين <x-info field="ap_aging_suppliers_count" /></p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ (int) $stats['suppliers_count'] }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="space-y-4 p-4">
            <form method="GET" action="{{ route('finance.reports.ap-aging') }}" class="no-print flex flex-wrap items-end justify-between gap-3">
                <div class="w-full max-w-xs space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>كما في</span>
                        <x-info field="ap_aging_as_of_date" />
                    </label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[1080px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">الرمز <x-info field="ap_aging_supplier_code" /></th>
                            <th class="px-4 py-3 text-right">اسم المورد <x-info field="ap_aging_supplier_name" /></th>
                            <th class="px-4 py-3 text-right">حالي <x-info field="ap_aging_bucket_current" /></th>
                            <th class="px-4 py-3 text-right">1-30 يوم <x-info field="ap_aging_bucket_1_30" /></th>
                            <th class="px-4 py-3 text-right">31-60 يوم <x-info field="ap_aging_bucket_31_60" /></th>
                            <th class="px-4 py-3 text-right">61-90 يوم <x-info field="ap_aging_bucket_61_90" /></th>
                            <th class="px-4 py-3 text-right">أكثر من 90 يوم <x-info field="ap_aging_bucket_over_90" /></th>
                            <th class="px-4 py-3 text-right">الإجمالي <x-info field="ap_aging_total" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $row->supplier_code ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $row->supplier_name }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ erp_money((float) $row->current_amount) }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ erp_money((float) $row->bucket_1_30) }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ erp_money((float) $row->bucket_31_60) }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ erp_money((float) $row->bucket_61_90) }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ erp_money((float) $row->bucket_over_90) }}</td>
                                <td class="px-4 py-3 font-bold text-blue-800">SAR {{ erp_money((float) $row->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        main.main-content {
            padding: 0 !important;
        }
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        .finance-aging-print-report {
            max-width: 100% !important;
        }
        .finance-aging-print-report .overflow-x-auto {
            overflow: visible !important;
            max-width: 100% !important;
        }
        .finance-aging-print-report table {
            min-width: 0 !important;
            width: 100% !important;
            table-layout: fixed;
            font-size: 8.5pt;
            border-collapse: collapse;
        }
        .finance-aging-print-report th,
        .finance-aging-print-report td {
            padding: 0.3rem 0.2rem !important;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .finance-aging-print-report th:nth-child(2),
        .finance-aging-print-report td:nth-child(2) {
            white-space: normal !important;
        }
        .finance-aging-print-report th:not(:nth-child(2)),
        .finance-aging-print-report td:not(:nth-child(2)) {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .finance-aging-print-report .rounded-lg,
        .finance-aging-print-report .rounded-xl {
            box-shadow: none !important;
        }
    }
</style>
@endpush
