@props(['product', 'currencyCode' => ''])

@php
    $url = route('store.portal.product', ['tenant_slug' => request()->route('tenant_slug'), 'product' => $product['id']]);
    $cartLine = [
        'id' => $product['id'],
        'name' => $product['name'],
        'sale_price' => $product['sale_price'],
        'vat_percent' => $product['vat_percent'] ?? 0,
        'image_url' => $product['image_url'] ?? null,
    ];
    $badge = null;
    if (!empty($product['is_bestseller'])) {
        $badge = 'الأكثر مبيعاً';
    } elseif (!empty($product['is_trending'])) {
        $badge = 'رائج';
    } elseif (!empty($product['is_featured'])) {
        $badge = 'مميز';
    } elseif (($product['discount_percent'] ?? 0) > 0) {
        $badge = 'خصم '.$product['discount_percent'].'%';
    }
@endphp

<div class="product-card bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover fade-in">
    <div class="relative overflow-hidden">
        <a href="{{ $url }}">
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="product-img w-full h-56 object-cover" loading="lazy">
        </a>
        @if($badge)
            <span class="absolute top-3 right-3 px-3 py-1 bg-store-gradient text-white text-xs font-bold rounded-full shadow-lg">{{ $badge }}</span>
        @endif
        @if(!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['sale_price'])
            <span class="absolute top-3 left-3 px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">−{{ $product['discount_percent'] }}%</span>
        @endif
        <a href="{{ $url }}" class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-all flex items-center justify-center opacity-0 hover:opacity-100">
            <span class="bg-white px-4 py-2 rounded-full font-bold text-sm shadow-lg">عرض التفاصيل</span>
        </a>
    </div>
    <div class="p-5">
        @if(($product['review_count'] ?? 0) > 0)
            <div class="flex items-center gap-1 mb-2 text-xs text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star{{ $i <= floor($product['avg_rating']) ? '' : ($i - $product['avg_rating'] < 1 ? '-half-alt' : ' text-gray-300') }}"></i>
                @endfor
                <span class="text-gray-400 mr-1">({{ $product['review_count'] }})</span>
            </div>
        @endif
        <a href="{{ $url }}" class="font-bold text-gray-800 mb-2 text-sm leading-snug block hover:text-store-primary transition-colors">{{ $product['name'] }}</a>
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xl font-black gradient-text">{{ number_format($product['sale_price'], 2) }} {{ $currencyCode }}</span>
                @if(!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['sale_price'])
                    <span class="text-xs text-gray-400 line-through mr-2">{{ number_format($product['compare_at_price'], 2) }}</span>
                @endif
            </div>
            @if($product['in_stock'])
                <button type="button"
                        @click="akStoreQuickAdd(@js($cartLine), 1, 'تمت الإضافة للسلة ✅')"
                        class="w-11 h-11 rounded-xl bg-store-gradient text-white flex items-center justify-center hover-shadow-store transition-all hover:-translate-y-0.5 active:translate-y-0"
                        aria-label="إضافة {{ $product['name'] }} للسلة">
                    <i class="fas fa-plus"></i>
                </button>
            @else
                <span class="text-xs text-gray-400 font-bold">نفد</span>
            @endif
        </div>
    </div>
</div>
