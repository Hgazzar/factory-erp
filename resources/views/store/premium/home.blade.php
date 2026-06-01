@extends('layouts.store-premium')

@section('title', $storeName.' — متجر فاخر')

@section('content')
@php
    $heroBanners = array_map(function (array $b) use ($storeSettings) {
        $b['offer'] = $storeSettings->hero_offer_text ?? 'مجموعة جديدة';
        return $b;
    }, $banners);
@endphp
@include('store.premium.partials.hero-cinema', ['banners' => $heroBanners])

@if(count($categories))
<section class="ak-section ak-section--tight">
    <div class="ak-container">
        <div style="display:flex;justify-content:space-between;align-items:end;margin-bottom:var(--ak-6)">
            <div>
                <p class="ak-eyebrow">تسوق حسب</p>
                <h2 class="ak-section-title">المجموعات</h2>
            </div>
            <a href="{{ $routes['shop'] }}" class="ak-caption" style="color:var(--ak-gold);text-decoration:none;font-weight:600">عرض الكل ←</a>
        </div>
        <div class="ak-collections">
            @foreach($categories as $cat)
                <a href="{{ $routes['shop'] }}?category_id={{ $cat['id'] }}" class="ak-collection-pill">
                    <span class="ak-eyebrow" style="display:block;margin-bottom:var(--ak-2)">مجموعة</span>
                    <span style="font-weight:600">{{ $cat['name'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@php
    $sections = [
        ['eyebrow' => 'مختارة', 'title' => 'مختارات الموسم', 'items' => $featured, 'query' => ['featured' => 1]],
        ['eyebrow' => 'رائج', 'title' => 'الأكثر رواجاً', 'items' => $trending, 'query' => ['trending' => 1]],
        ['eyebrow' => 'الأكثر مبيعاً', 'title' => 'قائمة الأكثر مبيعاً', 'items' => $bestsellers, 'query' => ['bestseller' => 1]],
    ];
@endphp

@foreach($sections as $section)
    @if(count($section['items']))
    <section class="ak-section">
        <div class="ak-container">
            <div style="display:flex;justify-content:space-between;align-items:end;margin-bottom:var(--ak-8)">
                <div>
                    <p class="ak-eyebrow">{{ $section['eyebrow'] }}</p>
                    <h2 class="ak-section-title">{{ $section['title'] }}</h2>
                </div>
                <a href="{{ $routes['shop'] }}@if($section['query'])?{{ http_build_query($section['query']) }}@endif"
                   style="font-size:0.8125rem;font-weight:600;color:var(--ak-gold);text-decoration:none">عرض الكل</a>
            </div>
            <div class="ak-grid">
                @foreach($section['items'] as $product)
                    @include('store.premium.partials.product-card', ['product' => $product, 'tenantSlug' => $tenantSlug])
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endforeach

<section class="ak-section">
    <div class="ak-container">
        <div class="ak-editorial">
            <div>
                <p class="ak-eyebrow">عرض محدود</p>
                <h2 class="ak-section-title">{{ $storeSettings->hero_offer_text ?: 'خصومات الموسم' }}</h2>
                <p class="ak-body-lg" style="margin:var(--ak-3) 0 0">اكتشف قطعاً حصرية بأسعار استثنائية — لفترة محدودة.</p>
            </div>
            <a href="{{ $routes['shop'] }}" class="ak-btn ak-btn--gold">تسوق العروض</a>
        </div>
    </div>
</section>

@if(!empty($allProducts))
<section class="ak-section ak-section--tight">
    <div class="ak-container">
        <div style="margin-bottom:var(--ak-8)">
            <p class="ak-eyebrow">المتجر</p>
            <h2 class="ak-section-title">جميع المنتجات</h2>
        </div>
        <div class="ak-grid">
            @foreach($allProducts as $product)
                @include('store.premium.partials.product-card', ['product' => $product, 'tenantSlug' => $tenantSlug])
            @endforeach
        </div>
        <div style="text-align:center;margin-top:var(--ak-10)">
            <a href="{{ $routes['shop'] }}" class="ak-btn ak-btn--ghost">استكشف المتجر كاملاً</a>
        </div>
    </div>
</section>
@endif

<section class="ak-section">
    <div class="ak-container ak-container--narrow" style="text-align:center">
        <p class="ak-eyebrow">قصتنا</p>
        <h2 class="ak-section-title" style="margin:var(--ak-3) auto var(--ak-4)">أناقة بمعايير عالمية</h2>
        <p class="ak-body-lg">
            {{ \Illuminate\Support\Str::limit(strip_tags($storeSettings->about_us ?: 'نصمم تجربة تسوق راقية تجمع الجودة والبساطة — إحساس المتاجر الأوروبية الفاخرة بروح محلية أصيلة.'), 320) }}
        </p>
        <a href="{{ $routes['about'] }}" class="ak-btn ak-btn--ghost" style="margin-top:var(--ak-6)">اعرف المزيد</a>
    </div>
</section>

<section class="ak-section ak-section--tight">
    <div class="ak-container">
        <div class="ak-grid" style="grid-template-columns:repeat(2,1fr);gap:var(--ak-4)">
            @foreach([
                ['title' => 'شحن سريع', 'desc' => 'توصيل خلال 2–5 أيام'],
                ['title' => 'دفع آمن', 'desc' => 'الدفع عند الاستلام'],
                ['title' => 'إرجاع سهل', 'desc' => 'خلال 14 يوماً'],
                ['title' => 'جودة مضمونة', 'desc' => 'منتجات أصلية 100%'],
            ] as $trust)
            <div class="ak-panel" style="padding:var(--ak-6);text-align:center">
                <p style="font-weight:600;margin:0 0 var(--ak-1)">{{ $trust['title'] }}</p>
                <p class="ak-caption" style="margin:0">{{ $trust['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="ak-section">
    <div class="ak-container">
        <div class="ak-panel" style="text-align:center;max-width:28rem;margin:0 auto;padding:var(--ak-10)">
            <p class="ak-eyebrow">النشرة</p>
            <h2 class="ak-section-title" style="margin:var(--ak-3) 0">كن أول من يعرف</h2>
            <p class="ak-caption" style="margin-bottom:var(--ak-6)">عروض حصرية ووصول مبكر للمجموعات الجديدة.</p>
            <form style="display:flex;gap:var(--ak-2);flex-wrap:wrap" onsubmit="event.preventDefault();alert('شكراً لاشتراكك!');">
                <input type="email" class="ak-input" style="flex:1;min-width:200px" placeholder="بريدك الإلكتروني" required>
                <button type="submit" class="ak-btn ak-btn--primary">اشترك</button>
            </form>
        </div>
    </div>
</section>
@endsection
