@extends('layouts.app')

@section('title', 'لوحة التحكم - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-indigo-900 font-semibold">الرئيسية</a>
@endsection

@push('styles')
<style>
    .ufuq-page { background-color: #eef0f4; min-height: 100vh; }
    .ufuq-welcome-banner { background: linear-gradient(135deg, #1a73e8 0%, #34a853 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; font-size: 1.75rem; }
    /* Soft premium cards — no borders, pure white, gentle hover */
    .ufuq-card { --mod-color: #64748b; --mod-pastel: #f1f5f9; background: #fff; border: none; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.25s ease, box-shadow 0.25s ease; position: relative; cursor: pointer; overflow: hidden; display: flex; flex-direction: column; }
    .ufuq-card:hover { transform: translateY(-0.25rem); box-shadow: 0 12px 32px -8px rgba(0,0,0,0.08), 0 4px 16px -4px rgba(0,0,0,0.04); }
    .ufuq-card.mod-emerald { --mod-color: #10b981; --mod-pastel: #ecfdf5; }
    .ufuq-card.mod-amber { --mod-color: #f59e0b; --mod-pastel: #fffbeb; }
    .ufuq-card.mod-blue { --mod-color: #2563eb; --mod-pastel: #eff6ff; }
    .ufuq-card.mod-indigo { --mod-color: #6366f1; --mod-pastel: #eef2ff; }
    .ufuq-card.mod-violet { --mod-color: #8b5cf6; --mod-pastel: #f5f3ff; }
    .ufuq-card.mod-slate { --mod-color: #64748b; --mod-pastel: #f8fafc; }
    .ufuq-card.mod-orange { --mod-color: #f97316; --mod-pastel: #fff7ed; }
    .ufuq-card.mod-red { --mod-color: #ef4444; --mod-pastel: #fef2f2; }
    .ufuq-card-stretch { position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; pointer-events: none; }
    .ufuq-card-body { position: relative; z-index: 2; pointer-events: auto; flex: 1; display: flex; flex-direction: column; }
    .ufuq-card-head { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.5rem; }
    .ufuq-card .ufuq-icon { width: 48px; height: 48px; min-width: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; background: var(--mod-pastel) !important; color: var(--mod-color) !important; }
    .ufuq-card-title-row { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .ufuq-card-title { color: #111827; font-weight: 700; font-size: 1.05rem; margin: 0; line-height: 1.3; }
    .ufuq-card-sub { color: #94a3b8; font-size: 0.75rem; line-height: 1.35; margin-top: 0.15rem; }
    .ufuq-card-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: auto; padding-top: 0.75rem; }
    .ufuq-card-actions .ufuq-qbtn { display: inline-flex; align-items: center; justify-content: center; padding: 0.35rem 0.6rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500; text-decoration: none; background: #f8fafc; color: #475569; border: none; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .ufuq-card-actions .ufuq-qbtn:hover { background: #f1f5f9; color: var(--mod-color); }
    .ufuq-card-count { display: inline-flex; align-items: center; height: 1.375rem; padding: 0 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; background: var(--mod-pastel); color: var(--mod-color); }
    .ufuq-card-count--pulse { animation: ufuq-badge-pulse 2s ease-in-out infinite; }
    @keyframes ufuq-badge-pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.85; transform: scale(1.05); } }
    .ufuq-link { color: #475569; text-decoration: none; font-size: 0.875rem; transition: color 0.2s; }
    .ufuq-link:hover { color: #2563eb; }
    .ufuq-search-bar { background: #f8f9fa; border: 1px solid #e0e6f0; border-radius: 1rem; padding: 0.6rem 1rem; }
    .ufuq-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1a73e8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
    .ufuq-card--quick { padding: 1rem 1.25rem; border-radius: 1rem; border: none; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .ufuq-card--quick:hover { transform: translateY(-0.25rem); box-shadow: 0 12px 32px -8px rgba(0,0,0,0.08), 0 4px 16px -4px rgba(0,0,0,0.04); }
    .ufuq-quick-icon { width: 48px; height: 48px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<div class="ufuq-page">
    <main class="container-fluid pt-3 pb-4 px-3 px-md-4">
        {{-- بانر الترحيب (نص متدرج + جملة فرعية) --}}
        <div class="text-center mb-4">
            <h1 class="ufuq-welcome-banner mb-1">مرحباً بعودتك! {{ Auth::user()->name ?? 'مستخدم' }}</h1>
            <p class="text-muted mb-0">اختر وحدة للوصول إلى ميزاتها وبياناتها</p>
            @if(!empty($dashboardSystemWideSummary) && $dashboardSystemWideSummary)
                <div class="mt-2 d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                        ملخص النظام — جميع المستأجرين
                    </span>
                    <x-info field="dashboard.system_wide_summary" />
                </div>
            @endif
        </div>

        <ul class="nav nav-tabs nav-fill border-0 gap-2 mb-4" role="tablist" dir="rtl">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link active rounded-lg border-0 py-2 px-4" id="dash-tab-main" data-bs-toggle="tab" data-bs-target="#dash-pane-main" role="tab">لوحة التحكم</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-lg border-0 py-2 px-4 d-inline-flex align-items-center justify-content-center gap-1" id="dash-tab-activity" data-bs-toggle="tab" data-bs-target="#dash-pane-activity" role="tab">
                    سجل المراقبة
                    <x-info field="dashboard.activity_log" />
                </button>
            </li>
        </ul>
        <div class="tab-content">
        <div class="tab-pane fade show active" id="dash-pane-main" role="tabpanel">

        {{-- نتائج البحث (عند وجود استعلام بحث) --}}
        @if(!empty($searchResults) && isset($searchQuery))
        <div class="ufuq-card p-4 mb-4">
            <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="العودة للوحة التحكم"></a>
            <div class="ufuq-card-body">
            <h6 class="ufuq-card-title mb-3">نتائج البحث: «{{ $searchQuery }}»</h6>
            <div class="row g-3">
                @if($searchResults['items']->isNotEmpty())
                <div class="col-12 col-md-4">
                    <strong class="text-muted small d-block mb-2">الأصناف / المنتجات</strong>
                    <ul class="list-unstyled mb-0">
                        @foreach($searchResults['items'] as $item)
                        <li class="mb-1">
                            @if(auth()->user()->role === 'admin')
                            <a href="{{ route('items.edit', $item) }}" class="ufuq-link">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</a>
                            @else
                            <span class="text-muted">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if($searchResults['units']->isNotEmpty())
                <div class="col-12 col-md-4">
                    <strong class="text-muted small d-block mb-2">وحدات القياس</strong>
                    <ul class="list-unstyled mb-0">
                        @foreach($searchResults['units'] as $unit)
                        <li class="mb-1"><span class="text-muted">{{ $unit->name_ar ?? $unit->name_en ?? $unit->code }}</span></li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if($searchResults['warehouses']->isNotEmpty())
                <div class="col-12 col-md-4">
                    <strong class="text-muted small d-block mb-2">المستودعات</strong>
                    <ul class="list-unstyled mb-0">
                        @foreach($searchResults['warehouses'] as $wh)
                        <li class="mb-1">
                            @if(auth()->user()->role === 'admin')
                            <a href="{{ route('warehouses.edit', $wh) }}" class="ufuq-link">{{ $wh->name_ar ?? $wh->name_en ?? $wh->code }}</a>
                            @else
                            <span class="text-muted">{{ $wh->name_ar ?? $wh->name_en ?? $wh->code }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @if($searchResults['items']->isEmpty() && $searchResults['units']->isEmpty() && $searchResults['warehouses']->isEmpty())
            <p class="text-muted mb-0">لا توجد نتائج مطابقة.</p>
            @endif
            </div>
        </div>
        @endif

        {{-- إحصائيات اليوم (Today's Statistics) - عرض أفقي: flex-row / grid --}}
        @if(isset($totalProductionToday) || isset($productionOrdersToday) || isset($journalEntriesToday))
        <div class="row g-2 mb-4 flex-row d-flex flex-wrap">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">إنتاج اليوم</small>
                        <span class="fw-bold">{{ number_format($totalProductionToday ?? 0, 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">هالك اليوم</small>
                        <span class="fw-bold">{{ number_format($totalScrapToday ?? 0, 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">سجلات التشغيل اليوم</small>
                        <span class="fw-bold">{{ $productionRecordsToday ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">أوامر الإنتاج اليوم</small>
                        <span class="fw-bold">{{ $productionOrdersToday ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">قيود اليومية اليوم</small>
                        <span class="fw-bold">{{ $journalEntriesToday ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="ufuq-card ufuq-card--quick text-center py-2">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="إحصائيات اليوم"></a>
                    <div class="ufuq-card-body">
                        <small class="text-muted d-block">المصروفات اليوم</small>
                        <span class="fw-bold">{{ number_format($totalExpensesToday ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- قيمة المخزون + التدفق النقدي (30 يوم) --}}
        @if(isset($inventoryValueTotal))
        <div class="row g-3 mb-4 flex-row d-flex flex-wrap" dir="rtl">
            <div class="col-12 col-lg-4">
                <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <h6 class="fw-bold mb-0">قيمة المخزون الحالية</h6>
                        <x-info field="dashboard.inventory_value_total" />
                    </div>
                    <p class="display-6 fw-bold text-dark mb-2">{{ number_format($inventoryValueTotal, 2) }}</p>
                    <p class="text-muted small mb-1">خامات ({{ $inventoryRmCode ?? '1041' }}): {{ number_format($inventoryRawMaterials ?? 0, 2) }}</p>
                    <p class="text-muted small mb-0">منتج تام ({{ $inventoryFgCode ?? '1042' }}): {{ number_format($inventoryFinishedGoods ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                        <h6 class="fw-bold mb-0">التدفق النقدي — آخر 30 يوم</h6>
                        <x-info field="dashboard.cash_flow_30d" />
                    </div>
                    <div style="height: 220px;">
                        <canvas id="cashFlowChart" aria-label="رسم التدفق النقدي"></canvas>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                        <span><span class="d-inline-block rounded-circle bg-success" style="width:10px;height:10px;"></span> وارد (تحصيلات)</span>
                        <span><span class="d-inline-block rounded-circle bg-danger" style="width:10px;height:10px;"></span> صادر (مشتريات ومصاريف)</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(auth()->user()->role === 'admin' && isset($serviceOpenCount))
        <div class="row g-3 mb-4 flex-row d-flex flex-wrap" dir="rtl">
            <div class="col-6 col-md-4">
                <a href="{{ route('services.orders.index') }}" class="text-decoration-none text-dark">
                    <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <h6 class="fw-bold mb-0 small">طلبات خدمة مفتوحة</h6>
                            <x-info field="dashboard.service_open_widget" />
                        </div>
                        <p class="display-6 fw-bold text-amber-700 mb-0">{{ $serviceOpenCount }}</p>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('services.orders.index', ['priority' => 'urgent']) }}" class="text-decoration-none text-dark">
                    <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <h6 class="fw-bold mb-0 small">طلبات خدمة عاجلة (مفتوحة)</h6>
                            <x-info field="dashboard.service_urgent_widget" />
                        </div>
                        <p class="display-6 fw-bold text-danger mb-0">{{ $serviceUrgentCount }}</p>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-4">
                <a href="{{ route('services.dashboard') }}" class="text-decoration-none text-dark">
                    <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <h6 class="fw-bold mb-0 small">ضمان ينتهي خلال 30 يوماً</h6>
                            <x-info field="dashboard.service_warranty_widget" />
                        </div>
                        <p class="display-6 fw-bold text-indigo-700 mb-0">{{ $serviceWarrantyExpiringCount }}</p>
                        <p class="text-muted small mb-0 mt-1">عرض التفاصيل في لوحة الخدمات</p>
                    </div>
                </a>
            </div>
        </div>
        @if($serviceWarrantyExpiringCount > 0)
        <div class="alert alert-warning border-0 rounded-lg mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert" dir="rtl">
            <span><strong>تنبيه ضمان:</strong> يوجد {{ $serviceWarrantyExpiringCount }} أصل مثبت تنتهي فترة ضمانه خلال الثلاثين يوماً القادمة.</span>
            <a href="{{ route('services.dashboard') }}" class="btn btn-sm btn-outline-dark">لوحة الخدمات</a>
        </div>
        @endif
        @endif

        @if(auth()->user()->is_technician && auth()->user()->role !== 'admin')
        <div class="row g-3 mb-4" dir="rtl">
            <div class="col-12 col-md-6">
                <a href="{{ route('services.technician.index') }}" class="text-decoration-none text-dark">
                    <div class="ufuq-card ufuq-card--quick h-100 p-4 rounded-lg border-0 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">مهام الخدمات المسندة إليّ</h6>
                            <p class="text-muted small mb-0">فتح شاشة الفني</p>
                        </div>
                        <span class="display-6 fw-bold text-sky-700 mb-0">{{ $technicianMyOpenServiceCount }}</span>
                    </div>
                </a>
            </div>
        </div>
        @endif

        {{-- الكروت الثلاثة السريعة - عرض أفقي --}}
        <div class="row g-3 mb-4 flex-row d-flex flex-wrap">
            <div class="col-md-4">
                <div class="ufuq-card ufuq-card--quick d-flex align-items-center justify-content-between">
                    <a href="#" class="ufuq-card-stretch" aria-label="جميع الإشعارات"></a>
                    <div class="ufuq-card-body d-flex align-items-center justify-content-between w-100">
                        <div>
                            <h6 class="fw-bold mb-1">جميع الإشعارات</h6>
                            <small class="text-muted">عرض جميع الإشعارات</small>
                        </div>
                        <div class="ufuq-quick-icon bg-danger bg-opacity-10 text-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2z"/><path d="M8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628.134 2.197.459 3.742.16.767.376 1.566.663 2.258h10.244c.287-.692.502-1.491.663-2.258C11.866 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ufuq-card ufuq-card--quick d-flex align-items-center justify-content-between">
                    <a href="#" class="ufuq-card-stretch" aria-label="موافقاتي"></a>
                    <div class="ufuq-card-body d-flex align-items-center justify-content-between w-100">
                        <div>
                            <h6 class="fw-bold mb-1">موافقاتي</h6>
                            <small class="text-muted">إدارة الموافقات المعلقة</small>
                        </div>
                        <div class="ufuq-quick-icon bg-success bg-opacity-10 text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ufuq-card ufuq-card--quick d-flex align-items-center justify-content-between">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="لوحة التحكم"></a>
                    <div class="ufuq-card-body d-flex align-items-center justify-content-between w-100">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">لوحة التحكم</h6>
                            <small class="text-muted">عرض التحليلات والرؤى</small>
                        </div>
                        <div class="ufuq-quick-icon bg-primary bg-opacity-10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3 text-dark">جميع الوحدات</h5>

        {{-- شبكة الوحدات بنفس ترتيب السكرين شوت: 4 أعمدة - عرض أفقي --}}
        <div class="row g-3 flex-row d-flex flex-wrap">
            {{-- 1. المشتريات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-violet h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('purchases.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المشتريات"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المشتريات"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">المشتريات</h6>
                                    <span class="ufuq-card-count">{{ isset($countSuppliers) ? $countSuppliers : '5+' }}</span>
                                </div>
                                <p class="ufuq-card-sub">الموردون والفواتير وطلبات الشراء</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('purchases.suppliers.index') }}" class="ufuq-qbtn">الموردون</a>
                            <a href="{{ route('purchases.invoices.index') }}" class="ufuq-qbtn">فواتير</a>
                            <a href="#" class="ufuq-qbtn">طلبات</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 2. المخزون --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-amber h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('inventory.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المخزون"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المخزون"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">المخزون</h6>
                                    <span class="ufuq-card-count">{{ isset($countItems) ? $countItems : '5+' }}</span>
                                </div>
                                <p class="ufuq-card-sub">المنتجات والمستودعات والحركات</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('inventory.dashboard') }}" class="ufuq-qbtn">لوحة</a>
                            <a href="{{ route('items.index') }}" class="ufuq-qbtn">منتجات</a>
                            <a href="{{ route('warehouses.index') }}" class="ufuq-qbtn">مستودعات</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 3. المحاسبة --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-blue h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('finance.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المحاسبة"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المحاسبة"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">المحاسبة</h6>
                                    <span class="ufuq-card-count">{{ isset($countAccounts) ? $countAccounts : '13' }}</span>
                                </div>
                                <p class="ufuq-card-sub">الحسابات والقيود والتقارير</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('finance.dashboard') }}" class="ufuq-qbtn">لوحة</a>
                            <a href="{{ route('finance.accounts.index') }}" class="ufuq-qbtn">دليل</a>
                            <a href="{{ route('finance.journals.index') }}" class="ufuq-qbtn">قيود</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 4. المبيعات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-emerald h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('sales.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المبيعات"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المبيعات"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">المبيعات</h6>
                                    <span class="ufuq-card-count">{{ isset($countCustomers) ? $countCustomers : '6+' }}</span>
                                </div>
                                <p class="ufuq-card-sub">إدارة الفواتير والعملاء</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('sales.dashboard') }}" class="ufuq-qbtn">لوحة</a>
                            <a href="{{ route('sales.customers.index') }}" class="ufuq-qbtn">عملاء</a>
                            <a href="{{ route('sales.invoices.index') }}" class="ufuq-qbtn">فواتير</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 4b. الخدمات والصيانة --}}
            @if(auth()->user()->role === 'admin')
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-blue h-100 p-4">
                    <a href="{{ route('services.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة الخدمات والصيانة"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 1.5a.5.5 0 0 0-1 0v1.086c-.563.097-1.159.236-1.668.43C5.48 3.328 5 4.108 5 5c0 .653.183 1.254.528 1.755.345.502.86.93 1.52 1.263.66.332 1.453.58 2.328.744V13H8v-1h1v-1.08c.563-.098 1.159-.237 1.668-.431 1.354-.312 2.332-1.092 2.332-1.991 0-.653-.183-1.254-.528-1.755-.345-.502-.86-.93-1.52-1.263C9.792 5.18 8.999 4.932 8.124 4.768V3h.5v1H8V3h-.5v1H7V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5V3z"/><path d="M6.5 14.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">الخدمات والصيانة</h6>
                                    @if(isset($serviceOpenCount))
                                        <span class="ufuq-card-count {{ $serviceUrgentCount > 0 ? 'ufuq-card-count--pulse' : '' }}">{{ $serviceOpenCount }}</span>
                                    @endif
                                </div>
                                <p class="ufuq-card-sub">طلبات التركيب والصيانة والفنيين</p>
                            </div>
                        </div>
                        <div class="ufuq-card-actions">
                            <a href="{{ route('services.dashboard') }}" class="ufuq-qbtn">لوحة</a>
                            <a href="{{ route('services.orders.index') }}" class="ufuq-qbtn">الطلبات</a>
                            <a href="{{ route('services.orders.create') }}" class="ufuq-qbtn">طلب جديد</a>
                        </div>
                    </div>
                </div>
            </div>
            @elseif(auth()->user()->is_technician)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-blue h-100 p-4">
                    <a href="{{ route('services.technician.index') }}" class="ufuq-card-stretch" aria-label="مهام الفني"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">مهام الخدمات</h6>
                                    @if(isset($technicianMyOpenServiceCount))
                                        <span class="ufuq-card-count">{{ $technicianMyOpenServiceCount }}</span>
                                    @endif
                                </div>
                                <p class="ufuq-card-sub">طلباتك المسندة والمفتوحة</p>
                            </div>
                        </div>
                        <div class="ufuq-card-actions">
                            <a href="{{ route('services.technician.index') }}" class="ufuq-qbtn">فتح</a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- 5. التصنيع --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-slate h-100 p-4">
                    <a href="{{ route('operations.dashboard.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التصنيع"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">التصنيع</h6>
                                    <span class="ufuq-card-count">{{ isset($countProductionShifts) ? $countProductionShifts : '6+' }}</span>
                                </div>
                                <p class="ufuq-card-sub">أوامر الإنتاج والورديات</p>
                            </div>
                        </div>
                        <div class="ufuq-card-actions">
                            <a href="{{ route('operations.dashboard.index') }}" class="ufuq-qbtn">أوامر</a>
                            <a href="{{ route('operations.production-entry.create') }}" class="ufuq-qbtn">تشغيل</a>
                            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'supervisor')
                            <a href="{{ route('operations.shifts.index') }}" class="ufuq-qbtn">ورديات</a>
                            @else
                            <a href="{{ route('operations.dashboard.index') }}" class="ufuq-qbtn">لوحة</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- 6. الموارد البشرية --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-orange h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('hr.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة الموارد البشرية"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة الموارد البشرية"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">الموارد البشرية</h6>
                                    <span class="ufuq-card-count">{{ isset($countEmployees) ? $countEmployees : '5+' }}</span>
                                </div>
                                <p class="ufuq-card-sub">الموظفون والأقسام</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('hr.dashboard') }}" class="ufuq-qbtn">موظفين</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 7. إدارة العملاء --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-indigo h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('sales.customers.index') }}" class="ufuq-card-stretch" aria-label="فتح إدارة العملاء"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="إدارة العملاء"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">إدارة العملاء</h6>
                                    <span class="ufuq-card-count">5+</span>
                                </div>
                                <p class="ufuq-card-sub">العملاء والعقود والمتابعة</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('sales.customers.index') }}" class="ufuq-qbtn">عملاء</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 8. نقاط البيع --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-red h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="وحدة نقاط البيع"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/><path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">نقاط البيع</h6>
                                    <span class="ufuq-card-count">6+</span>
                                </div>
                                <p class="ufuq-card-sub">نقاط البيع والمبيعات</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- 9. التقارير --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-violet h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('reports.statement.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التقارير"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة التقارير"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">التقارير</h6>
                                    <span class="ufuq-card-count">6+</span>
                                </div>
                                <p class="ufuq-card-sub">المالية والمبيعات والضرائب</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('reports.statement.index') }}" class="ufuq-qbtn">ميزان</a>
                            <a href="{{ route('finance.reports.profit-loss') }}" class="ufuq-qbtn">أرباح</a>
                            <a href="{{ route('reports.tax.index') }}" class="ufuq-qbtn">ضرائب</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 10. التدقيق --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-slate h-100 p-4">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('system.audit.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التدقيق"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة التدقيق"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">التدقيق</h6>
                                    <span class="ufuq-card-count">5+</span>
                                </div>
                                <p class="ufuq-card-sub">سجل العمليات والنشاط</p>
                            </div>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <div class="ufuq-card-actions">
                            <a href="{{ route('system.audit.index') }}" class="ufuq-qbtn">سجل</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- 11. مكتبة المستندات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-blue h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="مكتبة المستندات"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M1.75 1A1.75 1.75 0 0 0 0 2.75v10.5C0 14.216.784 15 1.75 15h12.5A1.75 1.75 0 0 0 16 13.25v-8.5A1.75 1.75 0 0 0 14.25 3H7.5a.75.75 0 0 1-.53-.22L5.03 1.28A.75.75 0 0 0 4.28 1H1.75zM1.5 2.75a.25.25 0 0 1 .25-.25h2.53l1.97 1.97a.75.75 0 0 0 .53.22h6.5a.25.25 0 0 1 .25.25v8.5a.25.25 0 0 1-.25.25H1.75a.25.25 0 0 1-.25-.25V2.75z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">مكتبة المستندات</h6>
                                    <span class="ufuq-card-count">5+</span>
                                </div>
                                <p class="ufuq-card-sub">المستندات والملفات</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- 12. التخطيط --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-blue h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="وحدة التخطيط"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">التخطيط</h6>
                                    <span class="ufuq-card-count">5+</span>
                                </div>
                                <p class="ufuq-card-sub">التخطيط والجدولة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- 13. الإدارة --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card mod-slate h-100 p-4">
                    <a href="{{ route('profile.edit') }}" class="ufuq-card-stretch" aria-label="فتح الإعدادات"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-card-head">
                            <div class="ufuq-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg>
                            </div>
                            <div>
                                <div class="ufuq-card-title-row">
                                    <h6 class="ufuq-card-title">الإدارة</h6>
                                    <span class="ufuq-card-count">6+</span>
                                </div>
                                <p class="ufuq-card-sub">إعدادات النظام والصلاحيات</p>
                            </div>
                        </div>
                        <div class="ufuq-card-actions">
                            <a href="{{ route('profile.edit') }}" class="ufuq-qbtn">ملف شخصي</a>
                            @if(auth()->user()->role === 'admin')
                            <a href="{{ route('system.audit.index') }}" class="ufuq-qbtn">سجل النشاط</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="tab-pane fade" id="dash-pane-activity" role="tabpanel" tabindex="0">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm" dir="rtl">
                <h2 class="h6 fw-bold text-dark mb-3">آخر العمليات</h2>
                @if(isset($recentActivity) && $recentActivity->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 text-end">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">التاريخ</th>
                                <th scope="col">المستخدم</th>
                                <th scope="col">نوع العملية</th>
                                <th scope="col">الجدول</th>
                                <th scope="col">المرجع</th>
                                <th scope="col">القيم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $row)
                            <tr>
                                <td class="text-nowrap small">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $row->user?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $actionLabels = ['create' => 'إنشاء', 'update' => 'تحديث', 'delete' => 'حذف', 'complete' => 'إتمام إنتاج'];
                                    @endphp
                                    {{ $actionLabels[$row->action] ?? $row->action }}
                                </td>
                                <td>
                                    @php
                                        $tableLabels = [
                                            'sales_orders' => 'أمر بيع',
                                            'production_orders' => 'أمر إنتاج',
                                            'bom' => 'BOM',
                                            'delivery_orders' => 'أمر توريد',
                                            'purchase_invoices' => 'فاتورة مشتريات',
                                            'service_orders' => 'طلب خدمة',
                                        ];
                                    @endphp
                                    {{ $tableLabels[$row->table_name] ?? $row->table_name }}
                                </td>
                                <td class="text-muted small">#{{ $row->record_id }}</td>
                                <td class="small text-muted" style="max-width: 320px;">
                                    <x-audit-activity-details :trail="$row" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">لا توجد عمليات مسجّلة بعد.</p>
                @endif
            </div>
        </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cfCanvas = document.getElementById('cashFlowChart');
    if (cfCanvas && typeof Chart !== 'undefined') {
        var cfLabels = @json($cashFlowLabels ?? []);
        var cfIn = @json($cashFlowIn ?? []);
        var cfOut = @json($cashFlowOut ?? []);
        if (cfLabels.length > 0) {
            new Chart(cfCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: cfLabels,
                    datasets: [
                        {
                            label: 'وارد (تحصيلات)',
                            data: cfIn,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.12)',
                            fill: true,
                            tension: 0.25,
                            pointRadius: 0,
                        },
                        {
                            label: 'صادر (صرف)',
                            data: cfOut,
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.08)',
                            fill: true,
                            tension: 0.25,
                            pointRadius: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var v = ctx.parsed.y != null ? ctx.parsed.y : 0;
                                    return ctx.dataset.label + ': ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                        y: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString(); } } },
                    },
                },
            });
        }
    }

    document.querySelectorAll('.ufuq-card').forEach(function(card) {
        var stretch = card.querySelector('.ufuq-card-stretch');
        var primaryUrl = stretch ? stretch.getAttribute('href') : null;
        if (!primaryUrl || primaryUrl === '#') return;
        card.addEventListener('click', function(e) {
            if (e.target.closest('a.ufuq-link, a.ufuq-qbtn')) return;
            e.preventDefault();
            window.location.href = primaryUrl;
        });
    });
});
</script>
@endpush
