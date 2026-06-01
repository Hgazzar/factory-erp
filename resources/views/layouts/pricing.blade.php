<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="باقات أكواد — منصة إدارة أعمال فاخرة للتجزئة والمصانع والعيادات">
    <title>@yield('title', 'الباقات والأسعار — '.config('app.name'))</title>
    @vite(['resources/css/pricing.css', 'resources/js/pricing.js'])
</head>
<body class="pr-page" x-data="prNav()" x-init="init()" :class="{ '': true }">
    @yield('content')
</body>
</html>
