<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نقاط البيع — '.config('app.name'))</title>
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
        body { font-family: 'Cairo', sans-serif; background-color: #f9fafb; min-height: 100vh; margin: 0; }
        .module-sidebar { width: 280px; min-width: 280px; background: #fff; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; overflow-y: auto; }
        .module-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; }
        .module-sidebar-icon-wrap { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem 0; }
        .module-sidebar-back { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; color: #6b7280; text-decoration: none; font-size: 0.875rem; background: #f9fafb; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .module-sidebar-back:hover { background: #f3f4f6; color: #374151; }
        .module-nav { padding: 0.75rem; list-style: none; margin: 0; flex: 1; }
        .module-nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; color: #4b5563; text-decoration: none; font-size: 0.9375rem; transition: background 0.15s, color 0.15s; }
        .module-nav-link:hover { background: #f3f4f6; color: #111827; }
        .module-nav-link.active { background: #2563eb; color: #fff; font-weight: 500; }
        .module-nav-link.disabled,
        .module-nav-link[aria-disabled="true"] { opacity: 0.45; pointer-events: none; cursor: default; }
        html[dir="rtl"] .fi-no { direction: rtl; }
        .fi-no-notification-title { font-weight: 700 !important; }
        #info-hint-popup { position: fixed; z-index: 9999; max-width: 20rem; padding: 0.75rem 1rem; font-family: 'Cairo', sans-serif; font-size: 0.8125rem; line-height: 1.5; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); pointer-events: none; display: none; text-align: right; direction: rtl; }
        #info-hint-popup.is-visible { display: block; }
        .modal-backdrop { z-index: 1050; }
        .modal { z-index: 1055; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50" style="font-family: 'Cairo', sans-serif;">
    <div class="flex flex-col min-h-screen">
        @include('layouts.partials.erp-global-navbar')

        <div class="flex flex-1 min-h-0 overflow-hidden">
            <aside class="module-sidebar shrink-0 flex no-print flex-col">
                <div class="module-sidebar-header">
                    <div class="module-sidebar-icon-wrap" style="background: rgba(220, 38, 38, 0.15); color: #dc2626;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/><path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/></svg>
                    </div>
                    <h2 class="module-sidebar-title">نقاط البيع</h2>
                    <a href="{{ route('dashboard') }}" class="module-sidebar-back">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
                        العودة للوحدات
                    </a>
                </div>
                <nav class="module-nav flex flex-col gap-1">
                    <a class="module-nav-link {{ request()->routeIs('pos.dashboard') ? 'active' : '' }}" href="{{ route('pos.dashboard') }}">لوحة نقاط البيع</a>
                    <span class="module-nav-link disabled" aria-disabled="true">الكاشير</span>
                    @if(auth()->user()?->role === 'admin')
                        <a class="module-nav-link {{ request()->routeIs('pos.devices.*') ? 'active' : '' }}" href="{{ route('pos.devices.index') }}">الأجهزة</a>
                    @endif
                    <span class="module-nav-link disabled" aria-disabled="true">الجلسات</span>
                    <a class="module-nav-link {{ request()->routeIs('pos.receipts.*') ? 'active' : '' }}" href="{{ route('pos.receipts.index') }}">الإيصالات</a>
                </nav>
            </aside>

            <div class="flex flex-1 flex-col min-w-0 min-h-0 bg-gray-50">
                <main class="flex-1 overflow-y-auto px-4 py-6 md:px-6">
                    <div class="mb-4 md:mb-6">
                        <x-flash-messages />
                    </div>
                    <div class="w-full max-w-7xl mx-auto">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @include('layouts.partials.erp-shell-footer-scripts')
</body>
</html>
