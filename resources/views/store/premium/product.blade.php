@extends('layouts.store-premium')

@section('title', ($product['seo_title'] ?? $product['name']).' — '.$storeName)

@section('content')
@php $p = $product; @endphp
<div class="ak-container"
     x-data="{
        gallery: @js($p['gallery']),
        activeImage: @js($p['gallery'][0] ?? $p['image_url']),
        qty: 1,
        tab: 'desc',
        showSticky: false,
        zoomed: false
     }"
     x-init="window.addEventListener('scroll', () => { showSticky = window.scrollY > 480 })">

    <nav class="ak-caption" style="padding:var(--ak-6) 0">
        <a href="{{ $routes['home'] }}" style="color:inherit;text-decoration:none">الرئيسية</a>
        <span style="margin:0 var(--ak-2)">/</span>
        <a href="{{ $routes['shop'] }}" style="color:inherit;text-decoration:none">المتجر</a>
        <span style="margin:0 var(--ak-2)">/</span>
        <span>{{ $p['name'] }}</span>
    </nav>

    <div class="ak-pdp">
        <div>
            <div class="ak-gallery__main" @click="zoomed = true">
                <img :src="activeImage" alt="{{ $p['name'] }}" id="main-image">
            </div>
            @if(count($p['gallery']) > 1)
            <div class="ak-thumbs">
                @foreach($p['gallery'] as $img)
                <button type="button" @click="activeImage = @js($img)" :class="activeImage === @js($img) && 'is-active'">
                    <img src="{{ $img }}" alt="">
                </button>
                @endforeach
            </div>
            @endif
        </div>

        <div class="ak-pdp-info">
            @if($p['category_name'])
                <p class="ak-eyebrow">{{ $p['category_name'] }}</p>
            @endif
            <h1 class="ak-display" style="font-size:clamp(1.75rem,3vw,2.25rem);margin:var(--ak-3) 0">{{ $p['name'] }}</h1>

            @if(($p['review_count'] ?? 0) > 0)
                <p class="ak-caption" style="margin-bottom:var(--ak-6)">★ {{ $p['avg_rating'] }} · {{ $p['review_count'] }} تقييم</p>
            @endif

            <div class="ak-card__price" style="margin-bottom:var(--ak-6)">
                <span class="ak-price" style="font-size:1.5rem">{{ number_format($p['sale_price'], 2) }} {{ $currencyCode }}</span>
                @if(!empty($p['compare_at_price']) && $p['compare_at_price'] > $p['sale_price'])
                    <span class="ak-price--old">{{ number_format($p['compare_at_price'], 2) }}</span>
                    <span class="ak-card__badge" style="position:static"><span>−{{ $p['discount_percent'] }}%</span></span>
                @endif
            </div>

            <p class="ak-stock @if($p['in_stock']) ak-stock--in @else ak-stock--out @endif" style="margin-bottom:var(--ak-6)">
                @if($p['in_stock']) ● متوفر — {{ $p['stock_qty'] }} قطعة @else ● نفد المخزون @endif
                @if($p['sku']) · <span class="ak-caption">SKU {{ $p['sku'] }}</span> @endif
            </p>

            <div style="display:flex;flex-wrap:wrap;gap:var(--ak-4);align-items:center;margin-bottom:var(--ak-8)">
                <span style="font-weight:500;font-size:0.875rem">الكمية</span>
                <div class="ak-qty">
                    <button type="button" @click="qty = Math.max(1, qty - 1)">−</button>
                    <input type="number" min="1" x-model.number="qty">
                    <button type="button" @click="qty++">+</button>
                </div>
            </div>

            <button type="button" class="ak-btn ak-btn--primary ak-btn--block"
                    @click="$root.addProduct(@js($p), qty)"
                    @if(!$p['in_stock']) disabled @endif>
                أضف إلى السلة
            </button>

            <div class="ak-trust">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    دفع آمن
                </span>
                <span>شحن 2–5 أيام</span>
                <span>إرجاع 14 يوماً</span>
            </div>
        </div>
    </div>

    <div class="ak-sticky-bar" :class="{ 'is-visible': showSticky }">
        <span class="ak-price" style="flex:1">{{ number_format($p['sale_price'], 2) }} {{ $currencyCode }}</span>
        <button type="button" class="ak-btn ak-btn--primary" style="flex:1.5" @click="$root.addProduct(@js($p), qty)" @if(!$p['in_stock']) disabled @endif>أضف للسلة</button>
    </div>

    <div class="ak-tabs ak-container" style="padding:0">
        <div style="display:flex;border-bottom:1px solid var(--ak-line)">
            <button type="button" class="ak-tab-btn" :class="tab === 'desc' && 'is-active'" @click="tab='desc'">الوصف</button>
            <button type="button" class="ak-tab-btn" :class="tab === 'ship' && 'is-active'" @click="tab='ship'">التوصيل</button>
            <button type="button" class="ak-tab-btn" :class="tab === 'faq' && 'is-active'" @click="tab='faq'">الأسئلة</button>
        </div>
        <div style="padding:var(--ak-8) 0">
            <div x-show="tab === 'desc'">
                <p class="ak-body-lg" style="white-space:pre-wrap;margin:0">{{ $p['description'] ?: 'منتج فاخر من مجموعة أكواد — جودة استثنائية وتفاصيل مدروسة.' }}</p>
            </div>
            <div x-show="tab === 'ship'" class="ak-accordion">
                <p class="ak-body-lg">توصيل سريع خلال 2–5 أيام عمل. الدفع عند الاستلام متاح لجميع المناطق المدعومة.</p>
            </div>
            <div x-show="tab === 'faq'" class="ak-accordion">
                <details open><summary>ما طرق الدفع؟</summary><p class="ak-caption" style="margin-top:var(--ak-3)">الدفع عند الاستلام (COD).</p></details>
                <details><summary>هل يمكن الإرجاع؟</summary><p class="ak-caption" style="margin-top:var(--ak-3)">نعم، خلال 14 يوماً بحالة المنتج الأصلية.</p></details>
            </div>
        </div>
    </div>

    @if(count($p['related'] ?? []))
    <section class="ak-section">
        <p class="ak-eyebrow">قد يعجبك</p>
        <h2 class="ak-section-title" style="margin-bottom:var(--ak-8)">منتجات ذات صلة</h2>
        <div class="ak-grid">
            @foreach($p['related'] as $rel)
                @if($rel['id'] !== $p['id'])
                    @include('store.premium.partials.product-card', ['product' => $rel, 'tenantSlug' => $tenantSlug])
                @endif
            @endforeach
        </div>
    </section>
    @endif

    <div class="ak-modal-backdrop" x-show="zoomed" x-cloak @click="zoomed = false" @keydown.escape.window="zoomed = false">
        <img :src="activeImage" alt="" style="max-width:95%;max-height:90vh;border-radius:var(--ak-r-lg);box-shadow:var(--ak-shadow-lg)">
    </div>
</div>
@endsection
