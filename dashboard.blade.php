@extends('layouts.dashboard-full')

@section('title', 'لوحة التحكم - ' . config('app.name'))

@push('styles')
<style>
    .ufuq-page { background-color: #eef0f4; min-height: 100vh; font-family: 'Cairo', sans-serif; }
    .ufuq-header { background: #fff; border-bottom: 1px solid #e8eaed; padding: 0.75rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .ufuq-logo { font-weight: 700; color: #1a237e; font-size: 1.1rem; }
    .ufuq-badge { font-size: 0.65rem; background: #1a73e8; color: #fff; padding: 0.15rem 0.35rem; border-radius: 0.25rem; }
    .ufuq-erp { font-weight: 700; color: #1a237e; font-size: 1rem; }
    .ufuq-erp-sub { font-size: 0.7rem; color: #5f6368; }
    .ufuq-welcome-banner { background: linear-gradient(135deg, #1a73e8 0%, #34a853 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; font-size: 1.75rem; }
    .ufuq-card { background: #fff; border: none; border-radius: 1.75rem; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: all 0.3s ease; position: relative; cursor: pointer; }
    .ufuq-card:hover { transform: translateY(-8px); box-shadow: 0 25px 50px rgba(0,0,0,0.12), 0 0 0 2px rgba(59, 130, 246, 0.2); }
    .ufuq-card-stretch { position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; pointer-events: none; }
    .ufuq-card-body { position: relative; z-index: 2; pointer-events: auto; }
    .ufuq-card-body .ufuq-link { position: relative; z-index: 3; cursor: pointer; }
    .ufuq-card-title { color: #1a237e; font-weight: 700; font-size: 1.05rem; }
    .ufuq-card-meta { color: #5f6368; font-size: 0.8rem; }
    .ufuq-card-count { background: #f1f3f4; color: #5f6368; font-size: 0.8rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 0.5rem; }
    .ufuq-link { color: #5f6368; text-decoration: none; font-size: 0.85rem; display: block; padding: 0.25rem 0; border-radius: 0.5rem; transition: color 0.2s, background 0.2s; }
    .ufuq-link:hover { color: #1a73e8; background: rgba(26, 115, 232, 0.06); }
    .ufuq-icon { width: 56px; height: 56px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .ufuq-search-bar { background: #f8f9fa; border: 1px solid #e0e6f0; border-radius: 1rem; padding: 0.6rem 1rem; }
    .ufuq-search-bar:focus-within { background: #fff; box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2); }
    .ufuq-btn-icon { width: 40px; height: 40px; border-radius: 0.75rem; display: inline-flex; align-items: center; justify-content: center; background: transparent; border: 1px solid #e0e6f0; color: #5f6368; }
    .ufuq-btn-icon:hover { background: #f1f3f4; color: #1a73e8; }
    .ufuq-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1a73e8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
    .ufuq-card--quick { padding: 1rem 1.25rem; }
    .ufuq-card--quick:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.08); }
    .ufuq-quick-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('body')
<div class="ufuq-page">
    {{-- الهيدر: لوجو يمين | أيقونات + بحث وسط | UFUQ ERP ومساء الخير وأفاتار يسار (RTL) --}}
    <header class="ufuq-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- يمين (بداية في RTL): لوجو المستقبل الذكي --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="ufuq-logo">المستقبل الذكي</span>
                    <span class="ufuq-badge">AD</span>
                    <div class="text-muted small d-none d-md-block">asaas dfree</div>
                </div>
                {{-- وسط: أيقونات + شريط بحث --}}
                <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-center" style="max-width: 520px;">
                    <a href="#" class="ufuq-btn-icon" title="التنبيهات">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2z"/><path d="M8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628.134 2.197.459 3.742.16.767.376 1.566.663 2.258h10.244c.287-.692.502-1.491.663-2.258C11.866 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917z"/></svg>
                    </a>
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('profile.edit') }}" class="ufuq-btn-icon" title="الإعدادات">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg>
                    </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="ufuq-btn-icon" title="الوحدات">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13zM1.5 1a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-13a.5.5 0 0 0-.5-.5h-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
                    </a>
                    <div class="ufuq-search-bar flex-grow-1">
                        <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="text-secondary" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                            <input type="search" name="q" class="form-control border-0 p-0 bg-transparent" placeholder="البحث في الوحدات (منتجات، وحدات، مخازن)..." value="{{ request('q') }}" style="outline: none;">
                        </form>
                    </div>
                </div>
                {{-- يسار (نهاية في RTL): UFUQ ERP، الوحدات، مساء الخير، أفاتار --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <div class="ufuq-erp">UFUQ ERP</div>
                        <div class="ufuq-erp-sub">Enterprise Resource Planning</div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-1 text-muted text-decoration-none small">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5 5 5z"/></svg>
                        الوحدات
                    </a>
                    @php $hour = (int) now()->format('G'); $greeting = $hour < 12 ? 'صباح الخير' : 'مساء الخير'; @endphp
                    <span class="d-flex align-items-center gap-1 text-muted small">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10 0a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM9 3a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 9 3zm0 10a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/></svg>
                        {{ $greeting }}
                    </span>
                    <div class="dropdown">
                        <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="dropdown" title="الملف الشخصي">
                            <span class="ufuq-avatar">{{ strtoupper(mb_substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">الملف الشخصي</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item text-danger">تسجيل الخروج</button></form></li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- شريط البحث الرئيسي تحت الهيدر --}}
            <div class="mt-2">
                <div class="ufuq-search-bar" style="max-width: 100%;">
                    <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="text-secondary" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        <input type="search" name="q" class="form-control border-0 p-0 bg-transparent flex-grow-1" placeholder="البحث في الوحدات (منتجات، وحدات، مخازن)..." value="{{ request('q') }}" style="outline: none;">
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4 px-3 px-md-4">
        {{-- بانر الترحيب (نص متدرج + جملة فرعية) --}}
        <div class="text-center mb-4">
            <h1 class="ufuq-welcome-banner mb-1">مرحباً بعودتك! {{ Auth::user()->name ?? 'مستخدم' }}</h1>
            <p class="text-muted mb-0">اختر وحدة للوصول إلى ميزاتها وبياناتها</p>
        </div>

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
                            @if(auth()->user()->isAdminOrSuperAdmin())
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
                            @if(auth()->user()->isAdminOrSuperAdmin())
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

        {{-- إحصائيات اليوم (Today's Statistics) --}}
        @if(isset($totalProductionToday) || isset($productionOrdersToday) || isset($journalEntriesToday))
        <div class="row g-2 mb-4">
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

        {{-- الكروت الثلاثة السريعة --}}
        <div class="row g-3 mb-4">
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

        {{-- شبكة الوحدات بنفس ترتيب السكرين شوت: 4 أعمدة --}}
        <div class="row g-3">
            {{-- 1. المشتريات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('purchases.suppliers.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المشتريات"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المشتريات"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(123, 31, 162, 0.15); color: #7b1fa2;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">المشتريات</h6>
                        <p class="ufuq-card-meta mb-2">الموردون · فواتير · طلبات</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('purchases.suppliers.index') }}" class="ufuq-link">الموردون</a>
                        <a href="{{ route('purchases.invoices.index') }}" class="ufuq-link">فواتير المشتريات</a>
                        <a href="#" class="ufuq-link">طلبات الشراء</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countSuppliers) ? $countSuppliers : '5+' }}</span>
                    </div>
                </div>
            </div>
            {{-- 2. المخزون --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('items.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المخزون"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المخزون"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(239, 108, 0, 0.2); color: #e65100;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">المخزون</h6>
                        <p class="ufuq-card-meta mb-2">المخزون · المنتجات · المستودعات</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('items.index') }}" class="ufuq-link">الأصناف</a>
                        <a href="{{ route('warehouses.index') }}" class="ufuq-link">المستودعات</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countItems) ? $countItems : '5+' }}</span>
                    </div>
                </div>
            </div>
            {{-- 3. المحاسبة --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('finance.accounts.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المحاسبة"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المحاسبة"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(2, 119, 189, 0.2); color: #0277bd;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">المحاسبة</h6>
                        <p class="ufuq-card-meta mb-2">المحاسبة · الحسابات · اليومية</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('finance.accounts.index') }}" class="ufuq-link">شجرة الحسابات</a>
                        <a href="{{ route('finance.journals.index') }}" class="ufuq-link">القيود اليومية</a>
                        <a href="{{ route('finance.ledger.index') }}" class="ufuq-link">دفتر الأستاذ</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countAccounts) ? $countAccounts : '13' }}</span>
                    </div>
                </div>
            </div>
            {{-- 4. المبيعات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('sales.dashboard') }}" class="ufuq-card-stretch" aria-label="فتح وحدة المبيعات"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة المبيعات"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">المبيعات</h6>
                        <p class="ufuq-card-meta mb-2">المبيعات · العملاء · الفواتير</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('sales.dashboard') }}" class="ufuq-link">لوحة المبيعات</a>
                        <a href="{{ route('sales.customers.index') }}" class="ufuq-link">العملاء</a>
                        <a href="{{ route('sales.invoices.index') }}" class="ufuq-link">فواتير المبيعات</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countCustomers) ? $countCustomers : '6+' }}</span>
                    </div>
                </div>
            </div>
            {{-- 5. التصنيع --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    <a href="{{ route('operations.dashboard.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التصنيع"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(97, 97, 97, 0.2); color: #616161;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">التصنيع</h6>
                        <p class="ufuq-card-meta mb-2">التحكم · المواد · العمل</p>
                        <a href="{{ route('operations.dashboard.index') }}" class="ufuq-link">أوامر الإنتاج</a>
                        <a href="{{ route('operations.production-entry.create') }}" class="ufuq-link">سجلات التشغيل</a>
                        @if(auth()->user()->isAdminOrSuperAdmin() || auth()->user()->role === 'supervisor')
                        <a href="{{ route('operations.shifts.index') }}" class="ufuq-link">الورديات</a>
                        @endif
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('production-lines.index') }}" class="ufuq-link">خطوط الإنتاج</a>
                        <a href="{{ route('machines.index') }}" class="ufuq-link">الماكينات</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countProductionShifts) ? $countProductionShifts : '6+' }}</span>
                    </div>
                </div>
            </div>
            {{-- 6. الموارد البشرية --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('hr.employees.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة الموارد البشرية"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة الموارد البشرية"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(239, 108, 0, 0.2); color: #e65100;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">الموارد البشرية</h6>
                        <p class="ufuq-card-meta mb-2">الموظفون · الأقسام · الهياكل</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('hr.employees.index') }}" class="ufuq-link">الموظفين</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">{{ isset($countEmployees) ? $countEmployees : '5+' }}</span>
                    </div>
                </div>
            </div>
            {{-- 7. إدارة العملاء --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('sales.customers.index') }}" class="ufuq-card-stretch" aria-label="فتح إدارة العملاء"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="إدارة العملاء"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(2, 119, 189, 0.2); color: #0277bd;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">إدارة العملاء</h6>
                        <p class="ufuq-card-meta mb-2">العملاء · العقود · المتابعة</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('sales.customers.index') }}" class="ufuq-link">العملاء</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">5+</span>
                    </div>
                </div>
            </div>
            {{-- 8. نقاط البيع --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="وحدة نقاط البيع"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(198, 40, 40, 0.2); color: #c62828;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/><path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">نقاط البيع</h6>
                        <p class="ufuq-card-meta mb-2">نقاط البيع · المبيعات</p>
                        <span class="ufuq-card-count mt-2 d-inline-block">6+</span>
                    </div>
                </div>
            </div>
            {{-- 9. التقارير --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('reports.statement.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التقارير"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة التقارير"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(123, 31, 162, 0.15); color: #7b1fa2;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">التقارير</h6>
                        <p class="ufuq-card-meta mb-2">التقارير · المالية · المبيعات</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('reports.statement.index') }}" class="ufuq-link">ميزان المراجعة</a>
                        <a href="{{ route('finance.reports.profit-loss') }}" class="ufuq-link">الأرباح والخسائر</a>
                        <a href="{{ route('reports.tax.index') }}" class="ufuq-link">تقارير الضرائب</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">6+</span>
                    </div>
                </div>
            </div>
            {{-- 10. التدقيق --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    @if(auth()->user()->isAdminOrSuperAdmin())
                    <a href="{{ route('system.audit.index') }}" class="ufuq-card-stretch" aria-label="فتح وحدة التدقيق"></a>
                    @else
                    <a href="#" class="ufuq-card-stretch" aria-label="وحدة التدقيق"></a>
                    @endif
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(198, 40, 40, 0.2); color: #c62828;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">التدقيق</h6>
                        <p class="ufuq-card-meta mb-2">التدقيق · السجلات</p>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('system.audit.index') }}" class="ufuq-link">سجل العمليات</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">5+</span>
                    </div>
                </div>
            </div>
            {{-- 11. مكتبة المستندات --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="مكتبة المستندات"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(2, 119, 189, 0.2); color: #0277bd;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M1.75 1A1.75 1.75 0 0 0 0 2.75v10.5C0 14.216.784 15 1.75 15h12.5A1.75 1.75 0 0 0 16 13.25v-8.5A1.75 1.75 0 0 0 14.25 3H7.5a.75.75 0 0 1-.53-.22L5.03 1.28A.75.75 0 0 0 4.28 1H1.75zM1.5 2.75a.25.25 0 0 1 .25-.25h2.53l1.97 1.97a.75.75 0 0 0 .53.22h6.5a.25.25 0 0 1 .25.25v8.5a.25.25 0 0 1-.25.25H1.75a.25.25 0 0 1-.25-.25V2.75z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">مكتبة المستندات</h6>
                        <p class="ufuq-card-meta mb-2">المستندات · الملفات</p>
                        <span class="ufuq-card-count mt-2 d-inline-block">5+</span>
                    </div>
                </div>
            </div>
            {{-- 12. التخطيط --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    <a href="{{ route('dashboard') }}" class="ufuq-card-stretch" aria-label="وحدة التخطيط"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(25, 118, 210, 0.2); color: #1976d2;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">التخطيط</h6>
                        <p class="ufuq-card-meta mb-2">التخطيط · الجدولة</p>
                        <span class="ufuq-card-count mt-2 d-inline-block">5+</span>
                    </div>
                </div>
            </div>
            {{-- 13. الإدارة --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="ufuq-card h-100 p-4">
                    <a href="{{ route('profile.edit') }}" class="ufuq-card-stretch" aria-label="فتح الإعدادات"></a>
                    <div class="ufuq-card-body">
                        <div class="ufuq-icon mb-3" style="background: rgba(198, 40, 40, 0.2); color: #c62828;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg>
                        </div>
                        <h6 class="ufuq-card-title mb-2">الإدارة</h6>
                        <p class="ufuq-card-meta mb-2">إعدادات النظام · الصلاحيات</p>
                        <a href="{{ route('profile.edit') }}" class="ufuq-link">الملف الشخصي</a>
                        @if(auth()->user()->isAdminOrSuperAdmin())
                        <a href="{{ route('system.audit.index') }}" class="ufuq-link">سجل النشاط</a>
                        @endif
                        <span class="ufuq-card-count mt-2 d-inline-block">6+</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ufuq-card').forEach(function(card) {
        var stretch = card.querySelector('.ufuq-card-stretch');
        var primaryUrl = stretch ? stretch.getAttribute('href') : null;
        if (!primaryUrl || primaryUrl === '#') return;
        card.addEventListener('click', function(e) {
            if (e.target.closest('a.ufuq-link')) return;
            e.preventDefault();
            window.location.href = primaryUrl;
        });
    });
});
</script>
@endpush
@endsection
