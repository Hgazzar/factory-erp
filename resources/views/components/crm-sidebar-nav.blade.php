@php
    $linkClass = 'module-nav-link';
    $isCustomersIndex = request()->routeIs('crm.customers.index');
    $isPotentialLeadsContext = request()->routeIs('crm.customers.create')
        || ($isCustomersIndex && request('crm_status') === 'potential');
@endphp

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.dashboard') ? 'active' : '' }}" data-crm-nav-search="لوحة إدارة العملاء dashboard">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13z"/>
            <path d="M2 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 2 13v-3zm8-8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3A.5.5 0 0 1 10 5V2zm0 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-3z"/>
        </svg>
        لوحة إدارة العملاء
    </a>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.customers.index', ['crm_status' => 'potential']) }}" class="{{ $linkClass }} {{ $isPotentialLeadsContext ? 'active' : '' }}" data-crm-nav-search="العملاء المحتملين leads محتمل">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m4.5 0a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5M12.5 9a4.5 4.5 0 0 1 3.5 1.93V16H9v-1.07A4.5 4.5 0 0 1 12.5 9"/>
        </svg>
        العملاء المحتملين
    </a>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.opportunities.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.opportunities.*') ? 'active' : '' }}" data-crm-nav-search="الفرص opportunities">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.917 3.013h-.971L14 5.29V7.5a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h2.291zm-1.5 0V2h-1.5v4h3.5z"/>
        </svg>
        الفرص
    </a>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.customers.index') }}" class="{{ $linkClass }} {{ ($isCustomersIndex && ! request()->filled('crm_status') && ! request()->routeIs('crm.customers.create')) || request()->routeIs('crm.customers.show') || request()->routeIs('crm.customers.new') ? 'active' : '' }}" data-crm-nav-search="جهات الاتصال contacts عملاء">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105V5.383zM1 6.116l4.394 2.636L1 11.105V6.116zm6.356 4.474L1.293 5.293A1 1 0 0 0 1 5.383V11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5.383a1 1 0 0 0-.293-.09L8.643 10.59z"/>
        </svg>
        جهات الاتصال
    </a>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.activities.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.activities.*') ? 'active' : '' }}" data-crm-nav-search="الأنشطة activities متابعة">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09zM4.157 8.5l2.744 3.332L11.44 7.13l-3.466-3.712L4.157 8.5z"/>
        </svg>
        الأنشطة
    </a>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.segments.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.segments.*') ? 'active' : '' }}" data-crm-nav-search="شرائح العملاء segments">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
        </svg>
        شرائح العملاء
    </a>
</div>

@php
    $isLoyaltyGroupActive = request()->routeIs('crm.loyalty.*') || request()->routeIs('crm.memberships.*');
@endphp
<div class="crm-nav-filter-item mt-1">
    <details class="rounded-lg border border-gray-100 bg-white/70" @if($isLoyaltyGroupActive) open @endif>
        <summary class="{{ $linkClass }} {{ $isLoyaltyGroupActive ? 'active crm-nav-neon-active' : '' }}" data-crm-nav-search="نظام الولاء loyalty plans subscriptions" style="list-style:none; cursor:pointer;">
            <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M3.612 15.443c-.396.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.33-.314-.159-.888.282-.95l4.899-.696L7.538.792a.513.513 0 0 1 .927 0l2.184 4.327 4.898.696c.441.062.612.636.283.95l-3.523 3.356.83 4.73c.078.443-.35.79-.746.592L8 13.187z"/>
            </svg>
            نظام الولاء
        </summary>
        <div class="px-2 pb-2 pt-1 flex flex-col gap-1">
            <a href="{{ route('crm.loyalty.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.loyalty.index', 'crm.loyalty.create', 'crm.loyalty.store') ? 'active crm-nav-neon-active' : '' }}" data-crm-nav-search="خطط العضويات plans loyalty">
                <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M2.866 14.85c-.078.444.36.791.746.593L8 13.187l4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.523-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356z"/>
                </svg>
                خطط العضويات
            </a>
            <a href="{{ route('crm.memberships.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.memberships.*') ? 'active crm-nav-neon-active' : '' }}" data-crm-nav-search="سجل المشتركين subscriptions memberships">
                <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M3.5 1A1.5 1.5 0 0 0 2 2.5v11A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 12.5 1h-9zM5 4h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1zm0 3h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1zm0 3h4a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1z"/>
                </svg>
                سجل المشتركين
            </a>
        </div>
    </details>
</div>

<div class="crm-nav-filter-item">
    <a href="{{ route('crm.appointments.index') }}" class="{{ $linkClass }} {{ request()->routeIs('crm.appointments.*') ? 'active' : '' }}" data-crm-nav-search="المواعيد appointments">
        <svg class="module-nav-icon shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2m13 4H1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/>
        </svg>
        المواعيد
    </a>
</div>
