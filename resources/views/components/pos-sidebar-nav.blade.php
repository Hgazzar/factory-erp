@php
    $linkClass = 'module-nav-link';
@endphp

<a href="{{ route('pos.dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('pos.dashboard') ? 'active' : '' }}">
    <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/>
        <path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/>
    </svg>
    لوحة نقاط البيع
</a>

<a href="{{ route('pos.cashier') }}" class="{{ $linkClass }} {{ request()->routeIs('pos.cashier') ? 'active' : '' }}">
    <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v2h14V2a1 1 0 0 0-1-1H2zm13 4H1v7a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z"/>
        <path d="M5 13a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm7 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>
    </svg>
    الكاشير
</a>

@if(auth()->user()?->isAdminOrSuperAdmin())
    <a href="{{ route('pos.devices.index') }}" class="{{ $linkClass }} {{ request()->routeIs('pos.devices.*') ? 'active' : '' }}">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/>
            <path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/>
        </svg>
        الأجهزة
    </a>
@endif

<a href="{{ route('pos.sessions.index') }}" class="{{ $linkClass }} {{ request()->routeIs('pos.sessions.*') ? 'active' : '' }}">
    <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
    </svg>
    الجلسات
</a>

<a href="{{ route('pos.receipts.index') }}" class="{{ $linkClass }} {{ request()->routeIs('pos.receipts.*') ? 'active' : '' }}">
    <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
        <path d="M5 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 5 8zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
    </svg>
    الإيصالات
</a>
