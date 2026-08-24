<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', niche_module_label('clinic').' — '.config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @filamentStyles
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @elseif(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @unless(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endunless
    @include('tenant.partials.theme-css-vars')
    <style>
        /* أساسيات التخطيط — تعمل حتى بدون Tailwind */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(160deg, var(--clinic-bg) 0%, var(--clinic-bg-mid) 45%, var(--clinic-secondary) 100%);
            color: var(--clinic-text);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .nursery-brand-mark--sidebar,
        .tenant-brand-mark--sidebar {
            flex-direction: column;
            align-items: flex-start;
            gap: .65rem;
            margin-bottom: .75rem;
        }
        .tenant-brand-mark--sidebar .tenant-brand-mark__logo-wrap,
        .nursery-brand-mark--sidebar .nursery-brand-mark__logo-wrap {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--clinic-bg-mid), #fff);
            border: 1px solid var(--clinic-border);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: .25rem;
        }
        .tenant-brand-mark__name,
        .nursery-brand-mark--sidebar .nursery-brand-mark__name {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--clinic-text);
            margin: 0;
        }
        .clinic-shell { display: flex; flex: 1; min-height: 0; overflow: hidden; }
        .clinic-main { flex: 1; display: flex; flex-direction: column; min-width: 0; min-height: 0; }
        .clinic-main-inner { flex: 1; overflow-y: auto; padding: 1rem 1.25rem; }
        .content-wrap { max-width: 100%; width: 100%; margin: 0 auto; }

        /* السايدبار */
        .module-sidebar {
            width: 280px; min-width: 280px; background: #fff;
            border-left: 1px solid var(--clinic-border);
            display: flex; flex-direction: column;
            overflow: hidden;
            min-height: 0;
            align-self: stretch;
        }
        .module-sidebar-header {
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--clinic-border);
            flex-shrink: 0;
        }
        .module-sidebar-icon-wrap {
            width: 48px; height: 48px; border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--clinic-primary), var(--clinic-primary-dark));
            color: var(--clinic-on-primary);
            display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;
        }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 700; color: var(--clinic-text); margin: 0 0 0.75rem; }
        .module-sidebar-back {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.75rem; border-radius: 0.5rem;
            color: var(--clinic-primary-dark); text-decoration: none; font-size: 0.875rem;
            background: var(--clinic-bg-mid); border: 1px solid var(--clinic-border);
        }
        .module-sidebar-back:hover { background: var(--clinic-secondary); color: var(--clinic-text); }
        .module-nav {
            padding: 0.75rem; list-style: none; margin: 0;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .module-nav-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0.75rem; border-radius: 0.5rem;
            color: var(--clinic-text-muted); text-decoration: none; font-size: 0.9375rem;
            transition: background 0.15s, color 0.15s;
        }
        .module-nav-link:hover { background: var(--clinic-secondary); color: var(--clinic-text); }
        .module-nav-link.active { background: var(--clinic-primary); color: var(--clinic-on-primary); font-weight: 500; }
        .module-sidebar-footer {
            margin-top: 0; padding: 0.75rem 1rem;
            border-top: 1px solid #f0fdfa; font-size: 0.8rem; color: #9ca3af;
            flex-shrink: 0;
        }

        /* أزرار وعناصر العيادة — fallback */
        .clinic-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;
            border: 1px solid transparent; cursor: pointer; text-decoration: none;
            font-family: inherit; line-height: 1.25;
        }
        .clinic-btn-primary { background: var(--clinic-primary); color: var(--clinic-on-primary); border-color: var(--clinic-primary); }
        .clinic-btn-primary:hover { background: var(--clinic-primary-dark); color: var(--clinic-on-primary); }
        .clinic-btn-outline { background: #fff; color: #374151; border-color: #e5e7eb; }
        .clinic-btn-outline:hover { background: #f9fafb; }
        .clinic-btn-outline.is-active { background: #0d9488; color: #fff; border-color: #0d9488; }

        .clinic-card {
            background: #fff; border: 1px solid #ccfbf1;
            border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(13, 148, 136, 0.08);
        }
        .clinic-page-header {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;
        }
        .clinic-page-title { font-size: 1.5rem; font-weight: 700; color: var(--clinic-text); margin: 0; }
        .clinic-page-subtitle { font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0; }
        .clinic-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }

        /* جدول الحجوزات */
        .clinic-board-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .clinic-board-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; min-width: 52rem; }
        .clinic-board-table th,
        .clinic-board-table td { border: 1px solid #e5e7eb; padding: 0.35rem 0.5rem; vertical-align: top; }
        .clinic-board-table thead th { background: #f0fdfa; color: #134e4a; font-weight: 600; }
        .clinic-board-table .time-col {
            position: sticky; right: 0; z-index: 2; background: #fff;
            font-family: ui-monospace, monospace; font-size: 0.75rem; color: #6b7280;
            min-width: 4.5rem;
        }
        .clinic-board-table thead .time-col { background: #f0fdfa; }

        .clinic-status-pending { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .clinic-status-completed { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .clinic-status-cancelled { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

        /* Filament + hints */
        html[dir="rtl"] .fi-no { direction: rtl; }
        .fi-no-notification-title { font-weight: 700 !important; }
        #info-hint-popup {
            position: fixed; z-index: 9999; max-width: 20rem; padding: 0.75rem 1rem;
            font-family: 'Cairo', sans-serif; font-size: 0.8125rem; line-height: 1.5;
            color: #374151; background: #fff; border: 1px solid #e5e7eb;
            border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            pointer-events: none; display: none; text-align: right; direction: rtl;
        }
        #info-hint-popup.is-visible { display: block; }
        [x-cloak] { display: none !important; }
        .modal-backdrop { z-index: 1050; }
        .modal { z-index: 1055; }

        @media (min-width: 768px) {
            .module-sidebar { display: flex !important; }
        }
        @media (max-width: 767px) {
            .module-sidebar { display: none !important; }
            .clinic-main-inner { padding: 0.75rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="flex flex-col h-screen overflow-hidden">
        @include('layouts.partials.erp-global-navbar')
        <div class="clinic-shell">
            <aside class="module-sidebar hidden md:flex shrink-0 flex-col min-h-0">
                <div class="module-sidebar-header">
                    @if(!empty($tenantBrand))
                        @include('tenant.partials.brand-mark', ['variant' => 'sidebar', 'markClass' => 'tenant-brand-mark', 'fallbackEmoji' => '🏥'])
                    @else
                        <div class="module-sidebar-icon-wrap" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 5.5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5z"/><path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/></svg>
                        </div>
                        <h2 class="module-sidebar-title">{{ niche_module_label('clinic') }}</h2>
                    @endif
                    <a href="{{ route('dashboard') }}" class="module-sidebar-back">← العودة للوحدات</a>
                </div>
                <nav class="module-nav flex-1 min-h-0">
                    <x-clinic-sidebar-nav />
                </nav>
                <div class="module-sidebar-footer">{{ config('app.name') }} · Clinic</div>
            </aside>
            <div class="clinic-main">
                <main class="clinic-main-inner">
                    <x-flash-messages />
                    <div class="content-wrap">@yield('content')</div>
                </main>
            </div>
        </div>
    </div>
    @include('layouts.partials.erp-shell-footer-scripts')
    @stack('scripts')
</body>
</html>
