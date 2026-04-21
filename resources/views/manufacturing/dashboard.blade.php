@extends('layouts.app')

@section('title', 'لوحة التحكم — التصنيع - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-gray-500">التصنيع</span>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">لوحة التحكم</span>
@endsection

@section('content')
@php
    $kpiCards = [
        [
            'label' => 'إجمالي قوائم المواد',
            'value' => $totalBoms,
            'iconBg' => 'bg-slate-100',
            'iconColor' => 'text-slate-600',
            'hint' => 'manufacturing.dashboard_kpi_total_bom',
            'svg' => '<path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>',
        ],
        [
            'label' => 'قوائم المواد النشطة',
            'value' => $activeBoms,
            'iconBg' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'hint' => 'manufacturing.dashboard_kpi_active_bom',
            'svg' => '<path d="M6.079 11.9a1.5 1.5 0 0 0 1.21.578H7.5h.21a1.5 1.5 0 0 0 1.21-.578l2.51-3.18a1.5 1.5 0 0 0 .121-1.66L9.395 3.53a1.5 1.5 0 0 0-1.42-.98H6.025a1.5 1.5 0 0 0-1.42.98L3.258 7.06a1.5 1.5 0 0 0 .12 1.66l2.701 3.18z"/><path d="M2.5 14.5a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>',
        ],
        [
            'label' => 'إجمالي أوامر العمل',
            'value' => $totalWorkOrders,
            'iconBg' => 'bg-indigo-100',
            'iconColor' => 'text-indigo-600',
            'hint' => 'manufacturing.dashboard_kpi_total_wo',
            'svg' => '<path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>',
        ],
        [
            'label' => 'قيد التنفيذ',
            'value' => $inProgress,
            'iconBg' => 'bg-amber-100',
            'iconColor' => 'text-amber-600',
            'hint' => 'manufacturing.dashboard_kpi_in_progress',
            'svg' => '<path d="M4 0h8v1H4V0zM4 15h8v1H4v-1zM4 2h8v1H4V2zm0 11h8v1H4v-1zM0 4h1v8H0V4zm15 0h1v8h-1V4zM2 3h1v10H2V3zm12 0h1v10h-1V3z"/>',
        ],
        [
            'label' => 'مكتمل هذا الشهر',
            'value' => $completedThisMonth,
            'iconBg' => 'bg-emerald-100',
            'iconColor' => 'text-emerald-600',
            'hint' => 'manufacturing.dashboard_kpi_completed_month',
            'svg' => '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>',
        ],
        [
            'label' => 'الكفاءة',
            'value' => $efficiencyPercent.'%',
            'iconBg' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'hint' => 'manufacturing.dashboard_kpi_efficiency',
            'svg' => '<path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0zm10.707 9.293a1 1 0 0 0-1.414 0l-3 3a1 1 0 0 0 1.414 1.414L10 12.414l2.293 2.293a1 1 0 0 0 1.414-1.414l-3-3z"/><path fill-rule="evenodd" d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
        ],
    ];
@endphp
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                لوحة التحكم
                <x-info field="manufacturing.dashboard_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">ملخص قوائم المواد وأوامر العمل</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 justify-end">
            <a href="{{ route('manufacturing.bom-lists.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 transition">
                <span class="text-lg leading-none text-blue-600 font-bold">+</span>
                <span class="inline-flex items-center gap-1">قائمة مواد جديدة <x-info field="manufacturing.dashboard_btn_new_bom" /></span>
            </a>
            <a href="{{ route('manufacturing.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition">
                <span class="text-lg leading-none font-bold">+</span>
                <span class="inline-flex items-center gap-1">أمر جديد <x-info field="manufacturing.dashboard_btn_new_wo" /></span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach($kpiCards as $card)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex items-start justify-between gap-3 min-h-[100px]">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-gray-500 mb-1 inline-flex items-center gap-1">
                        {{ $card['label'] }}
                        <x-info field="{{ $card['hint'] }}" />
                    </div>
                    <div class="text-2xl font-bold text-gray-900 tracking-tight">{{ $card['value'] }}</div>
                </div>
                <div class="shrink-0 w-10 h-10 rounded-full {{ $card['iconBg'] }} flex items-center justify-center {{ $card['iconColor'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{!! $card['svg'] !!}</svg>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-4 px-4 sm:px-6 py-4 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-bold text-gray-900 inline-flex items-center gap-2">
                    أوامر العمل الأخيرة
                    <x-info field="manufacturing.dashboard_recent_wo" />
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">أحدث الأوامر (مسودة أو مرحّلة)</p>
            </div>
            <a href="{{ route('manufacturing.runs.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">عرض الكل</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">رقم الأمر <x-info field="manufacturing.dashboard_col_order" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">المنتج <x-info field="manufacturing.dashboard_col_product" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الكمية <x-info field="manufacturing.dashboard_col_qty" /></span></th>
                        <th class="py-3 px-4 font-semibold w-48"><span class="inline-flex items-center gap-1">التقدم <x-info field="manufacturing.dashboard_col_progress" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">تاريخ الاستحقاق <x-info field="manufacturing.dashboard_col_due" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الحالة <x-info field="manufacturing.dashboard_col_status" /></span></th>
                        <th class="py-3 px-4 font-semibold w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentWorkOrders as $wo)
                        @php $pct = $wo->progressPercent(); @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-medium text-gray-900 whitespace-nowrap">{{ $wo->reference }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $wo->finishedItem?->name_ar ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-700 tabular-nums">{{ rtrim(rtrim(number_format((float) $wo->quantity_produced, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden min-w-[72px]">
                                        <div class="h-full rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 tabular-nums w-10 text-left">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-gray-700 whitespace-nowrap">{{ $wo->production_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($wo->status === \App\Models\ManufacturingRun::STATUS_POSTED)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">مكتمل</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900">قيد التنفيذ</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('manufacturing.show', $wo) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center text-gray-500">لا توجد أوامر عمل</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
