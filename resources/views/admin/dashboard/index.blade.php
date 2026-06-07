@extends('layouts.app')

@section('title', 'لوحة الأدمن - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">لوحة الأدمن</span>
@endsection

@push('styles')
<style>
    .adm-page { background: radial-gradient(1200px 500px at 100% -10%, rgba(99,102,241,.08), transparent 55%), #f4f6fa; min-height: calc(100vh - 4rem); }
    .adm-kpi { background: rgba(255,255,255,.92); backdrop-filter: blur(8px); border: 1px solid rgba(15,23,42,.06); border-radius: 1.25rem; padding: 1.25rem 1.35rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: transform .25s ease, box-shadow .25s ease; }
    .adm-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 40px -24px rgba(15,23,42,.25); }
    .adm-kpi-label { font-size: .78rem; font-weight: 600; color: #64748b; letter-spacing: .01em; }
    .adm-kpi-value { font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; margin-top: .35rem; }
    .adm-chart-card { background: #fff; border: 1px solid rgba(15,23,42,.06); border-radius: 1.25rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); padding: 1.25rem 1.35rem; }
</style>
@endpush

@section('content')
<div dir="rtl" class="adm-page -mx-3 px-3 py-4 md:-mx-4 md:px-4">
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Administrative Dashboard</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">لوحة تحكم الأدمن</h1>
            <p class="mt-1 text-sm text-slate-500">ملخص الأداء المالي — {{ $kpis['from_date'] }} إلى {{ $kpis['to_date'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('finance.reports.profit-loss') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">تقرير P&amp;L</a>
            <a href="{{ route('system.audit.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">سجل التدقيق</a>
        </div>
    </header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="adm-kpi">
            <div class="adm-kpi-label"><x-info field="admin.kpi_net_profit" /> صافي الربح (الشهر)</div>
            <div class="adm-kpi-value tabular-nums {{ $kpis['net_profit_mtd'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ erp_money($kpis['net_profit_mtd']) }}</div>
        </div>
        <div class="adm-kpi">
            <div class="adm-kpi-label"><x-info field="admin.kpi_liquidity" /> السيولة (نقد + بنك)</div>
            <div class="adm-kpi-value tabular-nums text-slate-900">{{ erp_money($kpis['liquidity']) }}</div>
        </div>
        <div class="adm-kpi">
            <div class="adm-kpi-label"><x-info field="admin.kpi_sales" /> مبيعات مرحّلة (الشهر)</div>
            <div class="adm-kpi-value tabular-nums text-indigo-600">{{ erp_money($kpis['sales_mtd']) }}</div>
        </div>
        <div class="adm-kpi">
            <div class="adm-kpi-label"><x-info field="admin.kpi_purchases" /> مشتريات مرحّلة (الشهر)</div>
            <div class="adm-kpi-value tabular-nums text-amber-600">{{ erp_money($kpis['purchases_mtd']) }}</div>
        </div>
    </div>

    @php $channel = $kpis['channel_sales'] ?? []; @endphp
    <div class="mb-6 adm-chart-card">
        <h2 class="mb-1 text-sm font-bold text-slate-900"><x-info field="admin.widget_channel_sales" /> مبيعات POS مقابل المتجر الإلكتروني</h2>
        <p class="mb-4 text-xs text-slate-500">إيراد مُثبت محاسبياً (مكتمل / مُحصّل) — {{ $channel['from_date'] ?? $kpis['from_date'] }} إلى {{ $channel['to_date'] ?? $kpis['to_date'] }}</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-5">
                <p class="text-xs font-semibold text-indigo-700">المتجر الإلكتروني</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-indigo-900">{{ erp_money($channel['online_total'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-indigo-600/80">{{ (int) ($channel['online_count'] ?? 0) }} عملية</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-semibold text-slate-700">نقطة البيع (POS)</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-slate-900">{{ erp_money($channel['pos_total'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ (int) ($channel['pos_count'] ?? 0) }} عملية</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="adm-chart-card">
            <h2 class="mb-1 text-sm font-bold text-slate-900"><x-info field="admin.chart_sales_vs_purchases" /> المبيعات مقابل المشتريات</h2>
            <p class="mb-4 text-xs text-slate-500">آخر 6 أشهر — فواتير مرحّلة</p>
            <canvas id="chartSalesPurchases" height="140"></canvas>
        </div>
        <div class="adm-chart-card">
            <h2 class="mb-1 text-sm font-bold text-slate-900"><x-info field="admin.chart_profit_liquidity" /> صافي الربح وحركة السيولة</h2>
            <p class="mb-4 text-xs text-slate-500">اتجاهات شهرية من القيود المحاسبية</p>
            <canvas id="chartProfitLiquidity" height="140"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const labels = @json($charts['labels']);
    const sales = @json($charts['sales']);
    const purchases = @json($charts['purchases']);
    const netProfit = @json($charts['net_profit']);
    const liquidity = @json($charts['liquidity_trend']);

    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color = '#64748b';

    new Chart(document.getElementById('chartSalesPurchases'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'المبيعات', data: sales, backgroundColor: 'rgba(99,102,241,.75)', borderRadius: 8, maxBarThickness: 36 },
                { label: 'المشتريات', data: purchases, backgroundColor: 'rgba(245,158,11,.7)', borderRadius: 8, maxBarThickness: 36 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, padding: 16 } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.15)' } },
            },
        },
    });

    new Chart(document.getElementById('chartProfitLiquidity'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'صافي الربح', data: netProfit, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .35, pointRadius: 3 },
                { label: 'السيولة', data: liquidity, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.08)', fill: false, tension: .35, pointRadius: 3, yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, padding: 16 } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.15)' } },
                y1: { position: 'left', grid: { drawOnChartArea: false } },
            },
        },
    });
})();
</script>
@endpush
