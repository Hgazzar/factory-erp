@props([
    'shell' => 'erp',
    'linkClass' => 'module-nav-link',
])

@php
    $navigation = $tenantNavigation ?? null;
    $links = $navigation ? $navigation->visibleLinks(shell: $shell) : [];
@endphp

@foreach($links as $link)
    <a href="{{ $link->url() }}"
       @if($shell === 'nursery') title="{{ $link->label }}" @endif
       class="{{ $linkClass }} {{ $link->isActive() ? 'active' : '' }}">
        @if($shell === 'pos')
            @include('components.partials.pos-nav-link-icon', ['linkKey' => $link->key])
            {{ $link->label }}
        @elseif($shell === 'fleet')
            <span>
                @include('components.partials.fleet-nav-link-icon', ['linkKey' => $link->key])
                @if($link->infoField)
                    <x-info :field="$link->infoField" />
                @endif
                {{ $link->label }}
            </span>
        @elseif($shell === 'clinic')
            <span>
                @if($link->infoField)
                    <x-info :field="$link->infoField" />
                @endif
                {{ $link->label }}
            </span>
        @elseif($shell === 'nursery')
            <span class="module-nav-icon" aria-hidden="true">
                @include('components.partials.nursery-nav-link-icon', ['linkKey' => $link->key])
            </span>
            <span class="module-nav-label">
                @if($link->infoField)
                    <x-info :field="$link->infoField" />
                @endif
                {{ $link->label }}
            </span>
        @else
            {{ $link->label }}
        @endif
    </a>
@endforeach
