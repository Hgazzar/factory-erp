@extends('layouts.store')

@section('title', $storeName.' — تسوق أونلاين')

@section('content')
@php
    $heroTitle = $storeSettings->hero_title ?: 'اكتشف أفضل المنتجات';
    $heroSubtitle = $storeSettings->hero_subtitle ?: 'تسوق من أفضل الماركات واحصل على منتجات أصلية مع توصيل سريع لجميع المناطق';
    $heroOffer = $storeSettings->hero_offer_text ?: 'خصومات تصل إلى 50%';
@endphp

<section class="hero-gradient relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 right-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-10 w-96 h-96 bg-orange-400 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 py-16 md:py-24 relative z-10">
        <div class="text-center">
            @if($heroOffer)
                <span class="inline-block px-4 py-1.5 bg-white/10 rounded-full text-white text-sm mb-6 backdrop-blur-sm border border-white/20">
                    🔥 {{ $heroOffer }}
                </span>
            @endif
            <h2 class="text-4xl md:text-6xl font-black text-white mb-4 leading-tight">
                {!! nl2br(e($heroTitle)) !!}
            </h2>
            <p class="text-gray-300 text-lg mb-8 max-w-2xl mx-auto">{{ $heroSubtitle }}</p>
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="#productsSection" class="px-8 py-3.5 bg-store-gradient text-white rounded-xl font-bold hover-shadow-store transition-all hover:-translate-y-1">
                    <i class="fas fa-shopping-bag ml-2"></i>تسوق الآن
                </a>
                @if(!empty($routes['offers']))
                    <a href="{{ $routes['offers'] }}" class="px-8 py-3.5 bg-white/10 text-white rounded-xl font-bold hover:bg-white/20 transition-all backdrop-blur-sm border border-white/20">
                        <i class="fas fa-tags ml-2"></i>شاهد العروض
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-gray-50 to-transparent"></div>
</section>

<section class="max-w-7xl mx-auto px-4 -mt-4 relative z-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center hover:shadow-md transition-shadow">
            <i class="fas fa-truck-fast text-2xl text-store-primary mb-2"></i>
            <h3 class="font-bold text-sm text-gray-800">توصيل سريع</h3>
            <p class="text-xs text-gray-400">خلال 24 ساعة</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center hover:shadow-md transition-shadow">
            <i class="fas fa-shield-halved text-2xl text-orange-500 mb-2"></i>
            <h3 class="font-bold text-sm text-gray-800">ضمان الجودة</h3>
            <p class="text-xs text-gray-400">منتجات أصلية 100%</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center hover:shadow-md transition-shadow">
            <i class="fas fa-rotate-left text-2xl text-green-500 mb-2"></i>
            <h3 class="font-bold text-sm text-gray-800">إرجاع مجاني</h3>
            <p class="text-xs text-gray-400">خلال 30 يوم</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center hover:shadow-md transition-shadow">
            <i class="fas fa-headset text-2xl text-blue-500 mb-2"></i>
            <h3 class="font-bold text-sm text-gray-800">دعم 24/7</h3>
            <p class="text-xs text-gray-400">خدمة عملاء متميزة</p>
        </div>
    </div>
</section>

<section id="productsSection" class="max-w-7xl mx-auto px-4 py-12"
         x-data="akHomeCatalog(@js([
             'products' => $allProducts,
             'categories' => $categories,
             'currency' => $currencyCode,
             'initialCategory' => request('category_id'),
             'initialQuery' => request('q'),
             'featuredOnly' => (bool) request('featured'),
         ]))"
         x-init="init()">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black text-gray-800">منتجاتنا <span class="gradient-text">المميزة</span></h2>
            <p class="text-gray-400 mt-1">اختر من بين مجموعة واسعة من المنتجات</p>
        </div>
    </div>

    @if(count($categories))
    <div class="flex flex-wrap gap-3 mb-8">
        <button type="button" class="filter-btn px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
                :class="!categoryId && 'active'" @click="setCategory('')">
            <i class="fas fa-th ml-1"></i> الكل
        </button>
        @foreach($categories as $cat)
        <button type="button" class="filter-btn px-5 py-2.5 bg-white rounded-xl font-bold text-sm text-gray-600 hover:bg-gray-100 transition-all border border-gray-200"
                :class="categoryId == '{{ $cat['id'] }}' && 'active'"
                @click="setCategory('{{ $cat['id'] }}')">
            {{ $cat['name'] }}
        </button>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($allProducts as $product)
            <div x-show="isVisible(@js($product))">
                @include('store.partials.product-card', ['product' => $product, 'currencyCode' => $currencyCode])
            </div>
        @empty
            <p class="col-span-full text-center text-gray-400 py-16 text-lg">
                <i class="fas fa-search text-4xl mb-4 block"></i>
                لا توجد منتجات منشورة بعد
            </p>
        @endforelse
    </div>

    <div class="text-center text-gray-400 py-16 text-lg" x-show="!hasVisible()" x-cloak>
        <i class="fas fa-search text-4xl mb-4 block"></i>
        لم يتم العثور على منتجات
    </div>
</section>
@endsection
