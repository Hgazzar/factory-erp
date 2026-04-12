@extends('layouts.app')

@section('title', 'لوحة المشتريات - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">لوحة المشتريات</span>
@endsection

@push('styles')
<style>
    .proc-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; justify-content: flex-end; }
    .proc-btn-primary { background: #2563eb; color: #fff; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; border: none; transition: background 0.2s; }
    .proc-btn-primary:hover { background: #1d4ed8; color: #fff; }
    .proc-btn-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; }
    .proc-btn-secondary:hover { background: #f9fafb; color: #1f2937; }
    .proc-kpi-card { background: #fff; border-radius: 1rem; padding: 1rem 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; height: 100%; display: flex; align-items: flex-start; gap: 0.75rem; }
    .proc-kpi-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .proc-kpi-value { font-size: 1.25rem; font-weight: 700; color: #111827; }
    .proc-kpi-label { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
    .proc-chart-card { background: #fff; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; height: 100%; min-height: 280px; display: flex; flex-direction: column; }
    .proc-chart-title { font-weight: 600; color: #1f2937; font-size: 0.95rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .proc-table-card { background: #fff; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; height: 100%; min-height: 280px; display: flex; flex-direction: column; }
    .proc-table-title { font-weight: 600; color: #1f2937; font-size: 0.95rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .proc-table { width: 100%; font-size: 0.875rem; }
    .proc-table th { text-align: right; padding: 0.5rem 0.75rem; color: #6b7280; font-weight: 500; border-bottom: 1px solid #e5e7eb; }
    .proc-table td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #f3f4f6; }
    .proc-table tr:last-child td { border-bottom: none; }
    .proc-empty { text-align: center; padding: 2rem 1rem; color: #9ca3af; font-size: 0.9rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    {{-- شريط الإجراء السريع: الترتيب من اليمين لليسار = الموردين ثم أمر شراء جديد، ومثبت على اليسار --}}
    <div class="proc-toolbar">
        <a href="{{ route('purchases.suppliers.index') }}" class="proc-btn-secondary d-inline-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
            الموردين
        </a>
        <a href="{{ route('purchases.orders.create') }}" class="proc-btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            أمر شراء جديد
        </a>
    </div>

    <header class="mb-4">
        <h1 class="text-xl md:text-2xl font-bold text-gray-900">لوحة المشتريات</h1>
    </header>

    {{-- 4 كروت إحصائية (KPI) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="proc-kpi-card rounded-2xl shadow-sm">
                <div class="proc-kpi-icon" style="background: rgba(6, 182, 212, 0.15); color: #0891b2;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div>
                    <div class="proc-kpi-value">{{ number_format($pendingReceiptsCount ?? 0) }}</div>
                    <div class="proc-kpi-label">الاستلامات المعلقة <x-info field="procurement.pending_receipts" /></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="proc-kpi-card rounded-2xl shadow-sm">
                <div class="proc-kpi-icon" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <div class="proc-kpi-value">SAR {{ number_format($unpaidInvoicesAmount ?? 0, 2) }}</div>
                    <div class="proc-kpi-label">الفواتير غير المدفوعة ({{ $unpaidInvoicesCount ?? 0 }}) <x-info field="procurement.unpaid_invoices" /></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="proc-kpi-card rounded-2xl shadow-sm">
                <div class="proc-kpi-icon" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="proc-kpi-value">{{ number_format($pendingPurchaseOrdersCount ?? 0) }}</div>
                    <div class="proc-kpi-label">أوامر الشراء المعلقة <x-info field="procurement.pending_orders" /></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="proc-kpi-card rounded-2xl shadow-sm">
                <div class="proc-kpi-icon" style="background: rgba(99, 102, 241, 0.15); color: #4f46e5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <div class="proc-kpi-value">SAR {{ number_format($totalPurchases ?? 0, 2) }}</div>
                    <div class="proc-kpi-label">
                        إجمالي المشتريات
                        <span class="text-gray-500">{{ $purchasesChangePercent ?? 0 }}% مقارنة بالشهر الماضي</span>
                        <x-info field="procurement.total_purchases" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الصف السفلي: أفضل الموردين (يسار) | المشتريات الشهرية (يمين) --}}
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="proc-table-card">
                <h3 class="proc-table-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #3b82f6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    أفضل الموردين <x-info field="procurement.top_suppliers" />
                </h3>
                @if(count($topSuppliers ?? []) > 0)
                    <table class="proc-table">
                        <thead>
                            <tr>
                                <th>المورد</th>
                                <th class="text-left">الإجمالي (SAR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topSuppliers as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-left font-medium">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="proc-empty flex-grow-1 d-flex align-items-center justify-content-center">لا يوجد موردين</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="proc-chart-card">
                <h3 class="proc-chart-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #3b82f6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    المشتريات الشهرية <x-info field="procurement.monthly_purchases" />
                </h3>
                <div class="flex-grow-1 position-relative" style=" min-height: 220px;">
                    <canvas id="procurementMonthlyChart" aria-label="المشتريات الشهرية"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('procurementMonthlyChart');
    if (!ctx) return;
    var data = @json($monthlyData ?? []);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(function(d) { return d.label; }),
            datasets: [{
                label: 'المشتريات (SAR)',
                data: data.map(function(d) { return d.value; }),
                fill: true,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: function(v) { return v >= 1000 ? (v/1000) + 'k' : v; } }
                },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
