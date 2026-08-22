<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

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
            $currentModule = ($tenantNavigation ?? null)?->detectActiveErpModule();
            $showErpSidebar = ! $isDashboard
                && $currentModule
                && ($tenantNavigation ?? null)?->hasVisibleErpModuleSidebar($currentModule);
        @endphp
        <div class="flex flex-1 min-h-0">
        @if($showErpSidebar)
        <x-erp-module-sidebar :module="$currentModule" />
        @endif

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Mobile sidebar toggle (يظهر فقط عند وجود سايدبار الوحدة) --}}
            @if($showErpSidebar)
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

    @if($showErpSidebar)
        <x-erp-module-sidebar-offcanvas :module="$currentModule" />
    @endif

    @include('layouts/partials.erp-shell-footer-scripts')
</body>
</html>
