<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'إدارة العملاء — '.config('app.name'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @filamentStyles
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @env('local')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @if(file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endenv

    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f5f7fb; min-height: 100vh; display: flex; flex-direction: column; }
        .content-wrap { max-width: 80rem; margin-left: auto; margin-right: auto; width: 100%; }
        .module-sidebar { width: 280px; min-width: 280px; background: #fff; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; overflow-y: auto; }
        .module-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; }
        .module-sidebar-icon-wrap { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem 0; }
        .module-sidebar-back { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; color: #6b7280; text-decoration: none; font-size: 0.875rem; background: #f9fafb; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .module-sidebar-back:hover { background: #f3f4f6; color: #374151; }
        .module-nav { padding: 0.75rem; list-style: none; margin: 0; }
        .module-nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; color: #4b5563; text-decoration: none; font-size: 0.9375rem; transition: background 0.15s, color 0.15s; }
        .module-nav-link:hover { background: #f3f4f6; color: #111827; }
        .module-nav-link.active { background: #2563eb; color: #fff; font-weight: 500; }
        .module-nav-link.active .module-nav-icon { color: #fff; opacity: 1; }
        .module-nav-link.crm-nav-neon-active.active { background: #00E9F9; color: #00363b; }
        .module-nav-link.crm-nav-neon-active.active .module-nav-icon { color: #00363b; }
        .module-nav-icon { width: 20px; height: 20px; flex-shrink: 0; color: #6b7280; opacity: 0.9; }
        .module-sidebar-footer { margin-top: auto; padding: 0.75rem 1rem; border-top: 1px solid #f3f4f6; font-size: 0.8rem; color: #9ca3af; }
        html[dir="rtl"] .fi-no { direction: rtl; }
        .fi-no-notification-title { font-weight: 700 !important; }
        #info-hint-popup { position: fixed; z-index: 9999; max-width: 20rem; padding: 0.75rem 1rem; font-family: 'Cairo', sans-serif; font-size: 0.8125rem; line-height: 1.5; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); pointer-events: none; display: none; text-align: right; direction: rtl; }
        #info-hint-popup.is-visible { display: block; }
        input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; display: none; }
        [x-cloak] { display: none !important; }
        .modal-backdrop { z-index: 1050; }
        .modal { z-index: 1055; }
        .module-sidebar-search { margin-top: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb; font-size: 0.875rem; width: 100%; height: 2.5rem; box-sizing: border-box; line-height: 1.25; }
        .module-sidebar-search:focus { outline: none; border-color: #6366f1; background: #fff; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50" style="font-family: 'Cairo', sans-serif;">
    <div class="flex flex-col min-h-screen">
        @include('layouts.partials.erp-global-navbar')

        <div class="flex flex-1 min-h-0 overflow-hidden">
            <aside class="module-sidebar min-h-0 hidden md:flex no-print shrink-0 flex-col">
                <div class="module-sidebar-header">
                    <div class="module-sidebar-icon-wrap" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M15 14s1 0 1-1-1-4-6-4-6 3-6 4 1 1 1 1h10zm-9-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm8 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                    </div>
                    <h2 class="module-sidebar-title">إدارة العملاء</h2>
                    <a href="{{ route('dashboard') }}" class="module-sidebar-back">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
                        العودة للوحدات
                    </a>
                    <label class="visually-hidden" for="crmSidebarNavSearch">بحث في القائمة</label>
                    <input type="search" id="crmSidebarNavSearch" class="module-sidebar-search crm-sidebar-nav-search" placeholder="بحث في القائمة" autocomplete="off" aria-label="بحث في قائمة إدارة العملاء">
                </div>
                <nav class="module-nav flex-1 overflow-y-auto flex flex-col gap-1">
                    <x-crm-sidebar-nav />
                </nav>
                <div class="module-sidebar-footer text-center md:text-start">
                    <span class="text-gray-400">{{ config('app.name') }} · CRM</span>
                </div>
            </aside>

            <div class="flex flex-1 flex-col min-w-0 min-h-0 bg-gray-50">
                <div class="md:hidden flex items-center gap-3 px-4 py-2 border-b border-gray-100 bg-white no-print shrink-0">
                    <button type="button" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100" data-bs-toggle="offcanvas" data-bs-target="#crmMobileSidebar" aria-label="قائمة إدارة العملاء">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="text-lg font-semibold text-gray-800">إدارة العملاء</span>
                </div>

                <main class="flex-1 overflow-y-auto px-4 md:px-6 py-4 main-content">
                    <x-flash-messages />
                    <div class="w-full content-wrap max-w-7xl mx-auto">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="crmMobileSidebar" dir="rtl" aria-labelledby="crmMobileSidebarLabel">
        <div class="offcanvas-header border-bottom align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div class="module-sidebar-icon-wrap mb-0" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-6-4-6 3-6 4 1 1 1 1h10z"/></svg>
                </div>
                <h5 class="offcanvas-title font-semibold mb-0" id="crmMobileSidebarLabel">قائمة CRM</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="p-3 border-bottom">
                <a href="{{ route('dashboard') }}" class="module-sidebar-back d-inline-flex">العودة للوحدات</a>
                <label class="visually-hidden" for="crmSidebarNavSearchMobile">بحث في القائمة</label>
                <input type="search" id="crmSidebarNavSearchMobile" class="module-sidebar-search crm-sidebar-nav-search" placeholder="بحث في القائمة" autocomplete="off" aria-label="بحث في قائمة إدارة العملاء">
            </div>
            <nav class="module-nav flex flex-col gap-1 p-2">
                <x-crm-sidebar-nav />
            </nav>
        </div>
    </div>

    @include('layouts/partials.erp-shell-footer-scripts')
    <script>
    (function () {
        function filterNav(q) {
            q = (q || '').trim().toLowerCase();
            document.querySelectorAll('.crm-nav-filter-item').forEach(function (wrap) {
                var a = wrap.querySelector('[data-crm-nav-search]');
                var hay = (a && a.getAttribute('data-crm-nav-search')) ? a.getAttribute('data-crm-nav-search').toLowerCase() : '';
                wrap.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        }
        document.querySelectorAll('.crm-sidebar-nav-search').forEach(function (inp) {
            inp.addEventListener('input', function () { filterNav(this.value); });
        });
    })();
    </script>
</body>
</html>
