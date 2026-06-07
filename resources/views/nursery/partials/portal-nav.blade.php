@php
    $current = request()->route()?->getName() ?? '';
@endphp
<a href="{{ route('nursery.portal.home', ['tenant_slug' => $tenantSlug]) }}"
   class="{{ str_starts_with($current, 'nursery.portal.home') || str_starts_with($current, 'nursery.portal.children') ? 'is-active' : '' }}">الرئيسية</a>
<a href="{{ route('nursery.portal.finance', ['tenant_slug' => $tenantSlug]) }}"
   class="{{ $current === 'nursery.portal.finance' ? 'is-active' : '' }}">المالية</a>
<a href="{{ route('nursery.portal.calendar', ['tenant_slug' => $tenantSlug]) }}"
   class="{{ $current === 'nursery.portal.calendar' ? 'is-active' : '' }}">التقويم</a>
