@extends('layouts.app')

@section('title', 'لوحة المبيعات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المبيعات</span>
@endsection

@push('styles')
<style>
    .sales-dashboard-toolbar { background: #ffffff; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; justify-content: flex-end; }
    .sales-dashboard-toolbar .btn-new-invoice { background: #1a73e8; color: #fff; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; border: none; }
    .sales-dashboard-toolbar .btn-new-invoice:hover { background: #1557b0; color: #fff; }
    .sales-dashboard-toolbar .btn-toolbar-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; }
    .sales-dashboard-toolbar .btn-toolbar-secondary:hover { background: #f9fafb; color: #1f2937; }
    .sales-page-header { padding-bottom: 0.5rem; }
    .sales-breadcrumb { font-size: 0.875rem; color: #5f6368; }
    .sales-breadcrumb a { color: #1a73e8; text-decoration: none; }
    .sales-breadcrumb a:hover { text-decoration: underline; }
    .sales-page-title { font-size: 1.5rem; font-weight: 700; color: #1a237e; margin: 0 0 0.5rem 0; }
    .sales-dashboard-body { width: 100%; max-width: 100%; }
    .kpi-card { background: #ffffff; border-radius: 1rem; padding: 1rem 1.25rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: none; height: 100%; }
    .kpi-card .kpi-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; }
    .kpi-card .kpi-value { font-size: 1.25rem; font-weight: 700; color: #000000; }
    .kpi-card .kpi-label { font-size: 0.8rem; color: #5f6368; margin-top: 0.25rem; }
    .widget-card { background: #fff; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: none; height: 100%; }
    .widget-card .widget-title { font-weight: 600; color: #1a237e; font-size: 0.95rem; margin-bottom: 1rem; }
    .widget-card .btn-link-blue { color: #1a73e8; text-decoration: none; font-size: 0.875rem; }
    .widget-card .btn-link-blue:hover { text-decoration: underline; }
    .widget-invoice-row .widget-card { min-height: 320px; display: flex; flex-direction: column; }
    .widget-invoice-row .widget-card .widget-body { flex: 1; display: flex; flex-direction: column; }
    .widget-empty-state { text-align: center; padding: 1.5rem 0.5rem; color: #6b7280; }
    .widget-empty-state .widget-empty-icon { width: 48px; height: 48px; margin: 0 auto 0.75rem; color: #9ca3af; }
    .chart-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.75rem; font-size: 0.8rem; color: #6b7280; }
    .chart-legend span { display: inline-flex; align-items: center; gap: 0.35rem; }
    .chart-legend .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
    .chart-tabs .nav-link { color: #5f6368; border: none; padding: 0.5rem 1rem; font-size: 0.875rem; }
    .chart-tabs .nav-link.active { color: #1a73e8; font-weight: 600; background: transparent; }
    /* إجراءات سريعة - نفس مساحة العرض كباقي الأقسام */
    .quick-actions-wrap { background: #f8f8f8; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem; }
    .quick-actions-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
    .quick-actions-header .qa-title-icon { width: 22px; height: 22px; color: #60a5fa; flex-shrink: 0; }
    .quick-actions-header .qa-title { font-weight: 600; font-size: 1rem; color: #1f2937; margin: 0; }
    .quick-actions-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.75rem; }
    @media (max-width: 992px) { .quick-actions-row { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 576px) { .quick-actions-row { grid-template-columns: repeat(2, 1fr); } }
    .quick-action-card { background: #ffffff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.06); padding: 1rem 0.75rem; display: flex; flex-direction: column; align-items: center; text-align: center; text-decoration: none; color: #374151; transition: box-shadow 0.2s; }
    .quick-action-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); color: #1f2937; }
    .quick-action-card .qa-card-icon { width: 40px; height: 40px; margin-bottom: 0.6rem; display: flex; align-items: center; justify-content: center; }
    .quick-action-card .qa-card-icon.qa-icon-blue { color: #3b82f6; }
    .quick-action-card .qa-card-icon.qa-icon-purple { color: #8b5cf6; }
    .quick-action-card .qa-card-icon.qa-icon-green { color: #22c55e; }
    .quick-action-card .qa-card-icon.qa-icon-teal { color: #14b8a6; }
    .quick-action-card .qa-card-icon.qa-icon-orange { color: #f59e0b; }
    .quick-action-card .qa-card-icon.qa-icon-pink { color: #ec4899; }
    .quick-action-card .qa-card-text { font-size: 0.8rem; font-weight: 500; color: #4b5563; line-height: 1.3; }
    /* الفوترة الإلكترونية | الأقساط | العمولات | أهداف المبيعات - مطابق للصورة */
    .bottom-cards-wrap { background: #f8f8f8; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem; }
    .bottom-card-item { min-width: 0; background: #ffffff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.06); padding: 1rem 1.25rem; text-decoration: none; color: #374151; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; transition: box-shadow 0.2s; min-height: 72px; }
    .bottom-card-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: #1f2937; }
    .bottom-card-content { flex: 1; min-width: 0; }
    .bottom-card-title { font-weight: 600; font-size: 0.95rem; color: #1f2937; margin: 0 0 0.2rem 0; }
    .bottom-card-sub { font-size: 0.8rem; color: #6b7280; margin: 0; }
    .bottom-card-arrow { color: #9ca3af; font-size: 1rem; flex-shrink: 0; }
    .bottom-card-icon-wrap { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bottom-card-icon-wrap.bc-icon-green { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
    .bottom-card-icon-wrap.bc-icon-blue { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
    .bottom-card-icon-wrap.bc-icon-pink { background: rgba(236, 72, 153, 0.15); color: #db2777; }
    .bottom-card-icon-wrap.bc-icon-orange { background: rgba(245, 158, 11, 0.2); color: #d97706; }
    /* قسم ج - طبق الأصل من الصورة: بطاقتان متساويتان */
    .section-g-wrap { background: #f8f8f8; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem; }
    .section-g-card { background: #ffffff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.04); min-height: 280px; display: flex; flex-direction: column; padding: 1.5rem; }
    .section-g-card-header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 1.25rem; }
    .section-g-card-header .section-g-title-wrap { display: flex; align-items: center; gap: 0.5rem; }
    .section-g-card-header .section-g-title { font-weight: 600; font-size: 1rem; color: #1f2937; margin: 0; }
    .section-g-card-header .section-g-header-icon { width: 22px; height: 22px; color: #60a5fa; flex-shrink: 0; }
    .section-g-card-header .section-g-view-all { font-size: 0.875rem; color: #6b7280; text-decoration: none; }
    .section-g-card-header .section-g-view-all:hover { color: #374151; }
    .section-g-card-body { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 1rem 0; }
    .section-g-card-body .section-g-big-icon { width: 72px; height: 72px; color: #d1d5db; margin-bottom: 1rem; }
    .section-g-card-body .section-g-empty-text { font-size: 0.9rem; color: #9ca3af; margin: 0; }
    .section-g-card-body .section-g-btn { display: inline-block; background: #f0f0f0; color: #4b5563; font-size: 0.9rem; padding: 0.5rem 1.25rem; border-radius: 0.375rem; text-decoration: none; margin-top: 1rem; border: none; }
    .section-g-card-body .section-g-btn:hover { background: #e5e7eb; color: #374151; }
</style>
@endpush

@section('content')
<div class="mx-auto w-full max-w-full px-0" dir="rtl">
{{-- شريط أدوات علوي --}}
<div class="sales-dashboard-toolbar mb-6 flex flex-wrap items-center justify-end gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
    <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" onclick="window.location.reload();">تحديث</button>
    <a href="{{ route('sales.invoices.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">الفواتير</a>
    <a href="{{ route('sales.invoices.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
        فاتورة جديدة
    </a>
</div>

{{-- رأس الصفحة (الجزء الشمالي من المحتوى): مسار التنقل + العنوان --}}
<header class="sales-page-header mb-4">
    <nav class="sales-breadcrumb mb-2" aria-label="مسار التنقل">
        <a href="{{ route('dashboard') }}">المبيعات</a>
        <span class="mx-1">›</span>
        <span>لوحة المبيعات</span>
    </nav>
    <h1 class="sales-page-title">لوحة المبيعات</h1>
</header>

{{-- جسم الصفحة: البطاقات والإحصائيات --}}
<div class="sales-dashboard-body">

{{-- الصف الأول: 4 كروت KPI --}}
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="kpi-card flex items-start gap-3">
            <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
            </div>
            <div>
                <div class="kpi-value">SAR {{ number_format($overdueAmount ?? 0, 2) }}</div>
                <div class="kpi-label">{{ $overdueCount ?? 0 }} القوائم المتأخرة</div>
            </div>
        </div>
        <div class="kpi-card flex items-start gap-3">
            <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
            </div>
            <div>
                <div class="kpi-value">SAR {{ number_format($dueAmount ?? 0, 2) }}</div>
                <div class="kpi-label">{{ $dueCount ?? 0 }} القوائم المعلقة</div>
            </div>
        </div>
        <div class="kpi-card flex items-start gap-3">
            <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
            </div>
            <div>
                <div class="kpi-value">SAR {{ number_format($thisMonthSales ?? 0, 2) }}</div>
                <div class="kpi-label">الشهر الماضي {{ number_format($lastMonthSales ?? 0, 2) }} SAR</div>
            </div>
        </div>
        <div class="kpi-card flex items-start gap-3">
            <div class="kpi-icon bg-success bg-opacity-10 text-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
            <div>
                <div class="kpi-value">SAR {{ number_format($totalSales ?? 0, 2) }}</div>
                <div class="kpi-label flex items-center gap-1">
                    <span>{{ $salesChangePercent ?? 0 }}% مقارنة بالشهر الماضي</span>
                    <x-info field="net_sales" />
                </div>
            </div>
        </div>
</div>

{{-- الصف الثاني: 4 كروت KPI --}}
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="kpi-card flex items-center gap-3">
            <div class="kpi-icon bg-info bg-opacity-10 text-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <div>
                <div class="kpi-value">{{ $totalInvoices ?? 0 }}</div>
                <div class="kpi-label">إجمالي الفواتير</div>
            </div>
        </div>
        <div class="kpi-card flex items-center gap-3">
            <div class="kpi-icon bg-success bg-opacity-10 text-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/></svg>
            </div>
            <div>
                <div class="kpi-value">{{ $quoteConversion ?? 0 }}%</div>
                <div class="kpi-label">تحويل العروض</div>
            </div>
        </div>
        <div class="kpi-card flex items-center gap-3">
            <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
            <div>
                <div class="kpi-value">{{ $avgPaymentDays ?? 0 }} أيام</div>
                <div class="kpi-label">متوسط أيام السداد</div>
            </div>
        </div>
        <div class="kpi-card flex items-center gap-3">
            <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <div>
                <div class="kpi-value">SAR {{ number_format($avgInvoiceValue ?? 0, 2) }}</div>
                <div class="kpi-label">متوسط قيمة الفاتورة</div>
            </div>
        </div>
</div>

{{-- قسم فوق: المبيعات الشهرية | حالة الفواتير --}}
<div class="section-g-wrap mb-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
                        <h3 class="section-g-title">المبيعات الشهرية</h3>
                    </div>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg>
                    <p class="section-g-empty-text">لا توجد بيانات</p>
                </div>
            </div>
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                        <h3 class="section-g-title">حالة الفواتير</h3>
                    </div>
                    <a href="{{ route('sales.invoices.index') }}" class="section-g-view-all">عرض الكل »</a>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                    <p class="section-g-empty-text">لا توجد فواتير</p>
                    <a href="{{ route('sales.invoices.create') }}" class="section-g-btn">فاتورة جديدة</a>
                </div>
            </div>
    </div>
</div>
{{-- قسم تحت: الفواتير | أعمار المستحقات --}}
<div class="section-g-wrap mb-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                        <h3 class="section-g-title">الفواتير</h3>
                    </div>
                    <a href="{{ route('sales.invoices.index') }}" class="section-g-view-all">عرض الكل »</a>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                    <p class="section-g-empty-text">لا توجد فواتير</p>
                    <a href="{{ route('sales.invoices.create') }}" class="section-g-btn">فاتورة جديدة</a>
                </div>
            </div>
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                        <h3 class="section-g-title">أعمار المستحقات</h3>
                    </div>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                    <p class="section-g-empty-text">لا توجد بيانات</p>
                </div>
            </div>
    </div>
</div>

{{-- أفضل العملاء | أفضل المنتجات --}}
<div class="section-g-wrap mb-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                        <h3 class="section-g-title">أفضل العملاء</h3>
                    </div>
                    <a href="{{ route('sales.customers.index') }}" class="section-g-view-all">عرض الكل ←</a>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    <p class="section-g-empty-text">لا يوجد عملاء</p>
                </div>
            </div>
            <div class="section-g-card">
                <div class="section-g-card-header">
                    <div class="section-g-title-wrap">
                        <svg class="section-g-header-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
                        <h3 class="section-g-title">أفضل المنتجات</h3>
                    </div>
                    <a href="{{ route('items.index') }}" class="section-g-view-all">عرض الكل ←</a>
                </div>
                <div class="section-g-card-body">
                    <svg class="section-g-big-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
                    <p class="section-g-empty-text">لا توجد منتجات</p>
                </div>
            </div>
    </div>
</div>

{{-- إجراءات سريعة --}}
<div class="quick-actions-wrap mb-6">
    <div class="quick-actions-header">
        <svg class="qa-title-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
        <h3 class="qa-title">إجراءات سريعة</h3>
    </div>
    <div class="quick-actions-row">
        <a href="{{ route('sales.invoices.create') }}" class="quick-action-card">
            <div class="qa-card-icon qa-icon-blue"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg></div>
            <span class="qa-card-text">فاتورة جديدة</span>
        </a>
        <a href="#" class="quick-action-card">
            <div class="qa-card-icon qa-icon-purple"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg></div>
            <span class="qa-card-text">عرض سعر جديد</span>
        </a>
        <a href="#" class="quick-action-card">
            <div class="qa-card-icon qa-icon-green"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg></div>
            <span class="qa-card-text">تسجيل دفعة</span>
        </a>
        <a href="{{ route('sales.customers.create') }}" class="quick-action-card">
            <div class="qa-card-icon qa-icon-teal"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg></div>
            <span class="qa-card-text">عميل جديد</span>
        </a>
        <a href="#" class="quick-action-card">
            <div class="qa-card-icon qa-icon-orange"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg></div>
            <span class="qa-card-text">أهداف المبيعات</span>
        </a>
        <a href="#" class="quick-action-card">
            <div class="qa-card-icon qa-icon-pink"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg></div>
            <span class="qa-card-text">العمولات</span>
        </a>
    </div>
</div>

{{-- الفوترة الإلكترونية | الأقساط | العمولات | أهداف المبيعات --}}
<div class="bottom-cards-wrap">
    <div class="bottom-cards-row grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="#" class="bottom-card-item block">
                <span class="bottom-card-arrow">←</span>
                <div class="bottom-card-content">
                    <h4 class="bottom-card-title">الفوترة الإلكترونية</h4>
                    <p class="bottom-card-sub">ZATCA / ETA</p>
                </div>
                <div class="bottom-card-icon-wrap bc-icon-green"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg></div>
            </a>
            <a href="#" class="bottom-card-item block">
                <span class="bottom-card-arrow">←</span>
                <div class="bottom-card-content">
                    <h4 class="bottom-card-title">الأقساط</h4>
                    <p class="bottom-card-sub">الأقساط</p>
                </div>
                <div class="bottom-card-icon-wrap bc-icon-blue"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg></div>
            </a>
            <a href="#" class="bottom-card-item block">
                <span class="bottom-card-arrow">←</span>
                <div class="bottom-card-content">
                    <h4 class="bottom-card-title">العمولات</h4>
                    <p class="bottom-card-sub">العمولات</p>
                </div>
                <div class="bottom-card-icon-wrap bc-icon-pink"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg></div>
            </a>
            <a href="#" class="bottom-card-item block">
                <span class="bottom-card-arrow">←</span>
                <div class="bottom-card-content">
                    <h4 class="bottom-card-title">أهداف المبيعات</h4>
                    <p class="bottom-card-sub">أهداف المبيعات</p>
                </div>
                <div class="bottom-card-icon-wrap bc-icon-orange"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0zm1.5 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0z"/></svg></div>
            </a>
    </div>
</div>
</div>{{-- /.sales-dashboard-body --}}
</div>{{-- /.sales-dashboard-root --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('monthlySalesChart');
    if (!ctx) return;
    var months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months.reverse(),
            datasets: [
                { label: 'إجمالي المبيعات', data: [0,0,0,0,0,0,0,0,0,0,0,0], backgroundColor: 'rgba(26, 115, 232, 0.7)' },
                { label: 'مدفوعة', data: [0,0,0,0,0,0,0,0,0,0,0,0], backgroundColor: 'rgba(52, 168, 83, 0.7)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
