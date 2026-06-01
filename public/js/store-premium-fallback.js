function akStoreShell(config) {
    return {
        slug: config.slug,
        apiBase: config.apiBase,
        currency: config.currency,
        routes: config.routes,
        cartOpen: false,
        cartLines: [],
        quote: null,
        quoteLoading: false,
        cartCount: 0,
        wishCount: 0,
        init() {
            this.refreshCart();
            window.addEventListener('ak-store-cart-updated', () => this.refreshCart());
            window.addEventListener('ak-store-wish-updated', () => this.refreshWishCount());
            this.refreshWishCount();
        },
        refreshWishCount() {
            this.wishCount = AkStore.readWishlist(this.slug).length;
        },
        refreshCart() {
            this.cartLines = AkStore.readCart(this.slug);
            this.cartCount = AkStore.cartCount(this.slug);
            if (this.cartLines.length) this.loadQuote();
            else this.quote = null;
        },
        openCart() {
            this.cartOpen = true;
            document.body.style.overflow = 'hidden';
            this.loadQuote();
        },
        closeCart() {
            this.cartOpen = false;
            document.body.style.overflow = '';
        },
        addProduct(product, qty = 1) {
            AkStore.addToCart(this.slug, product, qty);
            this.openCart();
        },
        updateQty(id, qty) {
            const cart = AkStore.readCart(this.slug);
            const line = cart.find((l) => l.id === id);
            if (!line) return;
            line.quantity = Math.max(0.01, Number(qty));
            AkStore.writeCart(
                this.slug,
                cart.filter((l) => l.quantity > 0),
            );
        },
        removeLine(id) {
            AkStore.writeCart(
                this.slug,
                AkStore.readCart(this.slug).filter((l) => l.id !== id),
            );
        },
        quoteLinesPayload() {
            return this.cartLines.map((l) => ({
                pos_product_id: l.id,
                quantity: Number(l.quantity),
            }));
        },
        async loadQuote(couponCode = null) {
            if (!this.cartLines.length) {
                this.quote = null;
                return;
            }
            this.quoteLoading = true;
            try {
                this.quote = await AkStore.fetchQuote(
                    this.apiBase,
                    this.quoteLinesPayload(),
                    couponCode,
                );
            } catch (e) {
                console.warn(e);
            } finally {
                this.quoteLoading = false;
            }
        },
        formatMoney(amount) {
            return AkStore.formatMoney(this.currency, amount);
        },
        toggleWish(productId) {
            AkStore.toggleWishlist(this.slug, productId);
            this.refreshWishCount();
        },
        isWishlisted(productId) {
            return AkStore.isWishlisted(this.slug, productId);
        },
        productUrl(id) {
            return `${this.routes.home.replace(/\/$/, '')}/p/${id}`;
        },
    };
}

function akHeroSlider(count) {
    return {
        active: 0,
        total: count,
        touchStartX: 0,
        timer: null,
        init() {
            this.startAuto();
        },
        startAuto() {
            clearInterval(this.timer);
            this.timer = setInterval(() => this.next(), 6000);
        },
        go(i) {
            this.active = i;
            this.startAuto();
        },
        next() {
            this.active = (this.active + 1) % this.total;
        },
        prev() {
            this.active = (this.active - 1 + this.total) % this.total;
        },
        onTouchStart(e) {
            this.touchStartX = e.changedTouches[0].screenX;
        },
        onTouchEnd(e) {
            const diff = e.changedTouches[0].screenX - this.touchStartX;
            if (Math.abs(diff) < 40) return;
            if (diff > 0) this.prev();
            else this.next();
            this.startAuto();
        },
    };
}

