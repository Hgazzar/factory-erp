<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'المبيعات - ' . config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @filamentStyles
    @livewireStyles
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f5f7fb; min-height: 100vh; margin: 0; }
        .sales-wrapper { display: flex; flex-direction: row; min-height: 100vh; }
        .sales-sidebar { width: 280px; min-width: 280px; background: #fff; border-left: 1px solid #e0e6f0; display: flex; flex-direction: column; position: fixed; top: 0; right: 0; bottom: 0; z-index: 100; overflow-y: auto; }
        .sales-main { flex: 1; margin-right: 280px; display: flex; flex-direction: column; min-height: 100vh; width: 100%; min-width: 0; }
        .sales-content-area { flex: 1; overflow-y: auto; background-color: #eef0f4; padding: 1.25rem 1.5rem; }
        .sales-sidebar .nav-link { color: #344767; border-radius: 0.5rem; font-weight: 500; padding: 0.6rem 1rem; }
        .sales-sidebar .nav-link:hover { background-color: rgba(26, 115, 232, 0.08); color: #1a73e8; }
        .sales-sidebar .nav-link.active { background-color: #1a73e8; color: #fff; }
        .ufuq-topbar { background: #fff; border-bottom: 1px solid #e8eaed; padding: 0.75rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .ufuq-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1a73e8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
        html[dir="rtl"] .fi-no { direction: rtl; }
        .fi-no-notification-title { font-weight: 700 !important; }
        .fi-no-notification-text, .fi-no-notification-body { text-align: center !important; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sales-wrapper">
        {{-- سايدبار ثابت على اليمين --}}
        <aside class="sales-sidebar">
            <div class="p-3 border-bottom border-secondary border-opacity-25">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark">{{ config('app.name') }}</span>
                    <a href="{{ route('profile.edit') }}" class="text-decoration-none" title="الملف الشخصي">
                        <span class="ufuq-avatar">{{ strtoupper(mb_substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    </a>
                </div>
            </div>
            <div class="p-3">
                <h2 class="h6 fw-bold text-dark mb-3">المبيعات</h2>
                <div class="mb-3">
                    <div class="input-group input-group-sm rounded-pill bg-light border">
                        <span class="input-group-text bg-transparent border-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg></span>
                        <input type="search" class="form-control bg-transparent border-0" placeholder="بحث">
                    </div>
                </div>
                <ul class="nav flex-column gap-1">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}" href="{{ route('sales.dashboard') }}">لوحة المبيعات</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}" href="{{ route('sales.customers.index') }}">العملاء</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">عروض الأسعار</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">أوامر البيع</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}" href="{{ route('sales.invoices.index') }}">الفواتير</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">المدفوعات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">مرتجعات المبيعات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">الأقساط</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">أهداف المبيعات</a></li>
                </ul>
            </div>
        </aside>

        {{-- المحتوى الرئيسي: يأخذ المساحة المتبقية --}}
        <div class="sales-main">
            {{-- الهيدر العلوي (اسم المستخدم + أيقونات + اسم النظام) --}}
            <header class="ufuq-topbar">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-link text-dark text-decoration-none p-0 me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
                        </a>
                        <span class="fw-bold text-dark">{{ auth()->user()?->name ?? '—' }}</span>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle p-1"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2z"/><path d="M8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628.134 2.197.459 3.742.16.767.376 1.566.663 2.258h10.244c.287-.692.502-1.491.663-2.258C11.866 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917z"/></svg></a>
                        <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary rounded-circle p-1"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg></a>
                        <div class="input-group input-group-sm rounded-pill bg-light border ms-2" style="max-width: 220px;">
                            <span class="input-group-text bg-transparent border-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg></span>
                            <input type="search" class="form-control bg-transparent border-0" placeholder="بحث">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small d-none d-md-inline">{{ config('app.name') }}</span>
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">الوحدات</a>
                    </div>
                </div>
            </header>

            {{-- منطقة المحتوى الرئيسي (الجزء الأيسر / الشمال) --}}
            <main class="sales-content-area">
                <div class="container-fluid px-0 mb-3">
                    <x-flash-messages />
                </div>
                <div class="container-fluid px-0">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @livewire('notifications')
    @livewireScripts
    @filamentScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (form) {
                if (!form.hasAttribute('data-html5-validate')) {
                    form.setAttribute('novalidate', 'novalidate');
                }
            });
            document.querySelectorAll('[data-auto-dismiss-success]').forEach(function (el) {
                window.setTimeout(function () {
                    if (window.bootstrap && window.bootstrap.Alert) {
                        const alert = window.bootstrap.Alert.getOrCreateInstance(el);
                        alert.close();
                        return;
                    }
                    el.remove();
                }, 5000);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
