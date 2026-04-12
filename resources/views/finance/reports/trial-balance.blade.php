@extends('layouts.app')

@section('title', 'ميزان المراجعة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">ميزان المراجعة</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">ميزان المراجعة</h1>
            <p class="mt-1 text-sm text-gray-500">عرض وتحليل ميزان المراجعة</p>
        </div>
        <div class="no-print flex flex-wrap items-center gap-2">
            <a href="{{ route('finance.reports.trial-balance', array_filter(['from_date' => $fromDate, 'to_date' => $toDate, 'export' => 'excel'])) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                </svg>
                تصدير Excel
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

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="space-y-4 p-4">
            <form method="GET" action="{{ route('finance.reports.trial-balance') }}" class="no-print flex flex-row flex-wrap items-end gap-4">
                <div class="w-auto space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>من تاريخ</span>
                        <x-info field="trial_balance_from_date" />
                    </label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="h-10 w-1/2 min-w-[160px] rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="w-auto space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>إلى تاريخ</span>
                        <x-info field="trial_balance_to_date" />
                    </label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="h-10 w-1/2 min-w-[160px] rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">رمز الحساب <x-info field="trial_balance_account_code" /></th>
                            <th class="px-4 py-3 text-right">اسم الحساب <x-info field="trial_balance_account_name" /></th>
                            <th class="px-4 py-3 text-left">مدين <x-info field="trial_balance_debit" /></th>
                            <th class="px-4 py-3 text-left">دائن <x-info field="trial_balance_credit" /></th>
                            <th class="px-4 py-3 text-left">الرصيد النهائي <x-info field="trial_balance_closing_balance" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-800">{{ $row['account_code'] }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <a href="{{ route('finance.ledger.index', array_filter(['account_id' => $row['account_id'], 'from_date' => $fromDate, 'to_date' => $toDate])) }}"
                                       class="font-semibold text-blue-700 hover:text-blue-800 hover:underline">
                                        {{ $row['account_name'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-800 text-left">SAR {{ number_format((float) $row['debit'], 2) }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800 text-left">SAR {{ number_format((float) $row['credit'], 2) }}</td>
                                <td class="px-4 py-3 font-semibold text-left {{ (float) $row['closing_balance'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                    SAR {{ number_format((float) $row['closing_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 text-sm font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right text-gray-900">الإجمالي</td>
                            <td class="px-4 py-3 text-gray-900 text-left">SAR {{ number_format((float) $totalDebit, 2) }}</td>
                            <td class="px-4 py-3 text-gray-900 text-left">SAR {{ number_format((float) $totalCredit, 2) }}</td>
                            <td class="px-4 py-3 text-left">
                                @if($isBalanced)
                                    <span class="text-emerald-700">الميزان متوازن ✅</span>
                                @else
                                    <span class="text-red-600">الميزان غير متوازن</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
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
    }
</style>
@endpush
