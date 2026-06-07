<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'حجز موعد') — {{ $tenantDisplayName ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endif
    @include('tenant.partials.theme-css-vars')
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(160deg, var(--cp-bg) 0%, var(--cp-bg-mid) 55%, var(--cp-secondary) 100%);
            color: var(--cp-text);
            min-height: 100vh;
            margin: 0;
        }
        .tenant-brand-mark {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            text-align: center;
        }
        .tenant-brand-mark--portal .tenant-brand-mark__logo-wrap {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1.15rem;
            background: #fff;
            border: 1px solid var(--cp-border);
            box-shadow: 0 6px 20px var(--cp-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: .35rem;
        }
        .tenant-brand-mark__logo { max-width: 100%; max-height: 100%; object-fit: contain; }
        .tenant-brand-mark__fallback { font-size: 2rem; line-height: 1; }
        .tenant-brand-mark__name {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--cp-primary-dark);
        }
        .tenant-brand-mark__tagline {
            margin: .35rem 0 0;
            font-size: .85rem;
            color: var(--cp-text-muted);
        }
        .portal-wrap { max-width: 640px; margin: 0 auto; padding: 1rem 1rem 3rem; }
        .portal-header { text-align: center; padding: 1rem 0 1.25rem; }
        .portal-card {
            background: var(--cp-card);
            border-radius: 1rem;
            border: 1px solid var(--cp-border);
            box-shadow: 0 8px 30px var(--cp-shadow);
            padding: 1.5rem;
        }
        .portal-step { display: none; }
        .portal-step.is-active { display: block; }
        .slot-btn {
            border: 1px solid var(--cp-border);
            background: var(--cp-bg-mid);
            border-radius: .5rem;
            padding: .5rem .75rem;
            cursor: pointer;
            transition: .15s;
        }
        .slot-btn:hover, .slot-btn.is-selected {
            background: var(--cp-primary);
            color: var(--cp-on-primary);
            border-color: var(--cp-primary);
        }
        .date-btn {
            border: 1px solid var(--cp-border);
            background: #fff;
            border-radius: .5rem;
            padding: .65rem;
            text-align: center;
            cursor: pointer;
        }
        .date-btn.is-selected {
            border-color: var(--cp-primary);
            background: var(--cp-secondary);
        }
        .portal-progress { display: flex; gap: .5rem; margin-bottom: 1.25rem; }
        .portal-progress span { flex: 1; height: 4px; background: var(--cp-border); border-radius: 2px; }
        .portal-progress span.is-done { background: var(--cp-primary); }
        .btn-portal-primary {
            background: var(--cp-primary);
            color: var(--cp-on-primary);
            border: none;
        }
        .btn-portal-primary:hover {
            background: var(--cp-primary-dark);
            color: var(--cp-on-primary);
        }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="portal-wrap">
        <header class="portal-header">
            @include('tenant.partials.brand-mark', [
                'variant' => 'portal',
                'markClass' => 'tenant-brand-mark',
                'fallbackEmoji' => '🏥',
                'tagline' => 'احجز موعدك أونلاين بخطوات بسيطة',
            ])
        </header>
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
