@props([
    'module',
])

@php
    $meta = ($tenantNavigation ?? null)?->erpModuleShellMeta($module);
@endphp

@if($meta)
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileModuleSidebar" dir="rtl">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="module-sidebar-icon-wrap" style="background: {{ $meta['iconBg'] }}; color: {{ $meta['iconColor'] }};">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">{!! $meta['icon'] !!}</svg>
            </div>
            <h5 class="offcanvas-title font-semibold mb-0">{{ $meta['title'] }}</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column" style="overflow:hidden;">
        <div class="p-3 border-bottom shrink-0">
            <a href="{{ route('dashboard') }}" class="module-sidebar-back d-inline-flex">العودة للوحدات</a>
            <input type="search" class="module-sidebar-search mt-2 w-100" placeholder="بحث" aria-label="بحث">
        </div>
        <nav class="module-nav flex-1 min-h-0 p-2" style="overflow-y:auto; -webkit-overflow-scrolling:touch; overscroll-behavior:contain;">
            <x-nav-shell-links :shell="$module" link-class="module-nav-link d-block" />
        </nav>
    </div>
</div>
@endif
