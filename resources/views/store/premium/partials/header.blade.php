<header class="ak-header" :class="{ 'is-scrolled': headerScrolled }">
    <div class="ak-container ak-header__inner">
        <button type="button" class="ak-icon-btn ak-menu-btn" @click="mobileOpen = true" aria-label="القائمة">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>

        <a href="{{ $routes['home'] }}" class="ak-logo">
            @if(!empty($company?->logo_url))
                <img src="{{ asset('storage/'.$company->logo_url) }}" alt="">
            @endif
            {{ $storeName }}
        </a>

        <nav class="ak-nav" aria-label="التنقل الرئيسي">
            <a href="{{ $routes['home'] }}" @class(['is-active' => request()->routeIs('store.portal.home')])>الرئيسية</a>
            <a href="{{ $routes['shop'] }}" @class(['is-active' => request()->routeIs('store.portal.shop')])>المتجر</a>
            <a href="{{ $routes['about'] }}">من نحن</a>
            <a href="{{ $routes['contact'] }}">اتصل بنا</a>
        </nav>

        <div class="ak-search-wrap ak-search-wrap--desktop" x-data="akLiveSearch('{{ $apiBase }}')" @click.outside="open = false">
            <span class="ak-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </span>
            <input type="search" class="ak-input" placeholder="ابحث..." x-model="q" @input.debounce.300ms="search()" @focus="q && search()">
            <div class="ak-search-dropdown" x-show="open && results.length" x-cloak>
                <template x-for="item in results" :key="item.id">
                    <a :href="productUrl(item.id)" class="ak-search-item" @click="open = false">
                        <img :src="item.image_url" alt="">
                        <span x-text="item.name"></span>
                    </a>
                </template>
            </div>
        </div>

        <div class="ak-header__actions">
            <a href="{{ $routes['shop'] }}?wishlist=1" class="ak-icon-btn max-lg:hidden" aria-label="المفضلة">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            </a>
            <button type="button" class="ak-icon-btn" @click="openCart()" aria-label="السلة">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                <span class="ak-badge" x-show="cartCount > 0" x-text="cartCount" x-cloak></span>
            </button>
        </div>
    </div>
</header>

@include('store.premium.partials.mobile-drawer')
