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
            background: #f7f6f3;
            color: var(--nursery-text);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .nursery-shell { display: flex; flex: 1; min-height: 0; overflow: hidden; }
        .nursery-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .nursery-main-inner { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem 1.75rem; }
        .nursery-topbar {
            display: none;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: #fff;
            border-bottom: 1px solid var(--nursery-border);
            flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .nursery-topbar { display: flex; }
        }
        .nursery-topbar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            border: 1px solid var(--nursery-border);
            background: #fff;
            color: var(--nursery-text);
            cursor: pointer;
            transition: background .15s, border-color .15s, color .15s;
        }
        .nursery-topbar-toggle:hover {
            background: var(--nursery-secondary);
            border-color: var(--nursery-primary);
            color: var(--nursery-primary-dark);
        }
        .module-sidebar {
            width: 268px;
            min-width: 268px;
            background: #fff;
            border-left: 1px solid var(--nursery-border);
            display: flex;
            flex-direction: column;
            transition: width .22s ease, min-width .22s ease;
            overflow: hidden;
        }
        .nursery-shell.is-sidebar-collapsed .module-sidebar {
            width: 84px;
            min-width: 84px;
        }
        .module-sidebar-header {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--nursery-border);
            min-height: 5.25rem;
            display: flex;
            align-items: center;
        }
        .nursery-shell.is-sidebar-collapsed .module-sidebar-header {
            justify-content: center;
            padding: 1rem 0.5rem;
        }
        .nursery-brand-mark--sidebar {
            flex-direction: column;
            align-items: flex-start;
            gap: .65rem;
            margin-bottom: 0;
            width: 100%;
        }
        .nursery-shell.is-sidebar-collapsed .nursery-brand-mark--sidebar {
            align-items: center;
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
        .nursery-shell.is-sidebar-collapsed .nursery-brand-mark--sidebar .nursery-brand-mark__logo-wrap {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
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
        .nursery-shell.is-sidebar-collapsed .nursery-brand-mark--sidebar .nursery-brand-mark__name,
        .nursery-shell.is-sidebar-collapsed .module-sidebar-title {
            display: none;
        }
        .module-sidebar-icon-wrap {
            width: 52px; height: 52px; border-radius: 1rem;
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            color: var(--nursery-on-primary); display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 0;
        }
        .nursery-shell.is-sidebar-collapsed .module-sidebar-icon-wrap {
            width: 2.75rem; height: 2.75rem; font-size: 1.15rem; border-radius: 0.85rem;
        }
        .module-sidebar-title { font-size: 1.125rem; font-weight: 800; color: var(--nursery-text); margin: 0; }
        .module-nav {
            padding: 0.75rem;
            list-style: none;
            margin: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .nursery-shell.is-sidebar-collapsed .module-nav {
            padding: 0.65rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
        }
        .module-nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.7rem 0.9rem;
            border-radius: 0.85rem;
            color: var(--nursery-text-muted);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            position: relative;
            transition: background .15s, color .15s, box-shadow .15s;
        }
        .module-nav-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            flex-shrink: 0;
        }
        .module-nav-label {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 0;
            white-space: nowrap;
        }
        .module-nav-link:hover { background: var(--nursery-secondary); color: var(--nursery-text); }
        .module-nav-link.active {
            background: color-mix(in srgb, var(--nursery-primary) 14%, #fff);
            color: var(--nursery-primary-dark);
            font-weight: 700;
            box-shadow: inset -3px 0 0 var(--nursery-primary);
        }
        .nursery-shell.is-sidebar-collapsed .module-nav-link {
            width: 3.15rem;
            height: 3.15rem;
            padding: 0;
            justify-content: center;
            border-radius: 0.95rem;
        }
        .nursery-shell.is-sidebar-collapsed .module-nav-label { display: none; }
        .nursery-shell.is-sidebar-collapsed .module-nav-link.active {
            background: var(--nursery-primary);
            color: var(--nursery-on-primary);
            box-shadow: none;
        }
        .nursery-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            padding: 0.55rem 1.1rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600;
            border: 1px solid transparent; cursor: pointer; text-decoration: none; font-family: inherit;
        }
        .nursery-btn-primary { background: var(--nursery-primary); color: var(--nursery-on-primary); }
        .nursery-btn-primary:hover { background: var(--nursery-primary-dark); color: var(--nursery-on-primary); }
        .nursery-btn-soft { background: #fff; color: var(--nursery-text); border-color: var(--nursery-border); }
        .nursery-btn-soft:hover { background: var(--nursery-secondary); }
        .nursery-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.05);
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.045);
        }
        .nursery-admina-stat {
            transition: transform .15s, box-shadow .15s;
            overflow: hidden;
            height: 100%;
            min-height: 11.5rem;
            display: flex;
            flex-direction: column;
        }
        .nursery-admina-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
        }
        .nursery-admina-stat__inner {
            padding: 1.15rem 1.25rem 1.2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }
        .nursery-admina-stat__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.85rem;
            margin-bottom: 0.95rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .nursery-admina-stat__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #334155;
            line-height: 1.3;
        }
        .nursery-admina-stat__menu {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 999px;
            color: #94a3b8;
            flex-shrink: 0;
            text-decoration: none;
        }
        a.nursery-admina-stat__menu:hover {
            background: #fff7ed;
            color: #ea580c;
        }
        .nursery-admina-stat__body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex: 1;
            min-height: 4.75rem;
        }
        .nursery-admina-stat__data {
            min-width: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.35rem;
        }
        .nursery-admina-stat__value {
            margin: 0;
            font-size: clamp(1.55rem, 2.2vw, 1.95rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.05;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }
        .nursery-admina-stat__hint {
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--hint);
            line-height: 1.3;
        }
        .nursery-admina-stat__arrow {
            font-size: 0.85rem;
            line-height: 1;
        }
        .nursery-admina-stat__link {
            display: inline-flex;
            margin-top: 0.15rem;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--nursery-primary-dark);
            text-decoration: none;
        }
        .nursery-admina-stat__link:hover { text-decoration: underline; }
        .nursery-stat-spark {
            flex-shrink: 0;
            align-self: stretch;
            display: flex;
            align-items: flex-end;
        }
        .nursery-stat-spark--bars {
            display: flex;
            align-items: flex-end;
            gap: 0.32rem;
            height: 4.4rem;
            width: 5.4rem;
        }
        .nursery-stat-spark--bars span {
            flex: 1;
            border-radius: 0.35rem 0.35rem 0.15rem 0.15rem;
            background: var(--spark-soft);
            min-height: 18%;
        }
        .nursery-stat-spark--bars span.is-active { background: var(--spark-active); }
        .nursery-stat-spark--line {
            width: 5.75rem;
            height: 4.2rem;
            align-items: stretch;
        }
        .nursery-stat-spark--line svg { width: 100%; height: 100%; display: block; }
        .nursery-stat-spark--ring {
            width: 4.4rem;
            height: 4.4rem;
            align-items: center;
            justify-content: center;
        }
        .nursery-stat-spark--ring svg { width: 100%; height: 100%; display: block; }
        .nursery-stats-row > .nursery-admina-stat { min-height: 11.5rem; }
        .nursery-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .nursery-panel-head__meta {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            min-width: 0;
        }
        .nursery-panel-head__icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #FFF1E8;
            color: #EA580C;
        }
        .nursery-panel-head__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--nursery-text);
            line-height: 1.25;
        }
        .nursery-panel-head__sub {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }
        .nursery-stat { padding: 1.25rem; text-align: center; }
        .nursery-stat-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: var(--nursery-primary); }
        .nursery-child-card {
            padding: 1rem 1.15rem; border-radius: 1.25rem; border: 1px solid rgba(15, 23, 42, 0.06);
            background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        }
        .nursery-child-card:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08); }
        .nursery-person-avatar {
            width: 2.5rem; height: 2.5rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.9rem; flex-shrink: 0;
            background: var(--nursery-secondary); color: var(--nursery-primary-dark);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }
        .nursery-person-avatar--photo {
            display: inline-block;
            padding: 0;
            background: #fff;
            object-fit: cover;
        }
        .nursery-list-row {
            display: flex; align-items: center; gap: 0.85rem;
            padding: 0.9rem 0.35rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .nursery-list-row:last-child { border-bottom: none; }
        .nursery-table-card {
            overflow: hidden;
            padding: 0;
            border-radius: 1.5rem;
            border: 1px solid rgba(15, 23, 42, 0.04);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.04);
        }
        .nursery-table-card__toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 1.2rem 1.4rem 1.05rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.04);
            background: #fff;
        }
        .nursery-table-card__toolbar h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .nursery-table-card__toolbar p {
            margin: 0.25rem 0 0;
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .nursery-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        .nursery-table thead th {
            background: transparent;
            color: #94a3b8;
            font-weight: 700;
            font-size: 0.75rem;
            text-align: right;
            padding: 0.85rem 1.4rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
            white-space: nowrap;
            letter-spacing: 0.02em;
            text-transform: none;
        }
        .nursery-table thead th.text-center,
        .nursery-table tbody td.text-center { text-align: center; }
        .nursery-table--plans thead th {
            white-space: normal;
            padding-inline: 0.85rem;
            vertical-align: bottom;
        }
        .nursery-table--plans tbody td {
            padding-inline: 0.85rem;
            vertical-align: middle;
        }
        .nursery-table tbody td {
            padding: 1.15rem 1.4rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.045);
            color: #64748b;
            vertical-align: middle;
            background: #fff;
            transition: background .15s ease;
        }
        .nursery-table tbody tr:last-child td { border-bottom: none; }
        .nursery-table tbody tr:hover td { background: #fafafa; }
        .nursery-table tbody tr:nth-child(even) td { background: #fcfcfd; }
        .nursery-table tbody tr:nth-child(even):hover td { background: #f8fafc; }
        .nursery-table-name {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }
        .nursery-table-name .nursery-person-avatar {
            width: 2.75rem;
            height: 2.75rem;
            font-size: 0.95rem;
            box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(15, 23, 42, 0.06);
        }
        .nursery-table-name__text { min-width: 0; }
        .nursery-table-name__title {
            display: block;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            font-size: 0.95rem;
        }
        .nursery-table-name__sub {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .nursery-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.72rem;
            font-weight: 700;
            border: none;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }
        .nursery-status-pill--success {
            background: #ecfdf5;
            color: #059669;
        }
        .nursery-status-pill--muted {
            background: #f1f5f9;
            color: #64748b;
        }
        .nursery-status-pill--warning {
            background: #fff7ed;
            color: #ea580c;
        }
        .nursery-table-card .erp-actions-trigger,
        .nursery-table .erp-actions-trigger {
            height: 2.25rem;
            width: 2.25rem;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            color: #64748b;
            box-shadow: none;
        }
        .nursery-table-card .erp-actions-trigger:hover,
        .nursery-table .erp-actions-trigger:hover {
            background: #fff7ed;
            border-color: rgba(249, 115, 22, 0.25);
            color: #ea580c;
        }
        .nursery-table td.tabular-nums,
        .nursery-table .tabular-nums {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: #475569;
        }
        .nursery-stats-row {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
            align-items: stretch;
        }
        @media (min-width: 768px) {
            .nursery-stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1100px) {
            .nursery-stats-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .nursery-stats-row > * { height: 100%; }
        .nursery-chart-panel__inner { padding: 1.25rem 1.35rem 1.4rem; }
        .nursery-chart-panel__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.1rem;
        }
        .nursery-chart-panel__meta {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            min-width: 0;
        }
        .nursery-chart-panel__icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #FFF1E8;
            color: #EA580C;
        }
        .nursery-chart-panel__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--nursery-text);
            line-height: 1.25;
        }
        .nursery-chart-panel__sub {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }
        .nursery-chart-panel__link {
            flex-shrink: 0;
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--nursery-primary-dark);
            text-decoration: none;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #FFF1E8;
        }
        .nursery-chart-panel__link:hover { text-decoration: underline; }
        .nursery-chart-panel__split {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .nursery-chart-panel__legend {
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 1 1 9rem;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }
        .nursery-chart-panel__legend-item {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.82rem;
        }
        .nursery-chart-panel__dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            flex-shrink: 0;
        }
        .nursery-chart-panel__legend-label {
            flex: 1;
            min-width: 0;
            color: #64748b;
            font-weight: 600;
        }
        .nursery-chart-panel__legend-value {
            font-weight: 800;
            color: #0f172a;
        }
        .nursery-chart-panel__chart {
            flex: 0 0 auto;
            margin-inline-start: auto;
        }
        .nursery-chart-donut {
            position: relative;
            width: 8.5rem;
            height: 8.5rem;
        }
        .nursery-chart-donut__svg { width: 100%; height: 100%; display: block; }
        .nursery-chart-donut__center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            pointer-events: none;
            padding: 1rem;
        }
        .nursery-chart-donut__value {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }
        .nursery-chart-donut__label {
            margin: 0.15rem 0 0;
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
            line-height: 1.2;
        }
        .nursery-chart-gauge {
            position: relative;
            width: 10rem;
            height: 6.2rem;
        }
        .nursery-chart-gauge__svg { width: 100%; height: auto; display: block; }
        .nursery-chart-gauge__center {
            position: absolute;
            left: 50%;
            bottom: 0.15rem;
            transform: translateX(-50%);
            text-align: center;
            width: 100%;
            pointer-events: none;
        }
        .nursery-chart-gauge__value {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }
        .nursery-chart-gauge__label {
            margin: 0.1rem 0 0;
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
        }
        .nursery-chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 0.4rem;
            height: 5.5rem;
            width: 8rem;
            padding-bottom: 0.15rem;
        }
        .nursery-chart-bars__col {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: flex-end;
        }
        .nursery-chart-bars__bar {
            width: 100%;
            border-radius: 0.45rem 0.45rem 0.2rem 0.2rem;
            min-height: 8%;
        }
        .nursery-chart-bars__caption {
            margin: 0.45rem 0 0;
            font-size: 0.72rem;
            color: #64748b;
            text-align: center;
        }
        .nursery-table--grid thead th {
            font-size: 0.72rem;
            padding: 0.8rem 0.7rem;
            color: #94a3b8;
            background: transparent;
        }
        .nursery-table--grid tbody td {
            padding: 0.85rem 0.7rem;
        }
        .nursery-table__sticky {
            position: sticky;
            right: 0;
            z-index: 2;
            background: #fff;
            box-shadow: -8px 0 16px rgba(15, 23, 42, 0.03);
        }
        .nursery-table thead th.nursery-table__sticky {
            background: #fff;
            z-index: 3;
        }
        .nursery-table tbody tr:hover td.nursery-table__sticky {
            background: #fafafa;
        }
        .nursery-table tbody tr:nth-child(even) td.nursery-table__sticky {
            background: #fcfcfd;
        }
        .nursery-table tbody tr:nth-child(even):hover td.nursery-table__sticky {
            background: #f8fafc;
        }
        .nursery-today-bulk-bar {
            position: sticky;
            bottom: 0;
            z-index: 40;
            margin-top: 1rem;
            padding-top: 0.5rem;
            padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
            background: linear-gradient(to top, #f7f6f3 70%, transparent);
            max-width: 100%;
        }
        .nursery-today-bulk-bar__inner {
            box-shadow: 0 8px 24px var(--nursery-shadow);
            max-width: 100%;
        }
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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.4rem;
            padding: 0.4rem;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }
        .nursery-attendance-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-height: 5.75rem;
            padding: 0.75rem 0.5rem;
            border-radius: 0.85rem;
            text-decoration: none;
            text-align: center;
            border: 2px solid transparent;
            color: var(--nursery-text-muted);
            transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .nursery-attendance-tab-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.7rem;
            background: var(--nursery-bg-mid);
            color: var(--nursery-primary-dark);
            flex-shrink: 0;
            font-size: 0;
            line-height: 1;
        }
        .nursery-attendance-tab-icon svg {
            width: 1.15rem;
            height: 1.15rem;
        }
        .nursery-attendance-tab > span:not(.nursery-attendance-tab-icon) {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            min-width: 0;
        }
        .nursery-attendance-tab-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.25;
            color: var(--nursery-text);
        }
        .nursery-attendance-tab-desc {
            display: block;
            font-size: 0.65rem;
            font-weight: 500;
            line-height: 1.3;
            color: var(--nursery-text-muted);
            opacity: 0.8;
        }
        .nursery-attendance-tab:hover {
            background: var(--nursery-bg-mid);
            border-color: var(--nursery-border);
        }
        .nursery-attendance-tab.is-active {
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            border-color: var(--nursery-primary-dark);
            box-shadow: 0 4px 14px var(--nursery-shadow);
            color: var(--nursery-on-primary);
        }
        .nursery-attendance-tab.is-active .nursery-attendance-tab-icon {
            background: rgba(255, 255, 255, 0.2);
            color: var(--nursery-on-primary);
        }
        .nursery-attendance-tab.is-active .nursery-attendance-tab-label,
        .nursery-attendance-tab.is-active .nursery-attendance-tab-desc {
            color: var(--nursery-on-primary);
            opacity: 1;
        }
        @media (max-width: 640px) {
            .nursery-attendance-tabs { grid-template-columns: 1fr; }
            .nursery-attendance-tab {
                min-height: 0;
                flex-direction: row;
                justify-content: flex-start;
                gap: 0.75rem;
                padding: 0.7rem 0.85rem;
                text-align: right;
            }
            .nursery-attendance-tab > span:not(.nursery-attendance-tab-icon) {
                align-items: flex-start;
            }
            .nursery-attendance-tab-label,
            .nursery-attendance-tab-desc { text-align: right; }
        }

        /* تبويبات صفحة الإعدادات — صف واحد متساوٍ بدون يتيم في صف ثانٍ */
        .nursery-settings-tabs {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.4rem;
            padding: 0.4rem;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }
        .nursery-settings-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-height: 5.75rem;
            padding: 0.75rem 0.4rem;
            border-radius: 0.85rem;
            text-decoration: none;
            text-align: center;
            border: 2px solid transparent;
            color: var(--nursery-text-muted);
            transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .nursery-settings-tab-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.7rem;
            background: var(--nursery-bg-mid);
            color: var(--nursery-primary-dark);
            flex-shrink: 0;
        }
        .nursery-settings-tab-icon svg {
            width: 1.15rem;
            height: 1.15rem;
        }
        .nursery-settings-tab > span:not(.nursery-settings-tab-icon) {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            min-width: 0;
        }
        .nursery-settings-tab-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.25;
            color: var(--nursery-text);
        }
        .nursery-settings-tab-desc {
            display: block;
            font-size: 0.65rem;
            font-weight: 500;
            line-height: 1.3;
            color: var(--nursery-text-muted);
            opacity: 0.8;
            max-width: 100%;
        }
        .nursery-settings-tab:hover {
            background: var(--nursery-bg-mid);
            border-color: var(--nursery-border);
        }
        .nursery-settings-tab.is-active {
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            border-color: var(--nursery-primary-dark);
            box-shadow: 0 4px 14px var(--nursery-shadow);
            color: var(--nursery-on-primary);
        }
        .nursery-settings-tab.is-active .nursery-settings-tab-icon {
            background: rgba(255, 255, 255, 0.2);
            color: var(--nursery-on-primary);
        }
        .nursery-settings-tab.is-active .nursery-settings-tab-label,
        .nursery-settings-tab.is-active .nursery-settings-tab-desc {
            color: var(--nursery-on-primary);
            opacity: 1;
        }
        @media (max-width: 1100px) {
            .nursery-settings-tabs {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            .nursery-settings-tab {
                flex: 0 0 9.5rem;
                min-height: 5.5rem;
            }
        }
        @media (max-width: 640px) {
            .nursery-settings-tabs {
                display: grid;
                grid-template-columns: 1fr;
                overflow: visible;
            }
            .nursery-settings-tab {
                flex: none;
                min-height: 0;
                flex-direction: row;
                justify-content: flex-start;
                gap: 0.75rem;
                padding: 0.7rem 0.85rem;
                text-align: right;
            }
            .nursery-settings-tab > span:not(.nursery-settings-tab-icon) {
                align-items: flex-start;
            }
            .nursery-settings-tab-label,
            .nursery-settings-tab-desc { text-align: right; }
        }
        @media (max-width: 767px) {
            .module-sidebar { display: none !important; }
        }
        #nurseryMobileSidebar { max-width: min(20rem, 100vw); }
        #nurseryMobileSidebar .offcanvas-body { overflow-x: hidden; }
    </style>
    @include('nursery.partials.finance-shell-colors')
    @stack('styles')
</head>
<body>
    <div class="flex flex-col min-h-screen"
         x-data="{
            sidebarCollapsed: localStorage.getItem('nurserySidebarCollapsed') === '1',
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('nurserySidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            }
         }">
        @include('layouts.partials.erp-global-navbar')
        <div class="nursery-shell" :class="{ 'is-sidebar-collapsed': sidebarCollapsed }">
            <aside class="module-sidebar hidden md:flex shrink-0 flex-col" :aria-expanded="(!sidebarCollapsed).toString()">
                <div class="module-sidebar-header">
                    @isset($nurseryBrand)
                        @include('nursery.partials.brand-mark', ['variant' => 'sidebar'])
                    @else
                        <div>
                            <div class="module-sidebar-icon-wrap" aria-hidden="true">🧸</div>
                            <h2 class="module-sidebar-title mt-2">{{ niche_module_label('nursery') }}</h2>
                        </div>
                    @endisset
                </div>
                <nav class="module-nav flex-1" aria-label="قائمة الحضانة">
                    <x-nursery-sidebar-nav />
                </nav>
            </aside>
            <div class="nursery-main">
                <div class="nursery-topbar">
                    <button type="button"
                            class="nursery-topbar-toggle"
                            @click="toggleSidebar()"
                            :title="sidebarCollapsed ? 'فتح القائمة' : 'طي القائمة'"
                            :aria-label="sidebarCollapsed ? 'فتح القائمة الجانبية' : 'طي القائمة الجانبية'"
                            :aria-pressed="sidebarCollapsed.toString()">
                        <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25H12"/>
                        </svg>
                        <svg x-show="sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-orange-950 truncate">{{ niche_module_label('nursery') }}</p>
                        <p class="text-xs text-orange-800/70 truncate">@yield('topbar_subtitle', 'إدارة الحضانة اليومية')</p>
                    </div>
                </div>
                <div class="md:hidden flex items-center gap-3 px-4 py-2 bg-white shrink-0" style="border-bottom: 1px solid var(--nursery-border);">
                    <button type="button" class="nursery-btn nursery-btn-soft p-2" data-bs-toggle="offcanvas" data-bs-target="#nurseryMobileSidebar" aria-label="قائمة الحضانة">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="text-lg font-semibold" style="color: var(--nursery-text);">{{ niche_module_label('nursery') }}</span>
                </div>
                <main class="nursery-main-inner">
                    <x-flash-messages />
                    <div class="content-wrap w-full max-w-[96rem] mx-auto px-1">@yield('content')</div>
                </main>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="nurseryMobileSidebar" dir="rtl" aria-labelledby="nurseryMobileSidebarLabel">
        <div class="offcanvas-header border-bottom align-items-center">
            <h5 class="offcanvas-title font-semibold mb-0" id="nurseryMobileSidebarLabel">{{ niche_module_label('nursery') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="module-nav flex flex-col gap-1 p-2">
                <x-nursery-sidebar-nav />
            </nav>
        </div>
    </div>

    @include('layouts.partials.erp-shell-footer-scripts')
    @stack('scripts')
</body>
</html>
