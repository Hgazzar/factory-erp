@extends('layouts.store-portal')

@section('title', $storeName.' — متجر أونلاين')

@section('content')
<div x-data="storeHome(@json($products), @json($categories), @json($apiBase))" x-init="init()">
    <div class="my-3">
        <input type="search" class="store-search w-100" placeholder="ابحث عن منتج…" x-model="searchQuery" @input.debounce.350ms="filterProducts()">
    </div>
    <section class="store-hero">
        <div class="small opacity-90 mb-1" x-show="false">{{-- placeholder --}}</div>
        @if($storeSettings->hero_offer_text)
            <div class="badge bg-white text-dark mb-2">{{ $storeSettings->hero_offer_text }}</div>
        @endif
        <h1 class="h3 fw-bold mb-2">{{ $storeSettings->hero_title ?: 'تسوق بكل سهولة' }}</h1>
        <p class="mb-0 opacity-90">{{ $storeSettings->hero_subtitle ?: 'توصيل سريع — دفع عند الاستلام' }}</p>
    </section>

    <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="flex-wrap:nowrap">
        <button type="button" class="store-chip" :class="!categoryId ? 'is-active' : ''" @click="setCategory(null)">الكل</button>
        <template x-for="cat in categories" :key="cat.id">
            <button type="button" class="store-chip" :class="categoryId === cat.id ? 'is-active' : ''"
                    @click="setCategory(cat.id)" x-text="cat.name"></button>
        </template>
    </div>

    <div class="store-grid">
        <template x-for="product in visibleProducts" :key="product.id">
            <article class="store-product">
                <div class="store-product-thumb" x-text="product.initial || '?'"></div>
                <div class="store-product-body">
                    <h2 class="h6 fw-bold mb-1" x-text="product.name"></h2>
                    <p class="small text-muted mb-2" x-text="product.category_name || ''"></p>
                    <div class="fw-bold text-primary mb-2 mt-auto" x-text="$root.formatMoney(lineTotal(product, 1))"></div>
                    <button type="button" class="store-btn" @click="$root.addToCart(product)" :disabled="product.current_quantity <= 0">
                        <span x-text="product.current_quantity > 0 ? 'أضف للسلة' : 'نفد المخزون'"></span>
                    </button>
                </div>
            </article>
        </template>
    </div>

    <p x-show="visibleProducts.length === 0" class="text-center text-muted py-5">لا توجد منتجات منشورة حالياً.</p>
</div>
@endsection

@push('scripts')
<script>
function storeHome(initialProducts, categories, apiBase) {
    return {
        allProducts: initialProducts,
        visibleProducts: initialProducts,
        categories,
        apiBase,
        categoryId: null,
        searchQuery: '',
        init() {},
        lineTotal(p, qty) {
            const sub = Number(p.sale_price) * qty;
            const vat = sub * (Number(p.vat_percent || 0) / 100);
            return sub + vat;
        },
        setCategory(id) {
            this.categoryId = id;
            this.filterProducts();
        },
        async filterProducts() {
            const params = new URLSearchParams();
            if (this.searchQuery.trim()) params.set('q', this.searchQuery.trim());
            if (this.categoryId) params.set('category_id', this.categoryId);
            const res = await fetch(`${this.apiBase}/products?${params}`, { headers: { Accept: 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                this.visibleProducts = data.products || [];
            }
        },
    };
}
</script>
@endpush
