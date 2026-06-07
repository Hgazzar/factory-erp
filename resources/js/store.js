/**
 * Akwad Online Store — cart, catalog, checkout (Tailwind marketplace UI)
 */
const AkStore = {
    storageKey(slug) {
        return `akwad_store_cart_${slug}`;
    },
    readCart(slug) {
        try {
            return JSON.parse(localStorage.getItem(this.storageKey(slug)) || '[]');
        } catch {
            return [];
        }
    },
    writeCart(slug, cart) {
        localStorage.setItem(this.storageKey(slug), JSON.stringify(cart));
        window.dispatchEvent(new CustomEvent('ak-store-cart-updated'));
    },
    addToCart(slug, product, qty = 1) {
        const cart = this.readCart(slug);
        const existing = cart.find((l) => l.id === product.id);
        if (existing) {
            existing.quantity = Number(existing.quantity) + qty;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                sale_price: product.sale_price,
                vat_percent: product.vat_percent || 0,
                image_url: product.image_url || null,
                quantity: qty,
            });
        }
        this.writeCart(slug, cart);
    },
    cartCount(slug) {
        return this.readCart(slug).reduce((s, l) => s + Number(l.quantity || 0), 0);
    },
    formatMoney(currency, amount) {
        return `${Number(amount || 0).toFixed(2)} ${currency}`;
    },
    async fetchQuote(apiBase, lines, couponCode = null) {
        const res = await fetch(`${apiBase}/quote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ lines, coupon_code: couponCode || null }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'تعذر حساب السلة');
        return data;
    },
};

window.AkStore = AkStore;

/** إضافة للسلة — لا تعتمد على Alpine $root (في v3 يشير $root لعنصر DOM وليس البيانات). */
window.akStoreQuickAdd = function akStoreQuickAdd(product, qty = 1, toastMessage) {
    const slug = document.body.dataset.storeSlug || '';
    if (!slug || !product?.id) {
        console.warn('akStoreQuickAdd: slug or product missing');
        return;
    }
    AkStore.addToCart(slug, product, Number(qty) || 1);
    window.dispatchEvent(new CustomEvent('ak-store-cart-open', {
        detail: { toast: toastMessage || 'تمت الإضافة للسلة ✅' },
    }));
};

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
        mobileSearchOpen: false,
        headerSearch: '',
        toastOpen: false,
        toastMessage: '',
        init() {
            this.refreshCart();
            window.addEventListener('ak-store-cart-updated', () => this.refreshCart());
            window.addEventListener('ak-store-cart-open', (e) => {
                this.refreshCart();
                this.openCart();
                const msg = e.detail?.toast;
                if (msg) this.showToast(msg);
            });
            if (window.location.hash === '#cart') {
                this.$nextTick(() => this.openCart());
            }
            window.addEventListener('ak-store-search', (e) => {
                this.headerSearch = e.detail?.q || '';
            });
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
        showToast(msg) {
            this.toastMessage = msg;
            this.toastOpen = true;
            setTimeout(() => { this.toastOpen = false; }, 2500);
        },
        updateQty(id, qty) {
            const cart = AkStore.readCart(this.slug);
            const line = cart.find((l) => l.id === id);
            if (!line) return;
            line.quantity = Math.max(0, Number(qty));
            AkStore.writeCart(this.slug, cart.filter((l) => l.quantity > 0));
        },
        removeLine(id) {
            AkStore.writeCart(this.slug, AkStore.readCart(this.slug).filter((l) => l.id !== id));
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
                this.quote = await AkStore.fetchQuote(this.apiBase, this.quoteLinesPayload(), couponCode);
            } catch (e) {
                console.warn(e);
            } finally {
                this.quoteLoading = false;
            }
        },
        formatMoney(amount) {
            return AkStore.formatMoney(this.currency, amount);
        },
        productUrl(id) {
            return `${this.routes.home.replace(/\/$/, '')}/p/${id}`;
        },
        applyHeaderSearch() {
            window.dispatchEvent(new CustomEvent('ak-store-search', { detail: { q: this.headerSearch } }));
            const section = document.getElementById('productsSection');
            if (section) section.scrollIntoView({ behavior: 'smooth' });
        },
    };
}

function akHomeCatalog(config) {
    return {
        products: config.products || [],
        categoryId: config.initialCategory || '',
        searchQuery: config.initialQuery || '',
        featuredOnly: config.featuredOnly || false,
        currency: config.currency || '',
        init() {
            window.addEventListener('ak-store-search', (e) => {
                this.searchQuery = e.detail?.q || '';
            });
        },
        isVisible(product) {
            if (this.featuredOnly && !product.is_featured && !(product.discount_percent > 0)) {
                return false;
            }
            if (this.categoryId && String(product.category_id) !== String(this.categoryId)) {
                return false;
            }
            const q = this.searchQuery.trim();
            if (q && !product.name.includes(q)) {
                return false;
            }
            return true;
        },
        hasVisible() {
            return this.products.some((p) => this.isVisible(p));
        },
        setCategory(id) {
            this.categoryId = id;
        },
    };
}

function akCheckoutPage(config) {
    return {
        apiBase: config.apiBase,
        currency: config.currency,
        routes: config.routes,
        slug: config.slug,
        paymentMethods: config.paymentMethods || [],
        paymentSandbox: config.paymentSandbox !== false,
        paymentReceiptFile: null,
        cartLines: [],
        quote: null,
        quoteLoading: false,
        submitting: false,
        form: {
            customer_name: '',
            customer_phone: '',
            customer_address: '',
            coupon_code: '',
            payment_method: (config.paymentMethods && config.paymentMethods[0]) ? config.paymentMethods[0].key : 'cod',
        },
        couponMessage: '',
        couponError: '',
        init() {
            this.cartLines = AkStore.readCart(this.slug);
            if (!this.cartLines.length) {
                window.location.href = this.routes.home;
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
                this.quote = await AkStore.fetchQuote(this.apiBase, this.quoteLinesPayload(), couponCode);
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
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
            if (this.selectedMethodRequiresReceipt() && !this.paymentReceiptFile) {
                alert('يرجى رفع صورة إيصال التحويل.');
                return;
            }

            this.submitting = true;
            try {
                const headers = {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                };

                let res;
                if (this.paymentReceiptFile) {
                    const body = new FormData();
                    body.append('customer_name', this.form.customer_name);
                    body.append('customer_phone', this.form.customer_phone);
                    body.append('customer_address', this.form.customer_address);
                    body.append('payment_method', this.form.payment_method);
                    if (this.form.coupon_code) body.append('coupon_code', this.form.coupon_code);
                    body.append('payment_receipt', this.paymentReceiptFile);
                    this.quoteLinesPayload().forEach((line, i) => {
                        body.append(`lines[${i}][pos_product_id]`, line.pos_product_id);
                        body.append(`lines[${i}][quantity]`, line.quantity);
                    });
                    res = await fetch(`${this.apiBase}/checkout`, { method: 'POST', headers, body });
                } else {
                    res = await fetch(`${this.apiBase}/checkout`, {
                        method: 'POST',
                        headers: { ...headers, 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            ...this.form,
                            lines: this.quoteLinesPayload(),
                        }),
                    });
                }

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
        selectedMethodRequiresReceipt() {
            const m = this.paymentMethods.find((x) => x.key === this.form.payment_method);
            return !!(m && m.requires_receipt);
        },
        onReceiptSelected(event) {
            this.paymentReceiptFile = event.target.files?.[0] || null;
        },
    };
}

window.akStoreShell = akStoreShell;
window.akHomeCatalog = akHomeCatalog;
window.akCheckoutPage = akCheckoutPage;

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
