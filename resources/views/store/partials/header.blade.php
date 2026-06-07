<header class="sticky top-0 z-50 glass border-b border-gray-200/50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <a href="{{ $routes['home'] }}" class="flex items-center gap-3">
                @if(!empty($tenantBrand['logo_url'] ?? null))
                    <img src="{{ $tenantBrand['logo_url'] }}" alt="" class="w-11 h-11 rounded-xl object-cover shadow-lg">
                @else
                    <div class="w-11 h-11 rounded-xl bg-store-gradient-br flex items-center justify-center shadow-lg">
                        <i class="fas fa-store text-white text-lg"></i>
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-black gradient-text">{{ $storeName }}</h1>
                    <p class="text-xs text-gray-400 -mt-1">تسوق بأفضل الأسعار</p>
                </div>
            </a>

            <div class="hidden md:flex items-center bg-gray-100 rounded-xl px-4 py-2.5 flex-1 max-w-xl mx-8">
                <i class="fas fa-search text-gray-400 ml-2"></i>
                <input type="search" placeholder="ابحث عن منتج..." class="bg-transparent border-none outline-none w-full text-sm font-cairo"
                       x-model="headerSearch" @input.debounce.300ms="applyHeaderSearch()">
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="openCart()" class="relative p-2.5 rounded-xl hover:bg-gray-100 transition-all group">
                    <i class="fas fa-shopping-bag text-2xl text-gray-600 group-hover:text-store-primary transition-colors"></i>
                    <span class="notification-badge" x-show="cartCount > 0" x-text="cartCount" x-cloak></span>
                </button>
                <button type="button" class="md:hidden p-2.5 rounded-xl hover:bg-gray-100" @click="mobileSearchOpen = !mobileSearchOpen">
                    <i class="fas fa-search text-xl text-gray-600"></i>
                </button>
            </div>
        </div>
        <div class="md:hidden mt-3" x-show="mobileSearchOpen" x-cloak>
            <div class="flex items-center bg-gray-100 rounded-xl px-4 py-2.5">
                <i class="fas fa-search text-gray-400 ml-2"></i>
                <input type="search" placeholder="ابحث عن منتج..." class="bg-transparent border-none outline-none w-full text-sm"
                       x-model="headerSearch" @input.debounce.300ms="applyHeaderSearch()">
            </div>
        </div>
    </div>
</header>
