<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', niche_module_label('nursery').' — '.config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
    @include('nursery.partials.theme-css-vars')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(160deg, var(--nursery-bg) 0%, var(--nursery-bg-mid) 45%, var(--nursery-secondary) 100%);
            color: var(--nursery-text);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .nursery-shell { display: flex; flex: 1; min-height: 0; overflow: hidden; }
        .nursery-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .nursery-main-inner { flex: 1; overflow-y: auto; padding: 1rem 1.25rem; }
        .module-sidebar {
            width: 260px; min-width: 260px; background: #fff;
            border-left: 1px solid var(--nursery-border);
            display: flex; flex-direction: column;
        }
        .module-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--nursery-border); }
        .nursery-brand-mark--sidebar {
            flex-direction: column;
            align-items: flex-start;
            gap: .65rem;
            margin-bottom: .75rem;
        }
        .nursery-brand-mark--sidebar .nursery-brand-mark__logo-wrap {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--nursery-bg-mid), #fff);
            border: 1px solid var(--nursery-border);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: .25rem;
        }
        .nursery-brand-mark--sidebar .nursery-brand-mark__logo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .nursery-brand-mark--sidebar .nursery-brand-mark__name {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--nursery-text);
            margin: 0;
            line-height: 1.2;
        }
        .module-sidebar-icon-wrap {
            width: 52px; height: 52px; border-radius: 1rem;
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            color: var(--nursery-on-primary); display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 0.75rem;
        }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 800; color: var(--nursery-text); margin: 0 0 0.75rem; }
        .module-sidebar-back {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.75rem; border-radius: 0.5rem;
            color: var(--nursery-primary-dark); text-decoration: none; font-size: 0.875rem;
            background: var(--nursery-bg-mid); border: 1px solid var(--nursery-border);
        }
        .module-nav { padding: 0.75rem; list-style: none; margin: 0; }
        .module-nav-link {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.65rem 0.85rem; border-radius: 0.65rem;
            color: var(--nursery-text-muted); text-decoration: none; font-size: 0.9375rem; font-weight: 500;
        }
        .module-nav-link:hover { background: var(--nursery-secondary); color: var(--nursery-text); }
        .module-nav-link.active { background: var(--nursery-primary); color: var(--nursery-on-primary); font-weight: 600; }
        .nursery-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            padding: 0.55rem 1.1rem; border-radius: 0.65rem; font-size: 0.875rem; font-weight: 600;
            border: 1px solid transparent; cursor: pointer; text-decoration: none; font-family: inherit;
        }
        .nursery-btn-primary { background: var(--nursery-primary); color: var(--nursery-on-primary); }
        .nursery-btn-primary:hover { background: var(--nursery-primary-dark); color: var(--nursery-on-primary); }
        .nursery-btn-soft { background: #fff; color: var(--nursery-text); border-color: var(--nursery-border); }
        .nursery-btn-soft:hover { background: var(--nursery-secondary); }
        .nursery-card {
            background: #fff; border: 1px solid var(--nursery-border);
            border-radius: 1rem; box-shadow: 0 4px 14px var(--nursery-shadow);
        }
        .nursery-stat { padding: 1.25rem; text-align: center; }
        .nursery-stat-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: var(--nursery-primary); }
        .nursery-child-card {
            padding: 1rem; border-radius: 1rem; border: 1px solid var(--nursery-border);
            background: #fff; transition: transform 0.15s, box-shadow 0.15s;
        }
        .nursery-child-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--nursery-shadow); }
        [x-cloak] { display: none !important; }
        .nursery-capacity-input::-webkit-outer-spin-button,
        .nursery-capacity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .nursery-capacity-input { -moz-appearance: textfield; appearance: textfield; }
        .nursery-age-group-list { border: 1px solid var(--nursery-border); border-radius: 0.5rem; overflow: hidden; background: #fff; }
        .nursery-age-row {
            display: flex; align-items: center; gap: 0.75rem; width: 100%;
            padding: 0.65rem 0.85rem; cursor: pointer; font-size: 0.875rem;
            color: var(--nursery-text-muted); border-bottom: 1px solid var(--nursery-border);
            background: #ffffff;
        }
        .nursery-age-row:last-child { border-bottom: none; }
        .nursery-age-row--stripe { background: var(--nursery-bg-mid); }
        .nursery-age-row--header {
            font-weight: 700; color: var(--nursery-text);
            background: var(--nursery-secondary); border-bottom: 1px solid var(--nursery-border);
        }
        .nursery-age-row:hover { background: var(--nursery-secondary); }
        .nursery-age-row--header:hover { background: var(--nursery-border); }
        .nursery-perm-row {
            padding: 0.55rem 0.75rem; min-height: 2.5rem;
            background: #ffffff; border-bottom: 1px solid var(--nursery-border);
        }
        .nursery-perm-row--stripe { background: var(--nursery-bg-mid); }
        .nursery-perm-row:last-child { border-bottom: none; }
        .nursery-switch { position: relative; display: inline-block; width: 2.75rem; height: 1.5rem; }
        .nursery-switch-input {
            position: absolute; opacity: 0; width: 0; height: 0;
        }
        .nursery-switch-track {
            position: absolute; inset: 0; border-radius: 999px;
            background: #d1d5db; transition: background 0.2s;
        }
        .nursery-switch-track::after {
            content: ''; position: absolute; width: 1.15rem; height: 1.15rem;
            right: 0.2rem; top: 0.17rem; border-radius: 50%; background: #fff;
            transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .nursery-switch-input:checked + .nursery-switch-track { background: #22c55e; }
        .nursery-switch-input:checked + .nursery-switch-track::after { transform: translateX(-1.25rem); }
        .nursery-switch-input:disabled + .nursery-switch-track { opacity: 0.55; }
        .nursery-attendance-tabs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
            gap: 0.35rem;
            padding: 0.35rem;
            background: #fff;
            border: 1px solid var(--nursery-border);
            border-radius: 0.85rem;
            box-shadow: 0 2px 8px var(--nursery-shadow);
        }
        @media (max-width: 640px) {
            .nursery-attendance-tabs { grid-template-columns: 1fr; }
        }
        .nursery-attendance-tab {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.15rem; padding: 0.65rem 0.5rem; border-radius: 0.65rem;
            text-decoration: none; text-align: center;
            border: 2px solid transparent;
            transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .nursery-attendance-tab-icon { font-size: 1.25rem; line-height: 1; }
        .nursery-attendance-tab-label { font-size: 0.875rem; font-weight: 700; color: var(--nursery-text); }
        .nursery-attendance-tab-desc { font-size: 0.65rem; font-weight: 500; color: var(--nursery-text-muted); opacity: 0.75; }
        .nursery-attendance-tab:hover {
            background: var(--nursery-bg-mid); border-color: var(--nursery-border);
        }
        .nursery-attendance-tab.is-active {
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            border-color: var(--nursery-primary-dark);
            box-shadow: 0 4px 12px var(--nursery-shadow);
        }
        .nursery-attendance-tab.is-active .nursery-attendance-tab-label,
        .nursery-attendance-tab.is-active .nursery-attendance-tab-desc { color: var(--nursery-on-primary); opacity: 1; }
        @media (max-width: 767px) {
            .module-sidebar { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="flex flex-col min-h-screen">
        @include('layouts.partials.erp-global-navbar')
        <div class="nursery-shell">
            <aside class="module-sidebar hidden md:flex shrink-0 flex-col">
                <div class="module-sidebar-header">
                    @isset($nurseryBrand)
                        @include('nursery.partials.brand-mark', ['variant' => 'sidebar'])
                    @else
                        <div class="module-sidebar-icon-wrap" aria-hidden="true">🧸</div>
                        <h2 class="module-sidebar-title">{{ niche_module_label('nursery') }}</h2>
                    @endisset
                    <a href="{{ route('dashboard') }}" class="module-sidebar-back">← العودة للوحدات</a>
                </div>
                <nav class="module-nav flex-1">
                    <x-nursery-sidebar-nav />
                </nav>
            </aside>
            <div class="nursery-main">
                <main class="nursery-main-inner">
                    <x-flash-messages />
                    <div class="content-wrap w-full max-w-[96rem] mx-auto px-1">@yield('content')</div>
                </main>
            </div>
        </div>
    </div>
    @include('layouts.partials.erp-shell-footer-scripts')
    @stack('scripts')
</body>
</html>
