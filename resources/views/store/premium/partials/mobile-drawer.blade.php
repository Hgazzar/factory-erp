<div class="ak-mobile-drawer-backdrop" :class="{ 'is-open': mobileOpen }" @click="mobileOpen = false" x-cloak></div>
<aside class="ak-mobile-drawer" :class="{ 'is-open': mobileOpen }" aria-label="قائمة الجوال" x-cloak>
    <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="ak-logo">{{ $storeName }}</span>
        <button type="button" class="ak-icon-btn" @click="mobileOpen = false" aria-label="إغلاق">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav style="display:flex;flex-direction:column">
        <a href="{{ $routes['home'] }}" @click="mobileOpen = false">الرئيسية</a>
        <a href="{{ $routes['shop'] }}" @click="mobileOpen = false">المتجر</a>
        <a href="{{ $routes['about'] }}" @click="mobileOpen = false">من نحن</a>
        <a href="{{ $routes['contact'] }}" @click="mobileOpen = false">اتصل بنا</a>
        <a href="{{ $routes['faq'] }}" @click="mobileOpen = false">الأسئلة الشائعة</a>
    </nav>
    <form action="{{ $routes['shop'] }}" method="get" class="mt-auto">
        <input type="search" name="q" class="ak-input" placeholder="ابحث في المتجر...">
    </form>
</aside>
