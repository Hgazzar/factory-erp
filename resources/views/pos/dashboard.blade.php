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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">لوحة نقاط البيع</h1>
        <p class="text-muted mb-0 small">ملخص المبيعات والجلسات والأجهزة للفترة المحددة.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="get" action="{{ route('pos.dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="small text-muted mb-0">التاريخ</label>
            <input type="date" name="date" value="{{ $filterDate }}" class="form-control form-control-sm" style="width: 11rem;">
            <button type="submit" class="btn btn-sm btn-primary rounded-lg">تحديث</button>
        </form>
        <a href="{{ route('pos.dashboard', ['date' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary rounded-lg">اليوم</a>
    </div>
</div>

{{-- KPIs --}}
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">مبيعات اليوم <x-info field="pos.dashboard_kpi_today_sales" /></div>
                <div class="fs-4 fw-bold tabular-nums">{{ $kpis['today_sales_label'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">الأجهزة النشطة <x-info field="pos.dashboard_kpi_active_devices" /></div>
                <div class="fs-4 fw-bold">{{ $kpis['active_devices'] }}</div>
                <div class="small text-muted">{{ $kpis['devices_total'] }} الإجمالي</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">الجلسات المفتوحة <x-info field="pos.dashboard_kpi_open_sessions" /></div>
                <div class="fs-4 fw-bold">{{ $kpis['open_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">معاملات اليوم <x-info field="pos.dashboard_kpi_today_transactions" /></div>
                <div class="fs-4 fw-bold">{{ $kpis['today_transactions'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">الاتجاه بالساعة</span>
                <x-info field="pos.chart_hourly" />
            </div>
            <div class="card-body">
                <canvas id="posChartHourly" height="220" aria-label="مبيعات بالساعة"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">المبيعات حسب طريقة الدفع</span>
                <x-info field="pos.chart_payment_method" />
            </div>
            <div class="card-body">
                @if(count($paymentChart))
                    <canvas id="posChartPayment" height="220" aria-label="توزيع طرق الدفع"></canvas>
                @else
                    <p class="text-muted mb-0 small">لا توجد بيانات للفترة المحددة.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">المعاملات الأخيرة</span>
                <x-info field="pos.table_recent_sales" />
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><x-info field="pos.col_receipt" /></th>
                                <th><x-info field="pos.col_amount_sar" /></th>
                                <th><x-info field="pos.col_device" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('pos.sales.show', $sale) }}" class="text-decoration-none">{{ $sale->receipt_number }}</a>
                                    </td>
                                    <td class="tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $sale->total_price, 2) }}</td>
                                    <td class="small">{{ $sale->posDevice?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">لا توجد معاملات.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">أكثر المنتجات مبيعاً</span>
                <x-info field="pos.table_best_products" />
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>الصنف</th>
                                <th>الكمية</th>
                                <th>القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bestSellingProducts as $row)
                                <tr>
                                    <td class="small">
                                        <span class="fw-medium">{{ $row['name'] }}</span>
                                        @if(!empty($row['code']))
                                            <span class="text-muted d-block">{{ $row['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="tabular-nums">{{ rtrim(rtrim(number_format($row['qty_sold'], 4, '.', ''), '0'), '.') ?: '0' }}</td>
                                    <td class="tabular-nums">{{ $erpCurrencyCode }} {{ number_format($row['revenue_sar'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">لا توجد بيانات.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">حالة الأجهزة</span>
                <x-info field="pos.table_devices" />
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>الجهاز</th>
                                <th>الحالة</th>
                                <th>المستودع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($devices as $d)
                                <tr>
                                    <td class="small fw-medium">{{ $d['name'] }}</td>
                                    <td><span class="badge rounded-pill bg-light text-dark">{{ $d['status'] }}</span></td>
                                    <td class="small text-muted">{{ $d['warehouse_name'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">لم يُعرَّف جهاز بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.12)',
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
                    backgroundColor: ['#1a73e8', '#34a853', '#fbbc04', '#ea4335', '#9334e6'],
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
