@props([
    'module' => '',
])

@php
    $dashboardActions = ($tenantNavigation ?? null)?->visibleDashboardActions($module) ?? [];
@endphp

@if(auth()->user()?->isAdminOrSuperAdmin() && $dashboardActions !== [])
    <div {{ $attributes->merge(['class' => 'ufuq-card-actions']) }}>
        @foreach($dashboardActions as $action)
            <a href="{{ $action->url() }}" class="ufuq-qbtn">
                @if($action->icon === 'plus')
                    <span aria-hidden="true">+</span>
                @endif
                {{ $action->label }}
            </a>
        @endforeach
    </div>
@endif
