@extends('layouts.store')

@section('title', ($product['seo_title'] ?? $product['name']).' — '.$storeName)

@section('content')
@php
    $p = $product;
    $cartLine = [
        'id' => $p['id'],
        'name' => $p['name'],
        'sale_price' => $p['sale_price'],
        'vat_percent' => $p['vat_percent'] ?? 0,
        'image_url' => $p['image_url'] ?? null,
    ];
@endphp
<div class="max-w-7xl mx-auto px-4 py-8" x-data="{ qty: 1 }">
    <a href="{{ $routes['home'] }}" class="flex items-center gap-2 text-gray-500 hover:text-store-primary transition-colors mb-6">
        <i class="fas fa-arrow-right"></i>
        <span class="font-bold">العودة للمتجر</span>
    </a>

    <div class="grid md:grid-cols-2 gap-10 fade-in">
        <div class="relative rounded-2xl overflow-hidden bg-white shadow-sm border border-gray-100">
            <img src="{{ $p['gallery'][0] ?? $p['image_url'] }}" alt="{{ $p['name'] }}" class="w-full h-[400px] md:h-[500px] object-cover">
            @if($p['discount_percent'] > 0)
                <span class="absolute top-4 left-4 px-4 py-2 bg-green-500 text-white text-sm font-bold rounded-full shadow-lg">−{{ $p['discount_percent'] }}%</span>
            @endif
        </div>

        <div>
            @if($p['category_name'])
                <span class="text-sm text-store-primary font-bold mb-2 block">{{ $p['category_name'] }}</span>
            @endif
            <h2 class="text-3xl font-black text-gray-800 mb-4 leading-snug">{{ $p['name'] }}</h2>

            @if(($p['review_count'] ?? 0) > 0)
                <div class="flex items-center gap-3 mb-6 text-sm text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star{{ $i <= floor($p['avg_rating']) ? '' : '-half-alt text-gray-300' }}"></i>
                    @endfor
                    <span class="text-gray-500 font-bold">{{ $p['avg_rating'] }}</span>
                    <span class="text-gray-400">({{ $p['review_count'] }} تقييم)</span>
                </div>
            @endif

            <p class="text-gray-500 leading-relaxed mb-6 whitespace-pre-wrap">{{ $p['description'] ?: 'منتج من متجرنا الإلكتروني.' }}</p>

            <div class="flex items-end gap-4 mb-6">
                <span class="text-4xl font-black gradient-text">{{ number_format($p['sale_price'], 2) }} {{ $currencyCode }}</span>
                @if(!empty($p['compare_at_price']) && $p['compare_at_price'] > $p['sale_price'])
                    <span class="text-lg text-gray-400 line-through">{{ number_format($p['compare_at_price'], 2) }}</span>
                @endif
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-3">
                <div class="flex items-center gap-3 text-sm text-gray-600"><i class="fas fa-truck-fast text-store-primary w-5"></i> توصيل مجاني</div>
                <div class="flex items-center gap-3 text-sm text-gray-600"><i class="fas fa-shield-halved text-green-500 w-5"></i> ضمان الجودة</div>
                <div class="flex items-center gap-3 text-sm text-gray-600"><i class="fas fa-rotate-left text-orange-500 w-5"></i> إرجاع مجاني</div>
            </div>

            <p class="text-sm mb-4 {{ $p['in_stock'] ? 'text-green-600' : 'text-red-500' }} font-bold">
                @if($p['in_stock']) ● متوفر @else ● نفد المخزون @endif
            </p>

            @if($p['in_stock'])
            <div class="flex gap-3 mb-8">
                <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-4 py-3 hover:bg-gray-100 text-lg font-bold text-gray-500">-</button>
                    <span class="px-4 py-3 font-bold text-gray-800 min-w-[50px] text-center" x-text="qty"></span>
                    <button type="button" @click="qty++" class="px-4 py-3 hover:bg-gray-100 text-lg font-bold text-gray-500">+</button>
                </div>
                <button type="button" @click="akStoreQuickAdd(@js($cartLine), qty, 'تمت الإضافة للسلة بنجاح! 🎉')"
                        class="flex-1 py-4 bg-store-gradient text-white rounded-xl font-bold text-lg hover-shadow-store transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i class="fas fa-shopping-bag"></i>
                    <span>أضف للسلة</span>
                </button>
            </div>
            @endif
        </div>
    </div>

    @if(!empty($p['related']))
    <section class="mt-16">
        <h3 class="text-2xl font-black text-gray-800 mb-8">منتجات <span class="gradient-text">ذات صلة</span></h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($p['related'] as $rel)
                @include('store.partials.product-card', ['product' => $rel, 'currencyCode' => $currencyCode])
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
