<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', niche_module_label('fleet').' — '.config('app.name'))</title>
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
    @include('tenant.partials.theme-css-vars', ['tenantThemeVars' => $tenantThemeVars ?? null])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(160deg, var(--fleet-bg) 0%, var(--fleet-bg-mid) 45%, var(--fleet-secondary) 100%);
            color: var(--fleet-text);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .fleet-shell { display: flex; flex: 1; min-height: 0; overflow: hidden; }
        .fleet-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .fleet-main-inner { flex: 1; overflow-y: auto; padding: 1rem 1.25rem; }
        .module-sidebar {
            width: 260px; min-width: 260px; background: #fff;
            border-left: 1px solid var(--fleet-border);
            display: flex; flex-direction: column;
            overflow: hidden;
            min-height: 0;
            align-self: stretch;
        }
        .module-sidebar-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--fleet-border); flex-shrink: 0; }
        .module-sidebar-icon-wrap {
            width: 52px; height: 52px; border-radius: 1rem;
            background: linear-gradient(135deg, var(--fleet-primary), var(--fleet-primary-dark));
            color: var(--fleet-on-primary); display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 0.75rem;
        }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 800; color: var(--fleet-text); margin: 0 0 0.75rem; }
        .module-sidebar-back {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.75rem; border-radius: 0.5rem;
            color: var(--fleet-primary-dark); text-decoration: none; font-size: 0.875rem;
            background: var(--fleet-bg-mid); border: 1px solid var(--fleet-border);
        }
        .module-sidebar-back:hover { background: var(--fleet-secondary); color: var(--fleet-text); }
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
            color: var(--fleet-text-muted); text-decoration: none; font-size: 0.9375rem;
        }
        .module-nav-link:hover { background: var(--fleet-secondary); color: var(--fleet-text); }
        .module-nav-link.active { background: var(--fleet-primary); color: var(--fleet-on-primary); font-weight: 600; }
        .fleet-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;
            border: 1px solid transparent; cursor: pointer; text-decoration: none; font-family: inherit;
        }
        .fleet-btn-primary { background: var(--fleet-primary); color: var(--fleet-on-primary); }
        .fleet-btn-primary:hover { background: var(--fleet-primary-dark); color: var(--fleet-on-primary); }
        .fleet-btn-soft { background: #fff; color: var(--fleet-primary-dark); border-color: var(--fleet-border); }
        .fleet-card {
            background: #fff; border: 1px solid var(--fleet-border);
            border-radius: 0.75rem; box-shadow: 0 1px 3px var(--fleet-shadow);
        }
        @media (max-width: 767px) {
            .module-sidebar { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="flex flex-col h-screen overflow-hidden">
        @include('layouts.partials.erp-global-navbar')
        <div class="fleet-shell">
            <aside class="module-sidebar hidden md:flex shrink-0 flex-col min-h-0">
                <div class="module-sidebar-header">
                    <div class="module-sidebar-icon-wrap" aria-hidden="true">🚚</div>
                    <h2 class="module-sidebar-title">{{ niche_module_label('fleet') }}</h2>
                    <a href="{{ route('dashboard') }}" class="module-sidebar-back">← العودة للوحدات</a>
                </div>
                <nav class="module-nav flex-1 min-h-0">
                    <x-fleet-sidebar-nav />
                </nav>
            </aside>
            <div class="fleet-main">
                <main class="fleet-main-inner">
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
