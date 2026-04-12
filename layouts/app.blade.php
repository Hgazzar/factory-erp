<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    {{-- Laravel يحمّل `layouts.app` من resources/views/layouts/app.blade.php — هذا الملف ليس التخطيط الافتراضي لصفحات ERP مثل القيود. --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Factory ERP'))</title>

    <!-- Bootstrap 5 RTL for ERP UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Breeze assets (Tailwind, Alpine, ...) for auth/profile screens -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #f5f7fb;
        }
        .erp-sidebar {
            min-height: 100vh;
            width: 260px;
        }
        .erp-sidebar .nav-link {
            color: #344767;
            border-radius: 0.375rem;
            font-weight: 500;
        }
        .erp-sidebar .nav-link.active,
        .erp-sidebar .nav-link:hover {
            background-color: #1a73e826;
            color: #1a73e8;
        }
        .erp-sidebar .nav-section-title {
            font-size: 0.78rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #9aa0ac;
        }
        .erp-topbar {
            border-bottom: 1px solid #e0e6f0;
            background-color: #ffffff;
        }

        /* رأس الجدول: رمادي فاتح، نص رمادي غامق، semi-bold — بدون برتقالي أو ألوان فاقعة */
        .content-wrap table thead th,
        table thead th {
            background-color: #f9fafb !important;
            color: #374151 !important;
            font-weight: 600 !important;
            border-bottom: 1px solid #e5e7eb;
        }

        /* توحيد أزرار الإلغاء: نفس الحجم وتمركز النص */
        .content-wrap .btn-outline-secondary,
        .content-wrap a.btn.btn-outline-secondary {
            height: 2.5rem;
            min-height: 2.5rem;
            padding: 0 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        @media print {
            .sidebar,
            .erp-sidebar,
            .navbar,
            .btn,
            .no-print,
            .form-filter {
                display: none !important;
            }
            .main-content,
            .container-fluid {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse;
            }
            table th,
            table td {
                padding: 6px;
                font-size: 12px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="d-flex">
        {{-- Sidebar --}}
        <nav class="erp-sidebar bg-white border-end d-none d-md-block">
            <div class="p-3">
                <div class="d-flex align-items-center mb-4">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                        <span class="h5 mb-0 text-primary fw-bold">{{ config('app.name', 'Factory ERP') }}</span>
                    </a>
                </div>

                @auth
                    @php
                        $inventoryModuleActive = request()->routeIs('inventory.*')
                            || request()->routeIs('items.*')
                            || request()->routeIs('warehouses.*');
                    @endphp
                    <div class="mb-2 nav-section-title">الرئيسية</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item mb-1">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link {{ request()->routeIs('operations.dashboard.*') ? 'active' : '' }}"
                               href="{{ route('operations.dashboard.index') }}">
                                لوحة العمليات
                            </a>
                        </li>
                    </ul>

                    @if(auth()->user()->role === 'admin')
                        <div class="mb-2 nav-section-title">البيانات الأساسية</div>
                        <ul class="nav flex-column mb-3">
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('production-lines.*') ? 'active' : '' }}"
                                   href="{{ route('production-lines.index') }}">
                                    خطوط الإنتاج
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('machines.*') ? 'active' : '' }}"
                                   href="{{ route('machines.index') }}">
                                    الماكينات
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('purchases.suppliers.*') ? 'active' : '' }}"
                                   href="{{ route('purchases.suppliers.index') }}">
                                    الموردون
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}"
                                   href="{{ route('sales.customers.index') }}">
                                    العملاء
                                </a>
                            </li>
                        </ul>

                        @if($inventoryModuleActive)
                            <div class="mb-2 nav-section-title">المخزون</div>
                            <ul class="nav flex-column mb-3">
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"
                                       href="{{ route('inventory.dashboard') }}">
                                        لوحة المخزون
                                    </a>
                                </li>
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}"
                                       href="{{ route('items.index') }}">
                                        المنتجات
                                    </a>
                                </li>
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"
                                       href="{{ route('warehouses.index') }}">
                                        المستودعات
                                    </a>
                                </li>
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}"
                                       href="{{ route('inventory.transfers.index') }}">
                                        تحويلات المخزون
                                    </a>
                                </li>
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}"
                                       href="{{ route('inventory.adjustments.index') }}">
                                        تسويات المخزون
                                    </a>
                                </li>
                                <li class="nav-item mb-1">
                                    <a class="nav-link {{ request()->routeIs('inventory.audits.*') ? 'active' : '' }}"
                                       href="{{ route('inventory.audits.index') }}">
                                        جرد المخزون
                                    </a>
                                </li>
                            </ul>
                        @endif
                    @elseif(auth()->user()->role === 'supervisor')
                        <div class="mb-2 nav-section-title">إعدادات التشغيل</div>
                        <ul class="nav flex-column mb-3">
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('production-lines.*') ? 'active' : '' }}"
                                   href="{{ route('production-lines.index') }}">
                                    خطوط الإنتاج
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('machines.*') ? 'active' : '' }}"
                                   href="{{ route('machines.index') }}">
                                    الماكينات
                                </a>
                            </li>
                        </ul>
                    @endif

                    <div class="mb-2 nav-section-title">العمليات اليومية</div>
                    <ul class="nav flex-column mb-3">
                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'supervisor')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('operations.shifts.*') ? 'active' : '' }}"
                                   href="{{ route('operations.shifts.index') }}">
                                    إدارة الورديات
                                </a>
                            </li>
                        @endif
                        @if(in_array(auth()->user()->role, ['admin', 'supervisor', 'worker']))
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('operations.production-entry.*') ? 'active' : '' }}"
                                   href="{{ route('operations.production-entry.create') }}">
                                    تسجيل الإنتاج
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('purchases.invoices.*') ? 'active' : '' }}"
                                   href="{{ route('purchases.invoices.index') }}">
                                    فواتير المشتريات
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}"
                                   href="{{ route('sales.invoices.index') }}">
                                    فواتير المبيعات
                                </a>
                            </li>
                        @endif
                    </ul>

                    {{-- Modules (تحضير للـ HR + Finance) --}}
                    <div class="mb-2 nav-section-title">المالية</div>
                    <ul class="nav flex-column mb-3">
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}"
                                   href="{{ route('finance.accounts.index') }}">
                                    شجرة الحسابات
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.journals.index') ? 'active' : '' }}"
                                   href="{{ route('finance.journals.index') }}">
                                    قيود اليومية
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.journals.create') ? 'active' : '' }}"
                                   href="{{ route('finance.journals.create') }}">
                                    إضافة قيد جديد
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.ledger.*') ? 'active' : '' }}"
                                   href="{{ route('finance.ledger.index') }}">
                                    كشوف الحسابات
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.receipts.*') ? 'active' : '' }}"
                                   href="{{ route('finance.receipts.index') }}">
                                    سندات القبض
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.payments.*') ? 'active' : '' }}"
                                   href="{{ route('finance.payments.index') }}">
                                    سندات الصرف
                                </a>
                            </li>
                        @endif
                    </ul>

                    <div class="mb-2 nav-section-title">الموارد البشرية</div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}"
                                   href="{{ route('hr.employees.index') }}">
                                    الموظفون
                                </a>
                            </li>
                        @endif
                    </ul>

                    <div class="mb-2 nav-section-title">التقارير</div>
                    <ul class="nav flex-column mb-3">
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('reports.statement.*') ? 'active' : '' }}"
                                   href="{{ route('reports.statement.index') }}">
                                    كشف حساب
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('reports.tax.*') ? 'active' : '' }}"
                                   href="{{ route('reports.tax.index') }}">
                                    التقرير الضريبي
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}"
                                   href="{{ route('finance.reports.profit-loss') }}">
                                    الأرباح والخسائر
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('reports.production.*') ? 'active' : '' }}"
                                   href="{{ route('reports.production.index') }}">
                                    تقرير الإنتاج
                                </a>
                            </li>
                        @endif
                    </ul>

                    <div class="mb-2 nav-section-title">السجلات والـ Audit</div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item mb-1">
                                <a class="nav-link {{ request()->routeIs('system.audit.*') ? 'active' : '' }}"
                                   href="{{ route('system.audit.index') }}">
                                    سجل العمليات (Audit Log)
                                </a>
                            </li>
                        @endif
                    </ul>
                @endauth
            </div>
        </nav>

        {{-- Main content --}}
        <div class="flex-grow-1 d-flex flex-column">
            <nav class="navbar navbar-light erp-topbar px-3 px-md-4">
                <div class="container-fluid">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                            القائمة
                        </button>
                        @isset($header)
                            <h1 class="h6 mb-0">{{ $header }}</h1>
                        @endisset
                    </div>

                    <div class="ms-auto d-flex align-items-center gap-3">
                        @auth
                            {{-- Breeze profile / logout dropdown --}}
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex align-items-center px-3 py-2 border border-0 rounded-3 bg-white text-muted">
                                        <div class="me-2">{{ Auth::user()->name }}</div>
                                        <div>
                                            <svg class="fill-current" style="height: 1rem; width: 1rem" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        @endauth
                    </div>
                </div>
            </nav>

            <main class="flex-grow-1 px-3 px-md-4 py-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
