@extends('layouts.pos')

@section('title', 'لوحة نقاط البيع - '.config('app.name'))

@section('content')
@php
    $paymentLabels = [
        'cash' => 'نقدي',
        'card' => 'بطاقة',
        'bank' => 'تحويل بنكي',
        'mixed' => 'مختلط',
        'other' => 'أخرى',
    ];
@endphp

<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 space-y-6" dir="rtl">
    <div class="flex flex-col lg:flex-row flex-wrap items-stretch lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                لوحة نقاط البيع
                <x-info field="pos.dashboard_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">ملخص المبيعات والجلسات والأجهزة للفترة المحددة.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 justify-end">
            <form method="get" action="{{ route('pos.dashboard') }}" class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">التاريخ</label>
                <input type="date" name="date" value="{{ $filterDate }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition">
                    تحديث
                </button>
            </form>
            <a href="{{ route('pos.dashboard', ['date' => now()->toDateString()]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 transition">
                اليوم
            </a>
        </div>
    </div>

    @php
        $kpiCards = [
            ['label' => 'مبيعات اليوم', 'value' => $kpis['today_sales_label'], 'hint' => 'pos.dashboard_kpi_today_sales', 'wrap' => 'bg-emerald-50 text-emerald-700', 'svg' => '<path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/>'],
            ['label' => 'الأجهزة النشطة', 'value' => $kpis['active_devices'].' / '.$kpis['devices_total'], 'hint' => 'pos.dashboard_kpi_active_devices', 'wrap' => 'bg-blue-50 text-blue-700', 'svg' => '<path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/><path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/>'],
            ['label' => 'الجلسات المفتوحة', 'value' => $kpis['open_sessions'], 'hint' => 'pos.dashboard_kpi_open_sessions', 'wrap' => 'bg-amber-50 text-amber-700', 'svg' => '<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>'],
            ['label' => 'معاملات اليوم', 'value' => $kpis['today_transactions'], 'hint' => 'pos.dashboard_kpi_today_transactions', 'wrap' => 'bg-violet-50 text-violet-700', 'svg' => '<path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($kpiCards as $card)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-gray-500 mb-2 inline-flex items-center gap-1">
                        {{ $card['label'] }}
                        <x-info field="{{ $card['hint'] }}" />
                    </div>
                    <div class="text-xl font-bold text-gray-900 tracking-tight tabular-nums">{{ $card['value'] }}</div>
                </div>
                <div class="shrink-0 w-11 h-11 rounded-xl {{ $card['wrap'] }} flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{!! $card['svg'] !!}</svg>
                </div>
            </div>
        @endforeach
    </div>

    @if(! empty($storeOnlinePanel))
        <x-store.online-metrics-panel :panel="$storeOnlinePanel" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">الاتجاه بالساعة</h2>
                <x-info field="pos.chart_hourly" />
            </div>
            <div class="p-5">
                <canvas id="posChartHourly" height="220" aria-label="مبيعات بالساعة"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">المبيعات حسب طريقة الدفع</h2>
                <x-info field="pos.chart_payment_method" />
            </div>
            <div class="p-5">
                @if(count($paymentChart))
                    <canvas id="posChartPayment" height="220" aria-label="توزيع طرق الدفع"></canvas>
                @else
                    <p class="text-sm text-gray-500 mb-0">لا توجد بيانات للفترة المحددة.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden xl:col-span-1">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">
                    المعاملات الأخيرة
                    <x-info field="pos.table_recent_sales" />
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الإيصال <x-info field="pos.col_receipt" /></span></th>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">المبلغ <x-info field="pos.col_amount_sar" /></span></th>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الجهاز <x-info field="pos.col_device" /></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                                <td class="py-3 px-4 font-medium">
                                    <a href="{{ route('pos.sales.show', $sale) }}" class="text-blue-600 hover:text-blue-800 font-semibold">{{ $sale->receipt_number }}</a>
                                </td>
                                <td class="py-3 px-4 tabular-nums text-gray-800">{{ $erpCurrencyCode }} {{ number_format((float) $sale->total_price, 2) }}</td>
                                <td class="py-3 px-4 text-gray-600">{{ $sale->posDevice?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 px-4 text-center text-gray-500">لا توجد معاملات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden xl:col-span-1">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">
                    أكثر المنتجات مبيعاً
                    <x-info field="pos.table_best_products" />
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الصنف <x-info field="pos.best_products_col_item" /></span></th>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الكمية <x-info field="pos.best_products_col_qty" /></span></th>
                            <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">القيمة <x-info field="pos.best_products_col_value" /></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bestSellingProducts as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900">{{ $row['name'] }}</span>
                                    @if(! empty($row['code']))
                                        <span class="text-gray-500 text-xs block">{{ $row['code'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 tabular-nums">{{ rtrim(rtrim(number_format($row['qty_sold'], 4, '.', ''), '0'), '.') ?: '0' }}</td>
                                <td class="py-3 px-4 tabular-nums">{{ $erpCurrencyCode }} {{ number_format($row['revenue_sar'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 px-4 text-center text-gray-500">لا توجد بيانات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden xl:col-span-1">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">
                    حالة الأجهزة
                    <x-info field="pos.table_devices" />
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">الجهاز</th>
                            <th class="py-3 px-4 font-semibold">الحالة</th>
                            <th class="py-3 px-4 font-semibold">المستودع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $d)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                                <td class="py-3 px-4 font-medium text-gray-900">{{ $d['name'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ $d['status'] }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $d['warehouse_name'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 px-4 text-center text-gray-500">لم يُعرَّف جهاز بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currencyLabel = @json($erpCurrencyCode ?? 'SAR');
    const hourlyData = @json(array_values($hourlySar));
    const hourlyLabels = @json($hourlyLabels);
    const ctxH = document.getElementById('posChartHourly');
    if (ctxH && window.Chart) {
        new Chart(ctxH.getContext('2d'), {
            type: 'line',
            data: {
                labels: hourlyLabels.map(function (h) { return String(h); }),
                datasets: [{
                    label: currencyLabel,
                    data: hourlyData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    fill: true,
                    tension: 0.25,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var v = ctx.parsed.y != null ? ctx.parsed.y : ctx.raw;
                                return currencyLabel + ' ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) { return Number(value).toFixed(2); }
                        }
                    }
                }
            }
        });
    }

    const payRaw = @json($paymentChart);
    const payLabelsMap = @json($paymentLabels);
    const ctxP = document.getElementById('posChartPayment');
    if (ctxP && window.Chart && Object.keys(payRaw).length) {
        const labels = Object.keys(payRaw).map(function (k) { return payLabelsMap[k] || k; });
        const data = Object.values(payRaw).map(function (v) { return Number(v); });
        new Chart(ctxP.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#2563eb', '#16a34a', '#ca8a04', '#dc2626', '#9333ea'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var val = ctx.parsed || 0;
                                var pct = total ? Math.round((val / total) * 100) : 0;
                                return currencyLabel + ' ' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
