@php
    use App\Support\FleetAccess;
    $access = app(FleetAccess::class);
@endphp
<nav class="flex flex-col gap-1">
    @if($access->allows(FleetAccess::CAP_VIEW_DASHBOARD))
        <a href="{{ route('fleet.dashboard') }}"
           class="module-nav-link {{ request()->routeIs('fleet.dashboard') ? 'active' : '' }}">
            <span>🏠 <x-info field="fleet.nav_dashboard" /> لوحة التحكم</span>
        </a>
    @endif
    @if($access->allows(FleetAccess::CAP_MANAGE_AGENTS))
        <a href="{{ route('fleet.agents.index') }}"
           class="module-nav-link {{ request()->routeIs('fleet.agents.*') ? 'active' : '' }}">
            <span>🚚 <x-info field="fleet.nav_agents" /> {{ niche_label('entities.agent', 'المناديب') }}</span>
        </a>
    @endif
    @if($access->allows(FleetAccess::CAP_MANAGE_CUSTOMERS))
        <a href="{{ route('fleet.customers.index') }}"
           class="module-nav-link {{ request()->routeIs('fleet.customers.*') ? 'active' : '' }}">
            <span>👥 <x-info field="fleet.nav_customers" /> {{ niche_label('entities.customer', 'العملاء') }}</span>
        </a>
    @endif
    @if($access->allows(FleetAccess::CAP_MANAGE_PRODUCTS))
        <a href="{{ route('fleet.products.index') }}"
           class="module-nav-link {{ request()->routeIs('fleet.products.*') ? 'active' : '' }}">
            <span>📦 <x-info field="fleet.nav_products" /> الكتalog الخفيف</span>
        </a>
    @endif
</nav>
