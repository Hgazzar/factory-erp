<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة أولياء الأمور') — {{ $nurseryDisplayName ?? $nurseryName ?? config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @endif
    {{-- بوابة عامة بدون Livewire — Alpine مطلوب لأي تفاعل مستقبلي --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @include('nursery.partials.theme-css-vars')
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            background: var(--np-bg, #F0FDFA);
            color: var(--np-text);
            min-height: 100vh;
        }
        [x-cloak] { display: none !important; }
        .np-wrap { max-width: 640px; margin: 0 auto; padding: 1rem 1rem 5rem; }
        .np-header {
            text-align: center;
            padding: 1rem 0 1.25rem;
        }
        .nursery-brand-mark {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
        }
        .nursery-brand-mark--portal .nursery-brand-mark__logo-wrap {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1.15rem;
            background: #fff;
            border: 1px solid var(--np-border);
            box-shadow: 0 6px 20px var(--np-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: .35rem;
        }
        .nursery-brand-mark__logo {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            aspect-ratio: auto;
        }
        .nursery-brand-mark__fallback {
            font-size: 2rem;
            line-height: 1;
        }
        .nursery-brand-mark__name {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--np-primary-dark);
            line-height: 1.25;
        }
        .nursery-brand-mark__tagline {
            margin: .35rem 0 0;
            font-size: .85rem;
            color: var(--np-text-muted);
            opacity: .88;
            line-height: 1.4;
        }
        .np-header h1 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--np-primary-dark);
            margin: 0;
        }
        .np-header p {
            margin: .25rem 0 0;
            font-size: .85rem;
            color: var(--np-text-muted);
            opacity: .85;
        }
        .np-card {
            background: var(--np-card);
            border: 1px solid var(--np-border);
            border-radius: 1rem;
            box-shadow: 0 8px 30px var(--np-shadow);
            padding: 1.25rem;
        }
        .np-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: none;
            border-radius: .75rem;
            padding: .75rem 1rem;
            font-weight: 700;
            font-size: .95rem;
            cursor: pointer;
            text-decoration: none;
            transition: .15s;
        }
        .np-btn-primary {
            background: var(--np-primary);
            color: var(--np-on-primary);
        }
        .np-btn-primary:hover { background: var(--np-primary-dark); color: var(--np-on-primary); }
        .np-btn-soft {
            background: var(--np-secondary);
            color: var(--np-primary-dark);
        }
        .np-input {
            width: 100%;
            border: 1px solid var(--np-border);
            border-radius: .65rem;
            padding: .65rem .85rem;
            font-size: 1rem;
            font-family: inherit;
            background: #fff;
        }
        .np-input:focus {
            outline: 2px solid var(--np-focus-ring);
            border-color: var(--np-primary);
        }
        .np-label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: .35rem;
            color: var(--np-text-muted);
        }
        .np-alert {
            border-radius: .65rem;
            padding: .65rem .85rem;
            font-size: .875rem;
            margin-bottom: 1rem;
        }
        .np-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .np-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .np-alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .np-child-card {
            border: 1px solid var(--np-border);
            border-radius: .85rem;
            padding: 1rem;
            background: var(--np-card-tint);
        }
        .nursery-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }
        .nursery-table thead th {
            background: transparent;
            color: #94a3b8;
            font-weight: 700;
            font-size: 0.75rem;
            text-align: right;
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
            white-space: nowrap;
        }
        .nursery-table tbody td {
            padding: 1rem 0.9rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.045);
            color: #64748b;
            vertical-align: middle;
            background: #fff;
        }
        .nursery-table tbody tr:last-child td { border-bottom: none; }
        .nursery-table tbody tr:hover td { background: #fafafa; }
        .nursery-table tbody tr:nth-child(even) td { background: #fcfcfd; }
        .nursery-table-name__title {
            display: block;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            font-size: 0.95rem;
        }
        .np-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid var(--np-border);
            padding: .5rem 1rem calc(.5rem + env(safe-area-inset-bottom));
            z-index: 30;
        }
        .np-bottom-nav-inner {
            max-width: 640px;
            margin: 0 auto;
            display: flex;
            gap: .5rem;
        }
        .np-bottom-nav a, .np-bottom-nav button {
            flex: 1;
            text-align: center;
            font-size: .8rem;
            font-weight: 700;
            color: var(--np-text-muted);
            text-decoration: none;
            padding: .5rem;
            border-radius: .5rem;
            background: transparent;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .np-bottom-nav a.is-active { background: var(--np-secondary); color: var(--np-primary-dark); }
    </style>
    @stack('styles')
</head>
<body>
    <div class="np-wrap">
        <header class="np-header">
            @include('nursery.partials.brand-mark', ['variant' => 'portal'])
        </header>

        @if(session('success'))
            <div class="np-alert np-alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="np-alert np-alert-error">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="np-alert np-alert-warning">{{ session('warning') }}</div>
        @endif

        @yield('content')
    </div>

    @hasSection('bottom_nav')
        <nav class="np-bottom-nav">
            <div class="np-bottom-nav-inner">
                @yield('bottom_nav')
            </div>
        </nav>
    @endif

    @stack('scripts')
</body>
</html>
