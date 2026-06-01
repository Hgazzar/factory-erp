@props(['product', 'tenantSlug' => null])

@php
    $url = route('store.portal.product', ['tenant_slug' => $tenantSlug ?? request()->route('tenant_slug'), 'product' => $product['id']]);
    $hasDiscount = ($product['discount_percent'] ?? 0) > 0;
@endphp

<article class="ak-card" x-data>
    <div class="ak-card__media">
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" loading="lazy" width="600" height="800">
        </a>

        @if($hasDiscount)
            <span class="ak-card__badge"><span>−{{ $product['discount_percent'] }}%</span></span>
        @endif

        <button type="button" class="ak-card__wish"
                :class="isWishlisted({{ $product['id'] }}) && 'is-active'"
                @click.prevent="toggleWish({{ $product['id'] }})"
                aria-label="أضف للمفضلة">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
        </button>

        <div class="ak-card__actions">
            <button type="button" class="ak-card__action ak-card__action--gold"
                    @click="$root.addProduct(@js($product))"
                    @if(empty($product['in_stock'])) disabled @endif>
                أضف للسلة
            </button>
            <a href="{{ $url }}" class="ak-card__action" style="text-align:center;text-decoration:none">عرض</a>
        </div>
    </div>

    <div class="ak-card__body">
        <a href="{{ $url }}" class="ak-card__title">{{ $product['name'] }}</a>
        <div class="ak-card__price">
            <span class="ak-price">{{ number_format($product['sale_price'], 2) }} {{ $currencyCode ?? '' }}</span>
            @if(!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['sale_price'])
                <span class="ak-price--old">{{ number_format($product['compare_at_price'], 2) }}</span>
            @endif
        </div>
        @if(($product['review_count'] ?? 0) > 0)
            <div class="ak-card__rating">★ {{ $product['avg_rating'] }} · {{ $product['review_count'] }} تقييم</div>
        @endif
    </div>
</article>
