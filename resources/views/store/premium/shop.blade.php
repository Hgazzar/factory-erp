@extends('layouts.store-premium')

@section('title', 'المتجر — '.$storeName)

@section('content')
<div class="ak-container ak-section"
     x-data="akShopCatalog(@js([
         'slug' => $tenantSlug,
         'apiBase' => $apiBase,
         'currency' => $currencyCode,
         'routes' => $routes,
         'initialProducts' => $initialProducts,
         'categories' => $categories,
         'initialTotal' => count($initialProducts),
     ]))"
     x-init="init()">

    <div class="ak-toolbar">
        <div>
            <p class="ak-eyebrow">تسوق</p>
            <h1 class="ak-section-title">المتجر</h1>
        </div>
        <p class="ak-caption" x-text="pagination.total ? pagination.total + ' منتج' : ''"></p>
    </div>

    <div class="ak-shop-layout">
        <aside class="ak-filters">
            <div class="max-lg:hidden">
                <p class="ak-eyebrow" style="margin-bottom:var(--ak-4)">التصنيف</p>
                <div style="display:flex;flex-direction:column;gap:var(--ak-2);align-items:flex-start">
                    <button type="button" class="ak-chip" :class="!filters.category_id && 'is-active'" @click="filters.category_id=''; applyFilters()">الكل</button>
                    @foreach($categories as $cat)
                    <button type="button" class="ak-chip"
                            :class="filters.category_id == '{{ $cat['id'] }}' && 'is-active'"
                            @click="filters.category_id='{{ $cat['id'] }}'; applyFilters()">{{ $cat['name'] }}</button>
                    @endforeach
                </div>
            </div>
            <div class="lg:hidden" style="display:flex;flex-wrap:wrap;gap:var(--ak-2);width:100%">
                <button type="button" class="ak-chip" :class="!filters.category_id && 'is-active'" @click="filters.category_id=''; applyFilters()">الكل</button>
                @foreach($categories as $cat)
                <button type="button" class="ak-chip"
                        :class="filters.category_id == '{{ $cat['id'] }}' && 'is-active'"
                        @click="filters.category_id='{{ $cat['id'] }}'; applyFilters()">{{ $cat['name'] }}</button>
                @endforeach
            </div>
        </aside>

        <div>
            <div style="display:flex;flex-wrap:wrap;gap:var(--ak-3);margin-bottom:var(--ak-8);align-items:center">
                <div class="ak-search-wrap" style="flex:1;min-width:200px;max-width:100%">
                    <span class="ak-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </span>
                    <input type="search" class="ak-input" placeholder="ابحث..." x-model="filters.q" @keydown.enter="applyFilters()">
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:var(--ak-2)">
                    <template x-for="opt in sortOptions" :key="opt.v">
                        <button type="button" class="ak-chip" :class="filters.sort === opt.v && 'is-active'"
                                @click="filters.sort = opt.v; applyFilters()" x-text="opt.l"></button>
                    </template>
                </div>
            </div>

            <div id="shop-ssr-grid" class="ak-grid" x-show="!products.length && !loading" x-ignore>
                @forelse($initialProducts as $product)
                    @include('store.premium.partials.product-card', ['product' => $product, 'tenantSlug' => $tenantSlug])
                @empty
                    <p class="ak-body-lg" style="grid-column:1/-1;text-align:center;padding:var(--ak-16) 0">لا توجد منتجات منشورة.</p>
                @endforelse
            </div>

            <div class="ak-grid" x-show="products.length" x-cloak>
                <template x-for="p in products" :key="p.id">
                    <article class="ak-card">
                        <div class="ak-card__media">
                            <a :href="productUrl(p.id)"><img :src="p.image_url" :alt="p.name" loading="lazy"></a>
                            <span class="ak-card__badge" x-show="p.discount_percent > 0"><span x-text="'−' + p.discount_percent + '%'"></span></span>
                            <div class="ak-card__actions">
                                <button type="button" class="ak-card__action ak-card__action--gold" @click="$root.addProduct(p)" :disabled="!p.in_stock">أضف للسلة</button>
                                <a :href="productUrl(p.id)" class="ak-card__action" style="text-decoration:none;text-align:center">عرض</a>
                            </div>
                        </div>
                        <div class="ak-card__body">
                            <a :href="productUrl(p.id)" class="ak-card__title" x-text="p.name"></a>
                            <div class="ak-card__price"><span class="ak-price" x-text="formatMoney(p.sale_price)"></span></div>
                        </div>
                    </article>
                </template>
            </div>

            <div class="ak-grid" x-show="loading">
                @for($i = 0; $i < 8; $i++)
                    <div class="ak-skeleton" style="aspect-ratio:3/4"></div>
                @endfor
            </div>

            <div x-show="!loading && !products.length && !document.getElementById('shop-ssr-grid')?.children.length" style="text-align:center;padding:var(--ak-16)">
                <p class="ak-body-lg">لا توجد نتائج</p>
            </div>

            <div style="text-align:center;margin-top:var(--ak-10)" x-show="pagination.has_more">
                <button type="button" class="ak-btn ak-btn--ghost" @click="loadMore()" :disabled="loading">تحميل المزيد</button>
            </div>
        </div>
    </div>
</div>
@endsection
