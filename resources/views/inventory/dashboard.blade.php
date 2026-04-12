@extends('layouts.app')

@section('title', 'لوحة المخزون - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المخزون</span>
@endsection

@push('styles')
<style>
    .inv-block {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }

    .inv-topbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .inv-tabs {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .inv-tab {
        border: 1px solid #d1d5db;
        border-radius: 0.4rem;
        background: #f9fafb;
        color: #4b5563;
        padding: 0.38rem 0.8rem;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inv-tab.is-active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .inv-icon-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #d1d5db;
        background: #fff;
        border-radius: 0.4rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .inv-kpi-card {
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
        min-height: 92px;
    }

    .inv-kpi-head {
        color: #6b7280;
        font-size: 0.83rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inv-kpi-value {
        color: #111827;
        font-size: 1.7rem;
        line-height: 1;
        font-weight: 700;
    }

    .inv-kpi-sub {
        color: #9ca3af;
        font-size: 0.78rem;
        margin-top: 0.28rem;
    }

    .inv-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .inv-chart-card {
        padding: 1rem;
        display: flex;
        flex-direction: column;
    }

    .inv-chart-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inv-chart-wrap {
        height: 300px;
        position: relative;
    }

    .chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 0.75rem;
        font-size: 0.8rem;
        color: #6b7280;
    }

    .chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .chart-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
    }

</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    <section class="w-full">
        <div class="inv-topbar">
            <div class="inv-tabs">
                <a href="{{ route('items.index') }}" class="inv-tab is-active">المنتجات</a>
                <a href="{{ route('warehouses.index') }}" class="inv-tab">المستودعات</a>
                <a href="{{ route('inventory.stock-in.create') }}" class="inv-tab">إذن إضافة مخزني</a>
            </div>
        </div>

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">لوحة المخزون</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">القيمة الإجمالية <x-info field="inventory.total_value" /></div>
                        <div class="inv-kpi-value">SAR {{ number_format($totalValue ?? 0, 2) }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a.5.5 0 0 1-.354-.146l-3-3a.5.5 0 1 1 .708-.708L8 10.793l4.646-4.647a.5.5 0 0 1 .708.708l-5 5A.5.5 0 0 1 8 12z"/></svg>
                    </div>
                </div>
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">نفاد المخزون <x-info field="inventory.out_of_stock" /></div>
                        <div class="inv-kpi-value">{{ $outOfStockCount ?? 0 }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-red-100 text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                    </div>
                </div>
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">تنبيهات المخزون المنخفض <x-info field="inventory.low_stock_alerts" /></div>
                        <div class="inv-kpi-value">{{ $lowStockCount ?? 0 }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5z"/><path d="M8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
                    </div>
                </div>
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">إجمالي المنتجات <x-info field="inventory.total_products" /></div>
                        <div class="inv-kpi-value">{{ $totalProducts ?? 0 }}</div>
                        <div class="inv-kpi-sub">{{ $activeProducts ?? 0 }} نشط</div>
                    </div>
                    <div class="inv-kpi-icon bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                    </div>
                </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <a href="{{ route('production-orders.index') }}" class="inv-block inv-kpi-card rounded-lg hover:shadow-md transition-shadow no-underline text-inherit block">
                <div>
                    <div class="inv-kpi-head">أوامر إنتاج معلّقة <x-info field="inventory.widget_pending_production" /></div>
                    <div class="inv-kpi-value">{{ $pendingProductionOrdersCount ?? 0 }}</div>
                    <div class="inv-kpi-sub">حالة «معلق» — اضغط لأوامر الإنتاج</div>
                </div>
                <div class="inv-kpi-icon bg-indigo-100 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M6.079 11.9a1.5 1.5 0 0 0 1.21.578H7.5h.21a1.5 1.5 0 0 0 1.21-.578l2.51-3.18a1.5 1.5 0 0 0 .121-1.66L9.395 3.53a1.5 1.5 0 0 0-1.42-.98H6.025a1.5 1.5 0 0 0-1.42.98L3.258 7.06a1.5 1.5 0 0 0 .12 1.66l2.701 3.18z"/></svg>
                </div>
            </a>
            <a href="{{ route('sales.delivery-orders.index') }}" class="inv-block inv-kpi-card rounded-lg hover:shadow-md transition-shadow no-underline text-inherit block">
                <div>
                    <div class="inv-kpi-head">أوامر توريد بانتظار التسليم <x-info field="inventory.widget_pending_delivery" /></div>
                    <div class="inv-kpi-value">{{ $pendingDeliveryOrdersCount ?? 0 }}</div>
                    <div class="inv-kpi-sub">لم يُؤكَّد التسليم بعد — اضغط لقائمة التوريد</div>
                </div>
                <div class="inv-kpi-icon bg-sky-100 text-sky-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 0 12.5v-9z"/><path d="M2 5h8v1H2V5zm0 3h6v1H2V8zm0 3h8v1H2v-1z"/></svg>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">الجرد المعلق <x-info field="inventory.pending_stocktake" /></div>
                        <div class="inv-kpi-value">{{ $pendingStocktake ?? 0 }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-fuchsia-100 text-fuchsia-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2.5A1.5 1.5 0 0 1 3.5 1h9A1.5 1.5 0 0 1 14 2.5v11a.5.5 0 0 1-.757.429L8 10.5l-5.243 3.429A.5.5 0 0 1 2 13.5v-11Z"/></svg>
                    </div>
                </div>
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">التحويلات المعلقة <x-info field="inventory.pending_transfers" /></div>
                        <div class="inv-kpi-value">{{ $pendingTransfers ?? 0 }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-cyan-100 text-cyan-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.5 1.5a.5.5 0 0 1 0 1h-8A1.5 1.5 0 0 0 2 4v1.5a.5.5 0 0 1-1 0V4a2.5 2.5 0 0 1 2.5-2.5h8z"/><path d="M14 10.5a.5.5 0 0 1 1 0V12a2.5 2.5 0 0 1-2.5 2.5h-8a.5.5 0 0 1 0-1h8A1.5 1.5 0 0 0 14 12v-1.5z"/><path d="M12.354 3.646a.5.5 0 0 1 0 .708L10.707 6h4.793a.5.5 0 0 1 0 1h-4.793l1.647 1.646a.5.5 0 1 1-.708.708l-2.5-2.5a.5.5 0 0 1 0-.708l2.5-2.5a.5.5 0 0 1 .708 0z"/><path d="M3.646 12.354a.5.5 0 0 1 0-.708L5.293 10H.5a.5.5 0 0 1 0-1h4.793L3.646 7.354a.5.5 0 1 1 .708-.708l2.5 2.5a.5.5 0 0 1 0 .708l-2.5 2.5a.5.5 0 0 1-.708 0z"/></svg>
                    </div>
                </div>
                <div class="inv-block inv-kpi-card">
                    <div>
                        <div class="inv-kpi-head">المستودعات <x-info field="inventory.warehouses_ratio" /></div>
                        <div class="inv-kpi-value">{{ $warehousesActive ?? 0 }} / {{ $warehousesCount ?? 0 }}</div>
                    </div>
                    <div class="inv-kpi-icon bg-violet-100 text-violet-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v12h-2v1h-1v-1H4v1H3v-1H1V2a1 1 0 0 1 1-1h12ZM2 2v11h12V2H2ZM4 4h2v2H4V4Zm3 0h2v2H7V4Zm3 0h2v2h-2V4ZM4 7h2v2H4V7Zm3 0h2v2H7V7Zm3 0h2v2h-2V7Z"/></svg>
                    </div>
                </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="inv-block inv-chart-card">
                    <div class="inv-chart-title">المخزون حسب المستودع <x-info field="inventory.stock_by_warehouse" /></div>
                    <div class="inv-chart-wrap">
                        <canvas id="inventoryByWarehouseChart"></canvas>
                    </div>
                </div>
                <div class="inv-block inv-chart-card">
                    <div class="inv-chart-title">حالة المخزون <x-info field="inventory.stock_status" /></div>
                    <div class="inv-chart-wrap">
                        <canvas id="inventoryStatusChart"></canvas>
                        <div class="chart-legend">
                            @foreach($inventoryStatus ?? [] as $s)
                                <span class="chart-legend-item">
                                    <span class="chart-legend-dot" style="background-color: {{ $s['color'] }};"></span>
                                    {{ $s['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawByWarehouse = @json($inventoryByWarehouse ?? []);
    const rawStatusData = @json($inventoryStatus ?? []);

    // تجهيز بيانات المستودعات مع قيم افتراضية عند عدم وجود بيانات
    let byWarehouse = rawByWarehouse;
    const totalQty = rawByWarehouse.reduce((s, w) => s + (w.quantity || 0), 0);
    if (rawByWarehouse.length === 0 || totalQty === 0) {
        byWarehouse = [{ label: 'لا توجد بيانات', quantity: 0 }];
    }

    const ctxWarehouse = document.getElementById('inventoryByWarehouseChart');
    if (ctxWarehouse) {
        new Chart(ctxWarehouse.getContext('2d'), {
            type: 'bar',
            data: {
                labels: byWarehouse.map(w => w.label),
                datasets: [{
                    label: 'الكمية',
                    data: byWarehouse.map(w => w.quantity),
                    backgroundColor: 'rgba(234, 88, 12, 0.7)',
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true },
                },
            },
        });
    }

    // تجهيز بيانات حالة المخزون مع قيم افتراضية عند عدم وجود بيانات
    let statusData = rawStatusData;
    const totalStatus = rawStatusData.reduce((s, x) => s + (x.count || 0), 0);
    if (rawStatusData.length === 0 || totalStatus === 0) {
        statusData = [{
            label: 'لا توجد بيانات',
            count: 1,
            color: '#e5e7eb',
        }];
    }

    const ctxStatus = document.getElementById('inventoryStatusChart');
    if (ctxStatus) {
        new Chart(ctxStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => s.label),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: statusData.map(s => s.color),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: { legend: { display: false } },
            },
        });
    }
});
</script>
@endpush
@endsection
