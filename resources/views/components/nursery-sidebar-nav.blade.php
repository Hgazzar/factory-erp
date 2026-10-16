@php
    $navigation = $tenantNavigation ?? null;
    /** @var list<\App\Data\Navigation\NavigationLink> $links */
    $links = $navigation ? $navigation->visibleLinks(shell: 'nursery') : [];

    $financeLinks = [];
    $otherLinks = [];
    foreach ($links as $link) {
        if (str_starts_with($link->key, 'nursery.finance')) {
            $financeLinks[] = $link;
        } else {
            $otherLinks[] = $link;
        }
    }

    $navItems = [];
    $financeInserted = false;
    foreach ($otherLinks as $link) {
        if (! $financeInserted && $financeLinks !== [] && $link->key === 'nursery.settings') {
            $navItems[] = ['type' => 'finance_group', 'links' => $financeLinks];
            $financeInserted = true;
        }
        $navItems[] = ['type' => 'link', 'link' => $link];
    }
    if (! $financeInserted && $financeLinks !== []) {
        $navItems[] = ['type' => 'finance_group', 'links' => $financeLinks];
    }

    $financeGroupActive = false;
    foreach ($financeLinks as $financeLink) {
        if ($financeLink->isActive()) {
            $financeGroupActive = true;
            break;
        }
    }
@endphp

@foreach($navItems as $item)
    @if($item['type'] === 'link')
        @php $link = $item['link']; @endphp
        <a href="{{ $link->url() }}"
           title="{{ $link->label }}"
           class="module-nav-link {{ $link->isActive() ? 'active' : '' }}">
            <span class="module-nav-icon" aria-hidden="true">
                @include('components.partials.nursery-nav-link-icon', ['linkKey' => $link->key])
            </span>
            <span class="module-nav-label">
                @if($link->infoField)
                    <x-info :field="$link->infoField" />
                @endif
                {{ $link->label }}
            </span>
        </a>
    @else
        @php
            /** @var list<\App\Data\Navigation\NavigationLink> $groupLinks */
            $groupLinks = $item['links'];
        @endphp
        <div class="module-nav-group"
             x-data="{ open: {{ $financeGroupActive ? 'true' : 'false' }} }"
             data-nursery-nav-group="finance">
            <button type="button"
                    class="module-nav-link module-nav-group__toggle w-full text-start {{ $financeGroupActive ? 'active' : '' }}"
                    title="القسم المالي"
                    @click="open = !open"
                    :aria-expanded="open.toString()">
                <span class="module-nav-icon" aria-hidden="true">
                    @include('components.partials.nursery-nav-link-icon', ['linkKey' => 'nursery.finance'])
                </span>
                <span class="module-nav-label flex-1">
                    <x-info field="nursery.nav_finance_section" />
                    القسم المالي
                </span>
                <span class="module-nav-group__chevron module-nav-label" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div class="module-nav-group__panel"
                 x-show="open"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                @foreach($groupLinks as $link)
                    <a href="{{ $link->url() }}"
                       title="{{ $link->label }}"
                       class="module-nav-link module-nav-group__link {{ $link->isActive() ? 'active' : '' }}">
                        <span class="module-nav-icon" aria-hidden="true">
                            @include('components.partials.nursery-nav-link-icon', ['linkKey' => $link->key])
                        </span>
                        <span class="module-nav-label">
                            @if($link->infoField)
                                <x-info :field="$link->infoField" />
                            @endif
                            {{ $link->label }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
