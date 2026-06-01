@props(['banners'])

<section class="ak-hero-cinema"
         x-data="akHeroCinema(@js($banners), '{{ $routes['shop'] }}')"
         x-init="init()"
         @touchstart="onTouchStart($event)"
         @touchend="onTouchEnd($event)">
    <template x-for="(slide, i) in slides" :key="i">
        <div class="ak-hero-cinema__slide"
             :class="{ 'is-active': active === i }"
             :style="'background-image:url(' + slide.image_url + ')'"></div>
    </template>
    <div class="ak-hero-cinema__overlay"></div>

    <div class="ak-hero-cinema__content">
        <p class="ak-eyebrow" style="color:var(--ak-gold)" x-text="current.offer || 'مجموعة جديدة'"></p>
        <h1 class="ak-hero-title" x-text="current.title"></h1>
        <p class="ak-body-lg" x-show="current.subtitle" x-text="current.subtitle"></p>
        <div style="display:flex;flex-wrap:wrap;gap:var(--ak-3);margin-top:var(--ak-2)">
            <a :href="current.cta_url || shopUrl" class="ak-btn ak-btn--white" x-text="current.cta_label || 'اكتشف المجموعة'"></a>
            <a :href="shopUrl" class="ak-btn ak-btn--ghost" style="color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,.35)">تسوق الكل</a>
        </div>
    </div>

    <div class="ak-hero-dots" x-show="slides.length > 1">
        <template x-for="(slide, i) in slides" :key="'dot'+i">
            <button type="button" class="ak-hero-dot" :class="{ 'is-active': active === i }" @click="go(i)" :aria-label="'شريحة ' + (i+1)"></button>
        </template>
    </div>
</section>
