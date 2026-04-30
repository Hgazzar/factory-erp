<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MIRADA ERP')</title>

    <!-- Bootstrap 5 RTL for legacy content -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @filamentStyles
    @livewireStyles
    {{-- SweetAlert2: في <head> لجميع البيئات حتى تكون Swal متاحة قبل أي سكربت في الصفحة --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @env('local')
        {{-- تطوير محلي: Vite (أو public/hot لخادم التطوير) --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @if(file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        {{--
            احتياط إنتاج (مثلاً Railway عند فشل Vite / Unexpected token '<'):
            Alpine من CDN حتى تعمل النماذج بدون الاعتماد على ملفات build فقط.
            SweetAlert2 تُحمّل أعلاه في <head> لجميع البيئات.
            متغيرات Railway: احذف ASSET_URL إن وُجد أو اجعله بالضبط https://factory-erp-production.up.railway.app
            بعد النشر: /force-deploy أو php artisan view:clear
        --}}
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endenv

    <style>
        /* Fallback when Tailwind/Vite is not loaded - keeps layout from collapsing */
        body { font-family: 'Cairo', sans-serif; background-color: #f5f7fb; min-height: 100vh; display: flex; flex-direction: column; }
        .content-wrap { max-width: 80rem; margin-left: auto; margin-right: auto; width: 100%; }
        /* Dynamic module sidebar */
        .module-sidebar { width: 280px; min-width: 280px; background: #fff; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; overflow-y: auto; }
        .module-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; }
        .module-sidebar-icon-wrap { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem 0; }
        .module-sidebar-back { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; color: #6b7280; text-decoration: none; font-size: 0.875rem; background: #f9fafb; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .module-sidebar-back:hover { background: #f3f4f6; color: #374151; }
        .module-sidebar-search { margin-top: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb; font-size: 0.875rem; width: 100%; height: 2.5rem; box-sizing: border-box; line-height: 1.25; }
        .module-sidebar-search:focus { outline: none; border-color: #6366f1; background: #fff; }
        .module-nav { padding: 0.75rem; list-style: none; margin: 0; }
        .module-nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; color: #4b5563; text-decoration: none; font-size: 0.9375rem; transition: background 0.15s, color 0.15s; }
        .module-nav-link:hover { background: #f3f4f6; color: #111827; }
        .module-nav-link.active { background: #2563eb; color: #fff; font-weight: 500; }
        .module-nav-link.active .module-nav-icon { color: #fff; opacity: 1; }
        .module-nav-icon { width: 20px; height: 20px; flex-shrink: 0; color: #6b7280; opacity: 0.9; }
        .module-sidebar-footer { margin-top: auto; padding: 0.75rem 1rem; border-top: 1px solid #f3f4f6; font-size: 0.8rem; color: #9ca3af; }
        .module-sidebar-footer a { color: #6b7280; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
        .module-sidebar-footer a:hover { color: #374151; }
        .erp-sidebar-section { font-size: 0.7rem; letter-spacing: 0.05em; text-transform: uppercase; color: #9ca3af; padding: 0.5rem 1rem 0.25rem; margin-top: 0.5rem; }
        .erp-sidebar-module { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; border-radius: 0.5rem; color: #374151; text-decoration: none; font-size: 0.9375rem; transition: background 0.15s; }
        .erp-sidebar-module:hover { background: #f3f4f6; }
        .erp-sidebar-module.active { background: #eef2ff; color: #4338ca; font-weight: 500; }
        .erp-sidebar-module .erp-sidebar-icon { width: 20px; height: 20px; flex-shrink: 0; opacity: 0.85; }
        .erp-sidebar-module.active .erp-sidebar-icon { opacity: 1; }
        /* Modern ERP cards (for pages using this layout) */
        .erp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 1rem; transition: box-shadow 0.2s; }
        .erp-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        /* ERP tables */
        .erp-table-wrap { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; overflow: hidden; }
        .erp-table-wrap table { width: 100%; }
        .erp-table-wrap thead { background: #f9fafb; color: #4b5563; font-size: 0.875rem; }
        .erp-table-wrap tbody tr { transition: background 0.15s; }
        .erp-table-wrap tbody tr:hover { background: #f9fafb; }
        /* ملخص المبالغ: في واجهة RTL يُرصّ على جهة اليسار البصري (نهاية المحور في flex) */
        html[dir="rtl"] .erp-totals-left {
            display: flex;
            width: 100%;
            justify-content: flex-end;
        }
        html[dir="ltr"] .erp-totals-left {
            display: flex;
            width: 100%;
            justify-content: flex-start;
        }
        /* Filament notifications: RTL، عنوان عريض، نص متمركز */
        html[dir="rtl"] .fi-no { direction: rtl; }
        .fi-no-notification-title { font-weight: 700 !important; }
        .fi-no-notification-text,
        .fi-no-notification-body { text-align: center !important; }
        /* تنبيهات ورسائل موحدة لجميع الشاشات */
        .erp-toast-success { font-family: 'Cairo', sans-serif; position: fixed; bottom: 1rem; left: 1rem; z-index: 9998; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #059669; color: #fff; font-size: 0.875rem; font-weight: 500; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .erp-alert-error { font-family: 'Cairo', sans-serif; margin-bottom: 1rem; padding: 1rem; border-radius: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.875rem; }
        .erp-alert-success-inline { font-family: 'Cairo', sans-serif; margin-bottom: 1rem; padding: 1rem; border-radius: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: 0.875rem; }
        /* تلميح (i) ثابت فوق كل العناصر - منسق مع الخط والتنسيق العام */
        #info-hint-popup { position: fixed; z-index: 9999; max-width: 20rem; padding: 0.75rem 1rem; font-family: 'Cairo', sans-serif; font-size: 0.8125rem; line-height: 1.5; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); pointer-events: none; display: none; text-align: right; direction: rtl; }
        #info-hint-popup.is-visible { display: block; }
        @media print {
            .sidebar, .erp-sidebar, .module-sidebar, .no-print, .form-filter { display: none !important; }
            header.sticky.top-0 { display: none !important; }
            html, body { height: auto !important; overflow: visible !important; }
            .main-content, .container-fluid {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
        /* Hide spinners on number inputs (fallback when Vite CSS not loaded) */
        input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; display: none; }
        /* Alpine: إخفاء العناصر قبل التهيئة لتفادي الوميض */
        [x-cloak] { display: none !important; }
        /* مودالات Bootstrap: التأكد من ترتيب الطبقات فوق الـ backdrop (يتعارض أحياناً مع جداول overflow/sticky أو Filament) */
        .modal-backdrop { z-index: 1050; }
        .modal { z-index: 1055; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50" style="font-family: 'Cairo', sans-serif;">
    <div class="flex flex-col min-h-screen">
        @include('layouts.partials.erp-global-navbar')

        @php
            $isDashboard = request()->routeIs('dashboard');
            $currentModule = null;
            if (request()->is('sales*') || request()->is('reports/statement*')) {
                $currentModule = 'sales';
            } elseif (request()->is('purchases*')) {
                $currentModule = 'purchases';
            } elseif (request()->is('inventory*') || request()->is('items*') || request()->is('warehouses*')) {
                $currentModule = 'inventory';
            } elseif (request()->is('production-orders*') || request()->is('manufacturing*')) {
                $currentModule = 'manufacturing';
            } elseif (request()->is('finance*')) {
                $currentModule = 'finance';
            } elseif (request()->is('services*')) {
                $currentModule = 'services';
            } elseif (request()->is('hr*')) {
                $currentModule = 'hr';
            }
            $moduleConfig = [
                'sales' => ['title' => 'المبيعات', 'iconBg' => 'rgba(46, 125, 50, 0.2)', 'iconColor' => '#2e7d32', 'icon' => '<path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/>'],
                'purchases' => ['title' => 'المشتريات', 'iconBg' => 'rgba(124, 58, 237, 0.2)', 'iconColor' => '#7c3aed', 'icon' => '<path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/>'],
                'inventory' => ['title' => 'المخزون', 'iconBg' => 'rgba(245, 158, 11, 0.25)', 'iconColor' => '#ea580c', 'icon' => '<path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>'],
                'manufacturing' => ['title' => 'التصنيع', 'iconBg' => 'rgba(59, 130, 246, 0.18)', 'iconColor' => '#1d4ed8', 'icon' => '<path d="M8 1a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V1zm1 0v14h6V1H9zM2 3a1 1 0 0 1 1-1h3v14H3a1 1 0 0 1-1-1V3z"/>'],
                'finance' => ['title' => 'المحاسبة المالية', 'iconBg' => 'rgba(2, 119, 189, 0.2)', 'iconColor' => '#0277bd', 'icon' => '<path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/>'],
                'services' => ['title' => 'الخدمات والصيانة', 'iconBg' => 'rgba(14, 165, 233, 0.18)', 'iconColor' => '#0284c7', 'icon' => '<path d="M8.5 1.5a.5.5 0 0 0-1 0v1.086c-.563.097-1.159.236-1.668.43C5.48 3.328 5 4.108 5 5c0 .653.183 1.254.528 1.755.345.502.86.93 1.52 1.263.66.332 1.453.58 2.328.744V13H8v-1h1v-1.08c.563-.098 1.159-.237 1.668-.431 1.354-.312 2.332-1.092 2.332-1.991 0-.653-.183-1.254-.528-1.755-.345-.502-.86-.93-1.52-1.263C9.792 5.18 8.999 4.932 8.124 4.768V3h.5v1H8V3h-.5v1H7V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5V3z"/><path d="M6.5 14.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>'],
                'hr' => ['title' => 'الموارد البشرية', 'iconBg' => 'rgba(13, 148, 136, 0.18)', 'iconColor' => '#0d9488', 'icon' => '<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>'],
            ];
        @endphp
        <div class="flex flex-1 min-h-0">
        @if(!$isDashboard && $currentModule && isset($moduleConfig[$currentModule]))
        {{-- Dynamic Module Sidebar (يظهر فقط خارج الداشبورد وعند دخول قسم معرّف) --}}
        <aside class="module-sidebar min-h-0 hidden md:flex no-print shrink-0 flex-col">
            <div class="module-sidebar-header">
                @php $m = $moduleConfig[$currentModule]; @endphp
                <div class="module-sidebar-icon-wrap" style="background: {{ $m['iconBg'] }}; color: {{ $m['iconColor'] }};">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16">{!! $m['icon'] !!}</svg>
                </div>
                <h2 class="module-sidebar-title">{{ $m['title'] }}</h2>
                <a href="{{ route('dashboard') }}" class="module-sidebar-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
                    العودة للوحدات
                </a>
                <input type="search" class="module-sidebar-search" placeholder="بحث" aria-label="بحث في القسم">
            </div>
            <nav class="module-nav flex-1 overflow-y-auto">
                @if($currentModule === 'sales')
                {{-- روابط قسم المبيعات (كما في الصورة) --}}
                <a href="{{ route('sales.dashboard') }}" class="module-nav-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة المبيعات</a>
                <a href="{{ route('sales.customers.index') }}" class="module-nav-link {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> العملاء</a>
                <a href="{{ route('sales.quotations.index') }}" class="module-nav-link {{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5z"/></svg> عروض الأسعار</a>
                <a href="{{ route('sales.orders.index') }}" class="module-nav-link {{ request()->routeIs('sales.orders.index', 'sales.orders.create', 'sales.orders.store', 'sales.orders.show') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg> أوامر البيع</a>
                <a href="{{ route('sales.delivery-orders.index') }}" class="module-nav-link {{ request()->routeIs('sales.delivery-orders.*', 'sales.orders.delivery-orders.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 0 12.5v-9z"/><path d="M2 5h8v1H2V5zm0 3h6v1H2V8zm0 3h8v1H2v-1z"/></svg> أوامر التوريد</a>
                <a href="{{ route('sales.invoices.index') }}" class="module-nav-link {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> الفواتير</a>
                <a href="{{ route('sales.payments.index') }}" class="module-nav-link {{ request()->routeIs('sales.payments.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg> المدفوعات</a>
                <a href="{{ route('sales.returns.index') }}" class="module-nav-link {{ request()->routeIs('sales.returns.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> مرتجعات المبيعات</a>
                <a href="{{ route('sales.installments.index') }}" class="module-nav-link {{ request()->routeIs('sales.installments.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319z"/></svg> الأقساط</a>
                <a href="{{ route('sales.targets.index') }}" class="module-nav-link {{ request()->routeIs('sales.targets.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0zm1.5 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0z"/></svg> أهداف المبيعات</a>
                <a href="{{ route('sales.commissions.index') }}" class="module-nav-link {{ request()->routeIs('sales.commissions.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/><path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> العمولات</a>
                <a href="{{ route('sales.contracts.index') }}" class="module-nav-link {{ request()->routeIs('sales.contracts.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> العقود</a>
                <a href="{{ route('sales.einvoice.settings.edit') }}" class="module-nav-link {{ request()->routeIs('sales.einvoice.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> الفوترة الإلكترونية</a>
                <a href="{{ route('reports.statement.index') }}" class="module-nav-link {{ request()->routeIs('reports.statement.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg> كشف حساب العميل</a>
                @elseif($currentModule === 'purchases')
                {{-- روابط قسم المشتريات --}}
                <a href="{{ route('purchases.dashboard') }}" class="module-nav-link {{ request()->routeIs('purchases.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة المشتريات</a>
                <a href="{{ route('purchases.suppliers.index') }}" class="module-nav-link {{ request()->routeIs('purchases.suppliers.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> الموردين</a>
                <a href="{{ route('purchases.orders.index') }}" class="module-nav-link {{ request()->routeIs('purchases.orders.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg> أوامر الشراء</a>
                <a href="{{ route('purchases.receive-notes.index') }}" class="module-nav-link {{ request()->routeIs('purchases.receive-notes.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> سندات الاستلام</a>
                <a href="{{ route('purchases.invoices.index') }}" class="module-nav-link {{ request()->routeIs('purchases.invoices.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> فواتير الموردين</a>
                <a href="{{ route('purchases.returns.index') }}" class="module-nav-link {{ request()->routeIs('purchases.returns.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> مرتجعات المشتريات</a>
                <a href="{{ route('purchases.reports.index') }}" class="module-nav-link {{ request()->routeIs('purchases.reports.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2.5 4a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 5.5 4v8a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5V4zm4 2a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 9.5 6v6a.5.5 0 0 1-.5.5H7a.5.5 0 0 1-.5-.5V6zm4-3a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 13.5 3v9a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V3z"/></svg> تقارير المشتريات</a>
                @elseif($currentModule === 'inventory')
                {{-- روابط قسم المخزون --}}
                <a href="{{ route('inventory.dashboard') }}" class="module-nav-link {{ request()->routeIs('inventory.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة المخزون</a>
                <a href="{{ route('items.index') }}" class="module-nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg> المنتجات</a>
                <a href="{{ route('warehouses.index') }}" class="module-nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H2z"/></svg> المستودعات</a>
                <a href="{{ route('inventory.transfers.index') }}" class="module-nav-link {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/></svg> تحويلات المخزون</a>
                <a href="{{ route('inventory.adjustments.index') }}" class="module-nav-link {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6.5L14 3.5v1z"/><path d="M4.5 5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.5-.5h-7z"/></svg> تسويات المخزون</a>
                <a href="{{ route('inventory.audits.index') }}" class="module-nav-link {{ request()->routeIs('inventory.audits.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-1z"/></svg> جرد المخزون</a>
                <a href="{{ route('inventory.movements.index') }}" class="module-nav-link {{ request()->routeIs('inventory.movements.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> حركات المخزون</a>
                <a href="{{ route('inventory.price-lists.index') }}" class="module-nav-link {{ request()->routeIs('inventory.price-lists.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> قوائم الأسعار</a>
                @elseif($currentModule === 'manufacturing')
                <a href="{{ route('manufacturing.dashboard') }}" class="module-nav-link {{ request()->routeIs('manufacturing.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة التحكم</a>
                <a href="{{ route('manufacturing.bom-lists.index') }}" class="module-nav-link {{ request()->routeIs('manufacturing.bom-lists.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg> قوائم المواد</a>
                <a href="{{ route('manufacturing.runs.index') }}" class="module-nav-link {{ request()->routeIs('manufacturing.runs.index', 'manufacturing.create', 'manufacturing.show', 'manufacturing.store', 'manufacturing.post', 'manufacturing.destroy') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> أوامر العمل</a>
                <a href="{{ route('manufacturing.reports.production-variance') }}" class="module-nav-link {{ request()->routeIs('manufacturing.reports.production-variance') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-3H7v6h2V8zm5-5h-2v11h2V3zm-5-1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/></svg> انحرافات التصنيع</a>
                @elseif($currentModule === 'finance')
                {{-- روابط قسم المحاسبة المالية (كما في mm.jpg) --}}
                <a href="{{ route('finance.dashboard') }}" class="module-nav-link {{ request()->routeIs('finance.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة المحاسبة</a>
                <a href="{{ route('finance.accounts.index') }}" class="module-nav-link {{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.86-.83 4.825-.908 1.585-.078 3.043.138 4.468.792V1.783z"/></svg> دليل الحسابات</a>
                <a href="{{ route('finance.journals.index') }}" class="module-nav-link {{ request()->routeIs('finance.journals.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg> القيود اليومية</a>
                <a href="{{ route('finance.expenses.index') }}" class="module-nav-link {{ request()->routeIs('finance.expenses.index', 'finance.expenses.create', 'finance.expenses.store', 'finance.expenses.edit', 'finance.expenses.update', 'finance.expenses.print') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg> المصروفات</a>
                <a href="{{ route('finance.expenses.categories.index') }}" class="module-nav-link {{ request()->routeIs('finance.expenses.categories.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2.5A1.5 1.5 0 0 1 3.5 1h9A1.5 1.5 0 0 1 14 2.5v2A1.5 1.5 0 0 1 12.5 6h-9A1.5 1.5 0 0 1 2 4.5v-2zm0 4.5A1.5 1.5 0 0 1 3.5 5h9A1.5 1.5 0 0 1 14 6.5v2A1.5 1.5 0 0 1 12.5 10h-9A1.5 1.5 0 0 1 2 8.5v-2zm0 4.5A1.5 1.5 0 0 1 3.5 9h9a1.5 1.5 0 0 1 1.5 1.5v2a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 12.5v-2z"/></svg> تصنيفات المصروفات</a>
                <a href="{{ route('finance.fixed-assets.index') }}" class="module-nav-link {{ request()->routeIs('finance.fixed-assets.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z"/></svg> الأصول الثابتة</a>
                <a href="{{ route('finance.cost-centers.index') }}" class="module-nav-link {{ request()->routeIs('finance.cost-centers.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg> مراكز التكلفة</a>
                <a href="{{ route('finance.cheques.index') }}" class="module-nav-link {{ request()->routeIs('finance.cheques.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 0-.5-.5H1.5a.5.5 0 0 0-.5.5V14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V4.5a.5.5 0 0 1 1 0z"/><path d="M2 6h12v1H2z"/></svg> الشيكات</a>
                <a href="{{ route('finance.bank-accounts.index') }}" class="module-nav-link {{ request()->routeIs('finance.bank-accounts.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H15v2a1 1 0 0 1 1 1v3.5a1.5 1.5 0 0 1-1.5 1.5h-12A2.5 2.5 0 0 1 0 12.5V3zm1 1.732V12.5A1.5 1.5 0 0 0 2.5 14h12a.5.5 0 0 0 .5-.5V5H2a1.99 1.99 0 0 1-1-.268zM1 3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2H2a1 1 0 0 0-1 1z"/></svg> الحسابات البنكية</a>
                <a href="{{ route('finance.bank-reconciliations.index') }}" class="module-nav-link {{ request()->routeIs('finance.bank-reconciliations.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> تسوية البنك</a>
                <a href="{{ route('finance.credit-notes.index') }}" class="module-nav-link {{ request()->routeIs('finance.credit-notes.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6.5L14 3.5v1zM10.5 1.5V4H13"/><path d="M8.854 7.146a.5.5 0 1 1 .707.708L8.207 9.207H11.5a.5.5 0 0 1 0 1H8.207l1.354 1.353a.5.5 0 1 1-.707.708l-2.207-2.207a.5.5 0 0 1 0-.708l2.207-2.207z"/></svg> إشعارات الائتمان</a>
                <a href="{{ route('finance.debit-notes.index') }}" class="module-nav-link {{ request()->routeIs('finance.debit-notes.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6.5L14 3.5v1zM10.5 1.5V4H13"/><path d="M7.146 7.146a.5.5 0 0 1 .708 0l2.207 2.207a.5.5 0 0 1 0 .708l-2.207 2.207a.5.5 0 1 1-.708-.708L8.793 10H5.5a.5.5 0 0 1 0-1h3.293L7.146 7.854a.5.5 0 0 1 0-.708z"/></svg> إشعارات المديونية</a>
                <a href="{{ route('finance.budgets.index') }}" class="module-nav-link {{ request()->routeIs('finance.budgets.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2.5 4a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 5.5 4v8a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5V4zm4 2a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 9.5 6v6a.5.5 0 0 1-.5.5H7a.5.5 0 0 1-.5-.5V6zm4-3a.5.5 0 0 1 .5-.5h2A.5.5 0 0 1 13.5 3v9a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V3z"/></svg> الموازنات</a>
                <a href="{{ route('finance.reports.trial-balance') }}" class="module-nav-link {{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4.5 0a.5.5 0 0 1 .5.5V2h6V.5a.5.5 0 0 1 1 0V2h1.5A1.5 1.5 0 0 1 15 3.5v10A1.5 1.5 0 0 1 13.5 15h-11A1.5 1.5 0 0 1 1 13.5v-10A1.5 1.5 0 0 1 2.5 2H4V.5a.5.5 0 0 1 .5-.5zM2 7h12V3.5a.5.5 0 0 0-.5-.5h-11a.5.5 0 0 0-.5.5V7zm12 1H2v5.5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5V8z"/><path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm3.5 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/></svg> التقارير المالية - ميزان المراجعة</a>
                <a href="{{ route('finance.reports.ar-aging') }}" class="module-nav-link {{ request()->routeIs('finance.reports.ar-aging') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a.5.5 0 0 1 .5.5v1.032a3.5 3.5 0 0 1 2.5 3.347.5.5 0 0 1-1 0A2.5 2.5 0 0 0 7.5 2.38V6h1a2.5 2.5 0 0 1 0 5h-1v1.62A2.5 2.5 0 0 0 10 10.12a.5.5 0 0 1 1 0 3.5 3.5 0 0 1-2.5 3.347V14.5a.5.5 0 0 1-1 0v-1.032A3.5 3.5 0 0 1 5 10.12a.5.5 0 0 1 1 0A2.5 2.5 0 0 0 8.5 12.62V9h-1a2.5 2.5 0 0 1 0-5h1V.5A.5.5 0 0 1 8 0z"/></svg> أعمار الذمم المدينة</a>
                <a href="{{ route('finance.reports.ap-aging') }}" class="module-nav-link {{ request()->routeIs('finance.reports.ap-aging') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h1.5v-.5a.5.5 0 0 1 1 0V2h7v-.5a.5.5 0 0 1 1 0V2H14a2 2 0 0 1 2 2v2H0V4zm0 3h16v5a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm4 7v-2a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4z"/></svg> أعمار الذمم الدائنة</a>
                <a href="{{ route('finance.reports.profit-loss') }}" class="module-nav-link {{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/></svg> الأرباح والخسائر</a>
                @elseif($currentModule === 'hr')
                <a href="{{ route('hr.dashboard') }}" class="module-nav-link {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة تحكم الموارد البشرية</a>
                <a href="{{ route('hr.departments.index') }}" class="module-nav-link {{ request()->routeIs('hr.departments.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6 1H1v14h5V1zm7 0H8v14h5V1z"/></svg> الأقسام</a>
                <a href="{{ route('hr.employees.index') }}" class="module-nav-link {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> الموظفون</a>
                <a href="{{ route('hr.attendance') }}" class="module-nav-link {{ request()->routeIs('hr.attendance') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg> الحضور</a>
                @can('manage_payroll')
                <a href="{{ route('hr.payrolls.index') }}" class="module-nav-link {{ request()->routeIs('hr.payrolls.*') || request()->routeIs('hr.payroll-slips.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 2A1.5 1.5 0 0 1 3 .5h10A1.5 1.5 0 0 1 14.5 2v1h-13V2z"/><path d="M2 3.5V14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V3.5H2zM4 5h2v2H4V5zm0 3h2v2H4V8zm0 3h4v1H4v-1zm5-3h2v2H9V8zm0-3h2v2H9V5z"/></svg> الرواتب</a>
                @endcan
                <a href="{{ route('hr.leave-requests') }}" class="module-nav-link {{ request()->routeIs(['hr.leave-requests', 'hr.leave-requests.create']) ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg> طلبات الإجازة</a>
                @can('manage_payroll')
                <a href="{{ route('hr.overtime') }}" class="module-nav-link {{ request()->routeIs('hr.overtime*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 5.5a.5.5 0 0 0-1 0v2.793L6.354 6.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 8.293V5.5z"/><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2z"/></svg> الوقت الإضافي</a>
                @endcan
                @elseif($currentModule === 'services')
                @if(auth()->user()->is_technician || auth()->user()->isAdminOrSuperAdmin())
                <a href="{{ route('services.technician.index') }}" class="module-nav-link {{ request()->routeIs('services.technician.*') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> مهام الفني</a>
                @endif
                @if(auth()->user()->isAdminOrSuperAdmin())
                <a href="{{ route('services.dashboard') }}" class="module-nav-link {{ request()->routeIs('services.dashboard') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg> لوحة الخدمات</a>
                <a href="{{ route('services.orders.index') }}" class="module-nav-link {{ request()->routeIs('services.orders.index', 'services.orders.show', 'services.orders.store', 'services.orders.assign', 'services.orders.parts.store', 'services.orders.complete', 'services.orders.cancel') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> طلبات الخدمة</a>
                <a href="{{ route('services.orders.create') }}" class="module-nav-link {{ request()->routeIs('services.orders.create') ? 'active' : '' }}"><svg class="module-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/></svg> طلب جديد</a>
                @endif
                @endif
            </nav>
            <div class="module-sidebar-footer">
                <a href="#"><span>طي</span> <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg></a>
            </div>
        </aside>
        @endif

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Mobile sidebar toggle (يظهر فقط عند وجود سايدبار الوحدة) --}}
            @if(!$isDashboard && $currentModule)
            <div class="md:hidden flex items-center px-4 py-2 border-b border-gray-100 bg-white no-print">
                <button type="button" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100" data-bs-toggle="offcanvas" data-bs-target="#mobileModuleSidebar" aria-label="قائمة القسم">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                @isset($header)<h1 class="text-lg font-semibold text-gray-800 mr-2">{{ $header }}</h1>@endisset
            </div>
            @endif

            <main class="flex-1 px-4 md:px-6 py-4 main-content">
                <x-flash-messages />

                <div class="w-full {{ request()->routeIs('finance.journals.*', 'finance.expenses.index') ? 'max-w-full' : 'content-wrap max-w-7xl mx-auto' }}">
                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </div>
            </main>
        </div>
        </div>
    </div>

    {{-- Mobile module sidebar offcanvas (نفس محتوى سايدبار الوحدة) --}}
    @if(!$isDashboard && $currentModule && isset($moduleConfig[$currentModule]))
    @php $m = $moduleConfig[$currentModule]; @endphp
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileModuleSidebar" dir="rtl">
        <div class="offcanvas-header border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="module-sidebar-icon-wrap" style="background: {{ $m['iconBg'] }}; color: {{ $m['iconColor'] }};">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">{!! $m['icon'] !!}</svg>
                </div>
                <h5 class="offcanvas-title font-semibold mb-0">{{ $m['title'] }}</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="p-3 border-bottom">
                <a href="{{ route('dashboard') }}" class="module-sidebar-back d-inline-flex">العودة للوحدات</a>
                <input type="search" class="module-sidebar-search mt-2 w-100" placeholder="بحث" aria-label="بحث">
            </div>
            <nav class="module-nav p-2">
                @if($currentModule === 'sales')
                <a href="{{ route('sales.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}">لوحة المبيعات</a>
                <a href="{{ route('sales.customers.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}">العملاء</a>
                <a href="{{ route('sales.quotations.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}">عروض الأسعار</a>
                <a href="{{ route('sales.orders.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.orders.index', 'sales.orders.create', 'sales.orders.store', 'sales.orders.show') ? 'active' : '' }}">أوامر البيع</a>
                <a href="{{ route('sales.delivery-orders.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.delivery-orders.*', 'sales.orders.delivery-orders.*') ? 'active' : '' }}">أوامر التوريد</a>
                <a href="{{ route('sales.invoices.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}">الفواتير</a>
                <a href="{{ route('sales.payments.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.payments.*') ? 'active' : '' }}">المدفوعات</a>
                <a href="{{ route('sales.returns.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.returns.*') ? 'active' : '' }}">مرتجعات المبيعات</a>
                <a href="{{ route('sales.installments.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.installments.*') ? 'active' : '' }}">الأقساط</a>
                <a href="{{ route('sales.targets.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.targets.*') ? 'active' : '' }}">أهداف المبيعات</a>
                <a href="{{ route('sales.commissions.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.commissions.*') ? 'active' : '' }}">العمولات</a>
                <a href="{{ route('sales.contracts.index') }}" class="module-nav-link d-block {{ request()->routeIs('sales.contracts.*') ? 'active' : '' }}">العقود</a>
                <a href="{{ route('sales.einvoice.settings.edit') }}" class="module-nav-link d-block {{ request()->routeIs('sales.einvoice.*') ? 'active' : '' }}">الفوترة الإلكترونية</a>
                <a href="{{ route('reports.statement.index') }}" class="module-nav-link d-block {{ request()->routeIs('reports.statement.*') ? 'active' : '' }}">كشف حساب العميل</a>
                @elseif($currentModule === 'purchases')
                <a href="{{ route('purchases.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.dashboard') ? 'active' : '' }}">لوحة المشتريات</a>
                <a href="{{ route('purchases.suppliers.index') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.suppliers.*') ? 'active' : '' }}">الموردين</a>
                <a href="{{ route('purchases.orders.index') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.orders.*') ? 'active' : '' }}">أوامر الشراء</a>
                <a href="{{ route('purchases.receive-notes.index') }}" class="module-nav-link d-block">سندات الاستلام</a>
                <a href="{{ route('purchases.invoices.index') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.invoices.*') ? 'active' : '' }}">فواتير الموردين</a>
                <a href="{{ route('purchases.returns.index') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.returns.*') ? 'active' : '' }}">مرتجعات المشتريات</a>
                <a href="{{ route('purchases.reports.index') }}" class="module-nav-link d-block {{ request()->routeIs('purchases.reports.*') ? 'active' : '' }}">تقارير المشتريات</a>
                @elseif($currentModule === 'inventory')
                <a href="{{ route('inventory.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.dashboard') ? 'active' : '' }}">لوحة المخزون</a>
                <a href="{{ route('items.index') }}" class="module-nav-link d-block {{ request()->routeIs('items.*') ? 'active' : '' }}">المنتجات</a>
                <a href="{{ route('warehouses.index') }}" class="module-nav-link d-block {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">المستودعات</a>
                <a href="{{ route('inventory.transfers.index') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}">تحويلات المخزون</a>
                <a href="{{ route('inventory.adjustments.index') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">تسويات المخزون</a>
                <a href="{{ route('inventory.audits.index') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.audits.*') ? 'active' : '' }}">جرد المخزون</a>
                <a href="{{ route('inventory.movements.index') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.movements.*') ? 'active' : '' }}">حركات المخزون</a>
                <a href="{{ route('inventory.price-lists.index') }}" class="module-nav-link d-block {{ request()->routeIs('inventory.price-lists.*') ? 'active' : '' }}">قوائم الأسعار</a>
                @elseif($currentModule === 'manufacturing')
                <a href="{{ route('manufacturing.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('manufacturing.dashboard') ? 'active' : '' }}">لوحة التحكم</a>
                <a href="{{ route('manufacturing.bom-lists.index') }}" class="module-nav-link d-block {{ request()->routeIs('manufacturing.bom-lists.*') ? 'active' : '' }}">قوائم المواد</a>
                <a href="{{ route('manufacturing.runs.index') }}" class="module-nav-link d-block {{ request()->routeIs('manufacturing.runs.index', 'manufacturing.create', 'manufacturing.show', 'manufacturing.store', 'manufacturing.post', 'manufacturing.destroy') ? 'active' : '' }}">أوامر العمل</a>
                <a href="{{ route('manufacturing.reports.production-variance') }}" class="module-nav-link d-block {{ request()->routeIs('manufacturing.reports.production-variance') ? 'active' : '' }}">انحرافات التصنيع</a>
                @elseif($currentModule === 'finance')
                <a href="{{ route('finance.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('finance.dashboard') ? 'active' : '' }}">لوحة المحاسبة</a>
                <a href="{{ route('finance.accounts.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}">دليل الحسابات</a>
                <a href="{{ route('finance.journals.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.journals.*') ? 'active' : '' }}">القيود اليومية</a>
                <a href="{{ route('finance.expenses.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.expenses.index', 'finance.expenses.create', 'finance.expenses.store', 'finance.expenses.edit', 'finance.expenses.update', 'finance.expenses.print') ? 'active' : '' }}">المصروفات</a>
                <a href="{{ route('finance.expenses.categories.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.expenses.categories.*') ? 'active' : '' }}">تصنيفات المصروفات</a>
                <a href="{{ route('finance.fixed-assets.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.fixed-assets.*') ? 'active' : '' }}">الأصول الثابتة</a>
                <a href="{{ route('finance.cost-centers.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.cost-centers.*') ? 'active' : '' }}">مراكز التكلفة</a>
                <a href="{{ route('finance.cheques.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.cheques.*') ? 'active' : '' }}">الشيكات</a>
                <a href="{{ route('finance.bank-accounts.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.bank-accounts.*') ? 'active' : '' }}">الحسابات البنكية</a>
                <a href="{{ route('finance.bank-reconciliations.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.bank-reconciliations.*') ? 'active' : '' }}">تسوية البنك</a>
                <a href="{{ route('finance.credit-notes.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.credit-notes.*') ? 'active' : '' }}">إشعارات الائتمان</a>
                <a href="{{ route('finance.debit-notes.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.debit-notes.*') ? 'active' : '' }}">إشعارات المديونية</a>
                <a href="{{ route('finance.budgets.index') }}" class="module-nav-link d-block {{ request()->routeIs('finance.budgets.*') ? 'active' : '' }}">الموازنات</a>
                <a href="{{ route('finance.reports.trial-balance') }}" class="module-nav-link d-block {{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}">التقارير المالية - ميزان المراجعة</a>
                <a href="{{ route('finance.reports.ar-aging') }}" class="module-nav-link d-block {{ request()->routeIs('finance.reports.ar-aging') ? 'active' : '' }}">أعمار الذمم المدينة</a>
                <a href="{{ route('finance.reports.ap-aging') }}" class="module-nav-link d-block {{ request()->routeIs('finance.reports.ap-aging') ? 'active' : '' }}">أعمار الذمم الدائنة</a>
                <a href="{{ route('finance.reports.profit-loss') }}" class="module-nav-link d-block {{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}">الأرباح والخسائر</a>
                @elseif($currentModule === 'hr')
                <a href="{{ route('hr.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">لوحة تحكم الموارد البشرية</a>
                <a href="{{ route('hr.departments.index') }}" class="module-nav-link d-block {{ request()->routeIs('hr.departments.*') ? 'active' : '' }}">الأقسام</a>
                <a href="{{ route('hr.employees.index') }}" class="module-nav-link d-block {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}">الموظفون</a>
                <a href="{{ route('hr.attendance') }}" class="module-nav-link d-block {{ request()->routeIs('hr.attendance') ? 'active' : '' }}">الحضور</a>
                @can('manage_payroll')
                <a href="{{ route('hr.payrolls.index') }}" class="module-nav-link d-block {{ request()->routeIs('hr.payrolls.*') || request()->routeIs('hr.payroll-slips.*') ? 'active' : '' }}">الرواتب</a>
                @endcan
                <a href="{{ route('hr.leave-requests') }}" class="module-nav-link d-block {{ request()->routeIs(['hr.leave-requests', 'hr.leave-requests.create']) ? 'active' : '' }}">طلبات الإجازة</a>
                @can('manage_payroll')
                <a href="{{ route('hr.overtime') }}" class="module-nav-link d-block {{ request()->routeIs('hr.overtime*') ? 'active' : '' }}">الوقت الإضافي</a>
                @endcan
                @elseif($currentModule === 'services')
                @if(auth()->user()->is_technician || auth()->user()->isAdminOrSuperAdmin())
                <a href="{{ route('services.technician.index') }}" class="module-nav-link d-block {{ request()->routeIs('services.technician.*') ? 'active' : '' }}">مهام الفني</a>
                @endif
                @if(auth()->user()->isAdminOrSuperAdmin())
                <a href="{{ route('services.dashboard') }}" class="module-nav-link d-block {{ request()->routeIs('services.dashboard') ? 'active' : '' }}">لوحة الخدمات</a>
                <a href="{{ route('services.orders.index') }}" class="module-nav-link d-block {{ request()->routeIs('services.orders.*') ? 'active' : '' }}">طلبات الخدمة</a>
                <a href="{{ route('services.orders.create') }}" class="module-nav-link d-block {{ request()->routeIs('services.orders.create') ? 'active' : '' }}">طلب جديد</a>
                @endif
                @endif
            </nav>
        </div>
    </div>
    @endif

    @include('layouts/partials.erp-shell-footer-scripts')
</body>
</html>
