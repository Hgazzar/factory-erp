@extends('layouts.app')

@section('title', 'عرض الميزانية - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.budgets.index') }}" class="text-gray-500 hover:text-blue-600">الموازنات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">عرض</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $budget->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">الفترة: {{ $budget->start_date->format('Y-m-d') }} - {{ $budget->end_date->format('Y-m-d') }}</p>
            <p class="mt-1 text-sm text-gray-500">السنة المالية: {{ $budget->fiscal_year }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('finance.budgets.export', $budget) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                تصدير PDF
            </a>
            <a href="{{ route('finance.budgets.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                رجوع
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">المخطط <x-info field="budget_total_planned" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ number_format((float) ($analysis['totals']['planned'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">الفعلي <x-info field="budget_total_actual" /></p>
            <p class="mt-2 text-2xl font-bold text-gray-900">SAR {{ number_format((float) ($analysis['totals']['actual'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            @php
                $totalVariance = (float) ($analysis['totals']['variance'] ?? 0);
                $totalVarianceClass = $totalVariance > 0 ? 'text-red-600' : 'text-emerald-600';
            @endphp
            <p class="text-xs font-medium text-gray-500">الانحراف <x-info field="budget_variance" /></p>
            <p class="mt-2 text-2xl font-bold {{ $totalVarianceClass }}">SAR {{ number_format($totalVariance, 2) }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-right">الحساب <x-info field="budget_account" /></th>
                        <th class="px-4 py-3 text-right">مركز التكلفة <x-info field="budget_cost_center" /></th>
                        <th class="px-4 py-3 text-right">المخطط <x-info field="budget_total_planned" /></th>
                        <th class="px-4 py-3 text-right">الفعلي <x-info field="budget_total_actual" /></th>
                        <th class="px-4 py-3 text-right">الانحراف <x-info field="budget_variance" /></th>
                        <th class="px-4 py-3 text-right">نسبة الانحراف <x-info field="budget_variance_percent" /></th>
                        <th class="px-4 py-3 text-right">استهلاك الموازنة <x-info field="budget_consumption" /></th>
                        <th class="px-4 py-3 text-right">التوزيع الشهري</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($analysis['lines'] ?? []) as $line)
                        @php
                            $overBudget = (float) $line['actual'] > (float) $line['planned'];
                            $varianceClass = $overBudget ? 'text-red-600' : 'text-emerald-700';
                            $barClass = $overBudget ? 'bg-red-500' : 'bg-emerald-500';
                            $consumePercent = max(0, min(100, (float) $line['consumption_percent']));
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-800">
                                <div class="font-medium">{{ $line['account_name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $line['account_code'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $line['cost_center'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-800">SAR {{ number_format((float) $line['planned'], 2) }}</td>
                            <td class="px-4 py-3 text-gray-800">SAR {{ number_format((float) $line['actual'], 2) }}</td>
                            <td class="px-4 py-3 font-semibold {{ $varianceClass }}">SAR {{ number_format((float) $line['variance'], 2) }}</td>
                            <td class="px-4 py-3 {{ $varianceClass }}">{{ number_format((float) $line['variance_percent'], 2) }}%</td>
                            <td class="px-4 py-3">
                                <div class="w-56 rounded-full bg-gray-100">
                                    <div class="h-2.5 rounded-full {{ $barClass }}" style="width: {{ $consumePercent }}%"></div>
                                </div>
                                <div class="mt-1 text-xs {{ $varianceClass }}">{{ number_format((float) $line['consumption_percent'], 1) }}%</div>
                            </td>
                            <td class="px-4 py-3">
                                <details class="group rounded-lg border border-gray-200 bg-white p-2">
                                    <summary class="cursor-pointer list-none text-xs font-semibold text-blue-700">عرض شهري</summary>
                                    <div class="mt-2 overflow-x-auto">
                                        <table class="w-full min-w-[420px] text-xs">
                                            <thead class="bg-gray-50 text-gray-500">
                                                <tr>
                                                    <th class="px-2 py-1 text-right">الشهر</th>
                                                    <th class="px-2 py-1 text-right">المخطط</th>
                                                    <th class="px-2 py-1 text-right">الفعلي</th>
                                                    <th class="px-2 py-1 text-right">الانحراف</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach(($line['monthly'] ?? []) as $month)
                                                    <tr>
                                                        <td class="px-2 py-1">{{ $month['label'] }}</td>
                                                        <td class="px-2 py-1">SAR {{ number_format((float) $month['planned'], 2) }}</td>
                                                        <td class="px-2 py-1">SAR {{ number_format((float) $month['actual'], 2) }}</td>
                                                        <td class="px-2 py-1 {{ (float) $month['variance'] > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                                            SAR {{ number_format((float) $month['variance'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center text-sm text-gray-500">لا توجد بنود داخل هذه الموازنة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

