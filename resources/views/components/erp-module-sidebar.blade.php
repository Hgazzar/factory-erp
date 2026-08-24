@props([
    'module',
])

@php
    $meta = ($tenantNavigation ?? null)?->erpModuleShellMeta($module);
@endphp

@if($meta)
<aside class="module-sidebar min-h-0 hidden md:flex no-print shrink-0 flex-col">
    <div class="module-sidebar-header shrink-0">
        <div class="module-sidebar-icon-wrap" style="background: {{ $meta['iconBg'] }}; color: {{ $meta['iconColor'] }};">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16">{!! $meta['icon'] !!}</svg>
        </div>
        <h2 class="module-sidebar-title">{{ $meta['title'] }}</h2>
        <a href="{{ route('dashboard') }}" class="module-sidebar-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/><path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/></svg>
            العودة للوحدات
        </a>
        <input type="search" class="module-sidebar-search" placeholder="بحث" aria-label="بحث في القسم">
    </div>
    <nav class="module-nav flex-1 min-h-0 overflow-y-auto">
        <x-nav-shell-links :shell="$module" />
    </nav>
    <div class="module-sidebar-footer shrink-0">
        <span class="d-block mb-1 text-gray-400">{{ config('app.name') }}</span>
        <a href="#"><span>طي</span> <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg></a>
    </div>
</aside>
@endif