function akShopCatalog(config) {
    return {
        apiBase: config.apiBase,
        currency: config.currency,
        products: config.initialProducts || [],
        categories: config.categories || [],
        filters: {
            q: new URLSearchParams(window.location.search).get('q') || '',
            category_id: new URLSearchParams(window.location.search).get('category_id') || '',
            sort: 'newest',
            min_price: '',
            max_price: '',
            page: 1,
        },
        pagination: { has_more: false, current_page: 1, last_page: 1 },
        loading: false,
        init() {
            if (this.products.length) {
                document.getElementById('shop-ssr-grid')?.remove();
            } else {
                this.fetchProducts();
            }
        },
        formatMoney(amount) {
            return AkStore.formatMoney(this.currency, amount);
        },
        productUrl(id) {
            const base = config.routes?.home?.replace(/\/$/, '') || '';
            return `${base}/p/${id}`;
        },
        async fetchProducts(append = false) {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.q) params.set('q', this.filters.q);
            if (this.filters.category_id) params.set('category_id', this.filters.category_id);
            if (this.filters.sort) params.set('sort', this.filters.sort);
            params.set('page', String(this.filters.page));
            try {
                const res = await fetch(`${this.apiBase}/products?${params}`);
                const data = await res.json();
                this.products = append ? [...this.products, ...data.products] : data.products;
                this.pagination = data.pagination;
                this.currency = data.currency || this.currency;
            } finally {
                this.loading = false;
            }
        },
        applyFilters() {
            this.filters.page = 1;
            this.fetchProducts();
        },
        loadMore() {
            if (!this.pagination.has_more || this.loading) return;
            this.filters.page += 1;
            this.fetchProducts(true);
        },
    };
}

function akCheckoutPage(config) {
    return {
        apiBase: config.apiBase,
        currency: config.currency,
        routes: config.routes,
        slug: config.slug,
        cartLines: [],
        quote: null,
        quoteLoading: false,
        submitting: false,
        form: {
            customer_name: '',
            customer_phone: '',
            customer_address: '',
            coupon_code: '',
        },
        couponMessage: '',
        couponError: '',
        init() {
            this.cartLines = AkStore.readCart(this.slug);
            if (!this.cartLines.length) {
                window.location.href = this.routes.shop;
            }
            this.loadQuote();
            window.addEventListener('ak-store-cart-updated', () => {
                this.cartLines = AkStore.readCart(this.slug);
                this.loadQuote();
            });
        },
        quoteLinesPayload() {
            return this.cartLines.map((l) => ({
                pos_product_id: l.id,
                quantity: Number(l.quantity),
            }));
        },
        async loadQuote(couponCode = null) {
            if (!this.cartLines.length) return;
            this.quoteLoading = true;
            try {
                this.quote = await AkStore.fetchQuote(
                    this.apiBase,
                    this.quoteLinesPayload(),
                    couponCode,
                );
                this.currency = this.quote.currency || this.currency;
            } catch (e) {
                console.warn(e);
            } finally {
                this.quoteLoading = false;
            }
        },
        formatMoney(amount) {
            return AkStore.formatMoney(this.currency, amount);
        },
        async applyCoupon() {
            this.couponError = '';
            this.couponMessage = '';
            if (!this.form.coupon_code) return;
            const subtotal = this.quote?.subtotal ?? 0;
            try {
                const res = await fetch(`${this.apiBase}/coupon`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ code: this.form.coupon_code, subtotal }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message);
                this.couponMessage = data.message;
                await this.loadQuote(this.form.coupon_code);
            } catch (e) {
                this.couponError = e.message || 'كود غير صالح';
            }
        },
        async submitOrder() {
            this.submitting = true;
            try {
                const res = await fetch(`${this.apiBase}/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        lines: this.quoteLinesPayload(),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'تعذر إتمام الطلب');
                AkStore.writeCart(this.slug, []);
                window.location.href = data.success_url;
            } catch (e) {
                alert(e.message || 'حدث خطأ');
            } finally {
                this.submitting = false;
            }
        },
    };
}

window.akStoreShell = akStoreShell;
window.akHeroSlider = akHeroSlider;
window.akShopCatalog = akShopCatalog;
window.akCheckoutPage = akCheckoutPage;
