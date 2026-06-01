@extends('layouts.app')

@section('title', 'تقارير المشتريات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تقارير المشتريات</span>
@endsection

@push('styles')
<style>
    .report-widget { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1rem 1.25rem; }
    .report-chart-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1.25rem; }
    .report-tab { padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
    .report-tab.active { background: #e5e7eb; color: #1f2937; }
    .report-tab:not(.active) { color: #6b7280; }
    .report-tab:not(.active):hover { background: #f3f4f6; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">تقارير المشتريات</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">لوحة المشتريات</a>
            <a href="{{ route('purchases.reports.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                تصدير
            </a>
        </div>
    </div>

    {{-- بطاقات الإحصائيات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">إجمالي قيمة المشتريات</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPurchaseValue, 2) }}</p>
                <p class="text-xs text-gray-500">0.0%-</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.2); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">إجمالي الطلبات</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalOrders }}</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.2); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">متوسط قيمة الطلب</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($averageOrderValue, 2) }}</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">الموردين النشطين</p>
                <p class="text-xl font-bold text-gray-900">{{ $activeSuppliersCount }}/{{ $totalSuppliersCount }}</p>
            </div>
        </div>
    </div>

    {{-- التبويبات --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <button type="button" class="report-tab active" data-tab="overview">نظرة عامة</button>
        <button type="button" class="report-tab" data-tab="trends">الاتجاهات</button>
        <button type="button" class="report-tab" data-tab="products">تحليل المنتجات</button>
        <button type="button" class="report-tab" data-tab="suppliers">تحليل الموردين</button>
    </div>

    {{-- الرسوم البيانية --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="report-chart-card">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #3b82f6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                الاتجاه الشهري
            </h3>
            <div class="position-relative" style="min-height: 280px;">
                <canvas id="monthlyTrendChart" aria-label="الاتجاه الشهري"></canvas>
            </div>
            <div class="flex flex-wrap gap-4 mt-3 justify-center">
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600"><span class="w-3 h-3 rounded-full bg-blue-500"></span> المشتريات</span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600"><span class="w-3 h-3 rounded-full bg-green-500"></span> المدفوعة</span>
            </div>
        </div>
        <div class="report-chart-card">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="color: #7c3aed;"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                توزيع الموردين
            </h3>
            <div class="position-relative" style="min-height: 280px;">
                <canvas id="suppliersDistributionChart" aria-label="توزيع الموردين"></canvas>
            </div>
            <p id="suppliersNoData" class="text-center text-gray-500 py-8 text-sm" style="display: {{ $suppliersDistribution->isEmpty() ? 'block' : 'none' }};">لا توجد بيانات</p>
        </div>
    </div>

    {{-- البطاقات السفلية الأربع --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.2); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v7.5a.5.5 0 0 1-1 0V5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h2a.5.5 0 0 1 0 1h-2A1.5 1.5 0 0 1 0 10.5v-7z"/><path d="M1 14.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v-2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-2H1.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">البضائع المستلمة</p>
                <p class="text-xl font-bold text-gray-900">{{ $receivedGoodsCount }}</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">الطلبات المعلقة</p>
                <p class="text-xl font-bold text-gray-900">{{ $pendingOrdersCount }}</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.2); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">الفواتير المدفوعة</p>
                <p class="text-xl font-bold text-gray-900">{{ $paidInvoicesCount }}</p>
            </div>
        </div>
        <div class="report-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">المنتجات المطلوبة</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($requestedProductsCount) }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var monthlyData = @json($monthlyTrend);
    var monthlyCtx = document.getElementById('monthlyTrendChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyData.map(function(d) { return d.month; }),
                datasets: [
                    {
                        label: 'المشتريات',
                        data: monthlyData.map(function(d) { return d.purchases; }),
                        fill: true,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        tension: 0.3
                    },
                    {
                        label: 'المدفوعة',
                        data: monthlyData.map(function(d) { return d.paid; }),
                        fill: true,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.15)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
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
    }

    var suppliersData = @json($suppliersDistribution);
    var suppliersCtx = document.getElementById('suppliersDistributionChart');
    if (suppliersCtx && suppliersData.length > 0) {
        var colors = ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6'];
        new Chart(suppliersCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: suppliersData.map(function(d) { return d.name; }),
                datasets: [{
                    label: 'قيمة المشتريات (SAR)',
                    data: suppliersData.map(function(d) { return d.total; }),
                    backgroundColor: suppliersData.map(function(_, i) { return colors[i % colors.length]; }),
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return v >= 1000 ? (v/1000) + 'k' : v; } }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    document.querySelectorAll('.report-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.report-tab').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
        });
    });
});
</script>
@endpush
@endsection
