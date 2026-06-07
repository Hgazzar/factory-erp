<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $tenantThemeVars['--store-primary'] ?? '#dc2626' }}">
    <title>@yield('title', ($storeName ?? config('app.name')).' — تسوق أونلاين')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { cairo: ['Cairo', 'sans-serif'] },
                    colors: {
                        dark: { 50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a' }
                    }
                }
            }
        }
    </script>
    @include('tenant.partials.theme-css-vars', ['tenantThemeVars' => $tenantThemeVars ?? []])
    @include('store.partials.store-styles')
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/js/store.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
        @include('store.partials.scripts-fallback')
    @endif
    @stack('head')
</head>
<body class="bg-gray-50 min-h-screen font-cairo"
      data-store-slug="{{ $tenantSlug }}"
      x-data="akStoreShell(@js([
          'slug' => $tenantSlug,
          'apiBase' => $apiBase,
          'currency' => $currencyCode,
          'routes' => $routes,
      ]))"
      x-init="init()"
      @keydown.escape.window="mobileSearchOpen = false; closeCart()">

    @include('store.partials.header')

    <main id="mainContent">
        @yield('content')
    </main>

    @include('store.partials.footer')
    @include('store.partials.cart-drawer')
    @include('store.partials.toast')

    @stack('scripts')
</body>
</html>
