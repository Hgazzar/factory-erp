<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FDFDFD">
    <title>@yield('title', $storeName ?? config('app.name'))</title>
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/store-premium.css', 'resources/js/store-premium.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
        @include('store.premium.partials.scripts-fallback')
    @endif
    @stack('head')
</head>
<body class="ak-store ak-main-pad"
      x-data="akStoreShell(@js([
          'slug' => $tenantSlug,
          'apiBase' => $apiBase,
          'currency' => $currencyCode,
          'routes' => $routes,
      ]))"
      x-init="init()"
      @keydown.escape.window="mobileOpen = false; closeCart()">

    @include('store.premium.partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('store.premium.partials.footer')
    @include('store.premium.partials.cart-drawer')
    @include('store.premium.partials.bottom-nav')

    @stack('scripts')
</body>
</html>
