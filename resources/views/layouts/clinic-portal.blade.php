<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'حجز موعد') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endif
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%); min-height: 100vh; margin: 0; }
        .portal-wrap { max-width: 640px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        .portal-card { background: #fff; border-radius: 1rem; border: 1px solid #ccfbf1; box-shadow: 0 8px 30px rgba(13,148,136,.08); padding: 1.5rem; }
        .portal-step { display: none; }
        .portal-step.is-active { display: block; }
        .slot-btn { border: 1px solid #99f6e4; background: #f0fdfa; border-radius: .5rem; padding: .5rem .75rem; cursor: pointer; transition: .15s; }
        .slot-btn:hover, .slot-btn.is-selected { background: #0d9488; color: #fff; border-color: #0d9488; }
        .date-btn { border: 1px solid #e5e7eb; background: #fff; border-radius: .5rem; padding: .65rem; text-align: center; cursor: pointer; }
        .date-btn.is-selected { border-color: #0d9488; background: #ccfbf1; }
        .portal-progress { display: flex; gap: .5rem; margin-bottom: 1.25rem; }
        .portal-progress span { flex: 1; height: 4px; background: #e5e7eb; border-radius: 2px; }
        .portal-progress span.is-done { background: #0d9488; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
