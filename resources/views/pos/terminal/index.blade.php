@extends('layouts.pos-terminal')

@section('title', 'كاشير نقاط البيع — '.config('app.name'))

@push('styles')
<style>
    .pos-shell { min-height: 100vh; display: flex; flex-direction: column; }
    .pos-topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: .75rem 1rem; display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; }
    .pos-main { flex: 1; display: grid; grid-template-columns: minmax(320px, 420px) 1fr; min-height: 0; }
    @media (max-width: 1024px) { .pos-main { grid-template-columns: 1fr; grid-template-rows: auto 1fr; } }
    .pos-cart-panel { background: #111827; border-left: 1px solid #334155; display: flex; flex-direction: column; min-height: 0; }
    .pos-products-panel { background: #0f172a; display: flex; flex-direction: column; min-height: 0; }
    .pos-search { background: #1e293b; border: 1px solid #475569; color: #f8fafc; border-radius: .75rem; padding: .85rem 1rem; width: 100%; font-size: 1rem; }
    .pos-search:focus { outline: 2px solid #38bdf8; border-color: #38bdf8; }
    .pos-chip { border: 1px solid #475569; background: #1e293b; color: #cbd5e1; border-radius: 999px; padding: .35rem .85rem; font-size: .85rem; cursor: pointer; white-space: nowrap; }
    .pos-chip.is-active { background: #0284c7; border-color: #0284c7; color: #fff; }
    .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .75rem; padding: 1rem; overflow-y: auto; }
    .pos-product-card { background: #1e293b; border: 1px solid #334155; border-radius: .85rem; padding: .75rem; cursor: pointer; transition: transform .12s, border-color .12s; }
    .pos-product-card:hover { transform: translateY(-2px); border-color: #38bdf8; }
    .pos-product-thumb { width: 100%; aspect-ratio: 1; border-radius: .65rem; background: linear-gradient(135deg, #0ea5e9, #6366f1); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: .5rem; }
    .pos-cart-list { flex: 1; overflow-y: auto; padding: .75rem 1rem; }
    .pos-cart-row { display: grid; grid-template-columns: 1fr auto auto; gap: .5rem; align-items: center; padding: .65rem 0; border-bottom: 1px solid #1f2937; }
    .pos-qty-btn { width: 2rem; height: 2rem; border-radius: .5rem; border: 1px solid #475569; background: #1e293b; color: #f8fafc; font-weight: 700; }
    .pos-footer { background: #0b1220; border-top: 1px solid #334155; padding: 1rem; }
    .pos-checkout-btn { width: 100%; background: linear-gradient(135deg, #059669, #10b981); color: #fff; border: none; border-radius: .85rem; padding: 1rem; font-size: 1.15rem; font-weight: 800; cursor: pointer; }
    .pos-checkout-btn:disabled { opacity: .45; cursor: not-allowed; }
    .pos-pay-pill { border: 1px solid #475569; background: #1e293b; color: #e2e8f0; border-radius: .65rem; padding: .55rem .75rem; font-size: .9rem; cursor: pointer; flex: 1; text-align: center; }
    .pos-pay-pill.is-active { background: #0369a1; border-color: #0284c7; color: #fff; font-weight: 700; }
    .pos-modal-backdrop { position: fixed; inset: 0; background: rgba(2,6,23,.75); z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .pos-modal { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; width: min(420px, 100%); padding: 1.25rem; }
</style>
@endpush

@section('content')
<div class="pos-shell" dir="rtl" x-data="posTerminal()" x-init="init()" x-cloak>
    <header class="pos-topbar no-print">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('pos.dashboard') }}" class="text-sky-300 text-sm font-semibold hover:text-sky-200">← لوحة POS</a>
            <h1 class="text-lg font-bold text-white m-0">كاشير سريع</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-slate-400 inline-flex items-center gap-1">
                <x-info field="pos.terminal_device" /> الجهاز
            </span>
            @forelse($devices as $device)
                <button type="button" class="pos-chip" :class="deviceId === {{ $device->id }} ? 'is-active' : ''"
                        @click="deviceId = {{ $device->id }}; syncSession()">{{ $device->name }}</button>
            @empty
                <span class="text-amber-400 text-xs">لا توجد أجهزة — أضف جهازاً من الإعدادات</span>
            @endforelse
            @canFeature(\App\Support\PosFeatureKeys::MULTI_WAREHOUSE)
                <span class="text-xs text-emerald-300 font-semibold">تعدد المخازن مفعّل</span>
            @endcanFeature
        </div>
    </header>

    <div class="pos-main">
        {{-- Cart (left in RTL layout) --}}
        <aside class="pos-cart-panel order-2 lg:order-1">
            <div class="p-3 border-b border-slate-700 no-print">
                <h2 class="text-base font-bold text-white m-0 inline-flex items-center gap-1">
                    <x-info field="pos.terminal_cart" /> سلة المشتريات
                </h2>
            </div>

            <div class="pos-cart-list">
                <template x-if="cart.length === 0">
                    <p class="text-slate-400 text-sm text-center py-8">امسح باركوداً أو اختر منتجاً لإضافته للسلة</p>
                </template>
                <template x-for="(line, index) in cart" :key="line.key">
                    <div class="pos-cart-row">
                        <div>
                            <div class="font-semibold text-white text-sm" x-text="line.name"></div>
                            <div class="text-xs text-slate-400" x-text="formatMoney(line.unit_price) + ' × ' + line.quantity"></div>
                            @canFeature(\App\Support\PosFeatureKeys::MANUAL_PRICE_OVERRIDE)
                                <input type="number" min="0" step="0.01" class="pos-search !py-1 !text-xs mt-1"
                                       x-model.number="line.unit_price" @change="recalcLine(line)">
                            @endcanFeature
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="pos-qty-btn" @click="changeQty(index, -1)">−</button>
                            <span class="w-8 text-center font-bold" x-text="line.quantity"></span>
                            <button type="button" class="pos-qty-btn" @click="changeQty(index, 1)">+</button>
                        </div>
                        <div class="text-start">
                            <div class="font-bold text-emerald-300 text-sm" x-text="formatMoney(line.line_total)"></div>
                            <button type="button" class="text-xs text-rose-400 mt-1" @click="removeLine(index)">حذف</button>
                        </div>
                    </div>
                </template>
            </div>

            <footer class="pos-footer no-print">
                <div class="space-y-1 text-sm mb-3">
                    <div class="flex justify-between"><span class="text-slate-400 inline-flex items-center gap-1"><x-info field="pos.terminal_subtotal" /> المجموع الفرعي</span><span x-text="formatMoney(totals.subtotal)"></span></div>
                    <div class="flex justify-between"><span class="text-slate-400 inline-flex items-center gap-1"><x-info field="pos.terminal_vat" /> الضريبة</span><span x-text="formatMoney(totals.vat)"></span></div>
                    <div class="flex justify-between text-lg font-extrabold text-white pt-1 border-t border-slate-700"><span class="inline-flex items-center gap-1"><x-info field="pos.terminal_total" /> الإجمالي</span><span x-text="formatMoney(totals.total)"></span></div>
                </div>

                <div class="flex gap-2 mb-3">
                    <button type="button" class="pos-pay-pill" :class="paymentMethod === 'cash' ? 'is-active' : ''" @click="paymentMethod = 'cash'">نقدي</button>
                    <button type="button" class="pos-pay-pill" :class="paymentMethod === 'card' ? 'is-active' : ''" @click="paymentMethod = 'card'">بطاقة</button>
                    <button type="button" class="pos-pay-pill" :class="paymentMethod === 'mixed' ? 'is-active' : ''" @click="openSplitModal()">مقسم</button>
                </div>

                <button type="button" class="pos-checkout-btn" :disabled="cart.length === 0 || checkoutLoading || !deviceId" @click="checkout()">
                    <span x-show="!checkoutLoading">إتمام الدفع</span>
                    <span x-show="checkoutLoading">جاري المعالجة…</span>
                </button>
                <p x-show="errorMessage" class="text-rose-400 text-xs mt-2 mb-0" x-text="errorMessage"></p>
            </footer>
        </aside>

        {{-- Products (right in RTL layout) --}}
        <section class="pos-products-panel order-1 lg:order-2">
            <div class="p-3 border-b border-slate-700 no-print space-y-3">
                <input type="text" class="pos-search" placeholder="امسح الباركود أو ابحث بالاسم / SKU…"
                       x-ref="scanner" x-model="searchQuery" @keydown.enter.prevent="handleScannerEnter()"
                       @input.debounce.350ms="loadProducts()">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button type="button" class="pos-chip" :class="!selectedCategoryId ? 'is-active' : ''" @click="selectCategory(null)">الكل</button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button type="button" class="pos-chip" :class="selectedCategoryId === cat.id ? 'is-active' : ''"
                                @click="selectCategory(cat.id)" x-text="cat.name"></button>
                    </template>
                </div>
            </div>

            <div class="pos-grid">
                <template x-for="product in products" :key="product.id">
                    <button type="button" class="pos-product-card text-start" @click="addProduct(product)">
                        <div class="pos-product-thumb" x-text="product.initial || '?'"></div>
                        <div class="text-sm font-bold text-white leading-tight mb-1" x-text="product.name"></div>
                        <div class="text-xs text-slate-400 mb-1" x-text="product.sku || product.barcode || ''"></div>
                        <div class="text-sm font-extrabold text-sky-300" x-text="formatMoney(product.sale_price)"></div>
                        <div class="text-xs mt-1" :class="product.current_quantity <= product.low_stock_alert_quantity ? 'text-amber-400' : 'text-slate-500'"
                             x-text="'متوفر: ' + product.current_quantity"></div>
                    </button>
                </template>
            </div>
        </section>
    </div>

    {{-- Split payment modal --}}
    <div class="pos-modal-backdrop no-print" x-show="splitModalOpen" x-transition @keydown.escape.window="splitModalOpen = false">
        <div class="pos-modal" @click.outside="splitModalOpen = false">
            <h3 class="text-lg font-bold text-white mb-3 inline-flex items-center gap-1"><x-info field="pos.terminal_split_payment" /> دفع مقسم</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">نقدي</label>
                    <input type="number" min="0" step="0.01" class="pos-search" x-model.number="splitCash">
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">بطاقة</label>
                    <input type="number" min="0" step="0.01" class="pos-search" x-model.number="splitCard">
                </div>
                <p class="text-xs text-slate-400 mb-0">المتبقي: <span x-text="formatMoney(Math.max(0, totals.total - (splitCash + splitCard)))"></span></p>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="button" class="pos-pay-pill" @click="splitModalOpen = false">إلغاء</button>
                <button type="button" class="pos-checkout-btn !py-3" @click="confirmSplit()">تأكيد</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posTerminal() {
    return {
        apiBase: @json(url('/pos/api')),
        deviceId: @json($selectedDeviceId),
        sessionId: @json($selectedSessionId),
        currencyCode: @json($currencyCode),
        canManualPrice: @json($canManualPrice),
        categories: @json($initialCategories),
        products: @json($initialProducts),
        selectedCategoryId: null,
        searchQuery: '',
        cart: [],
        paymentMethod: 'cash',
        splitCash: 0,
        splitCard: 0,
        splitModalOpen: false,
        checkoutLoading: false,
        errorMessage: '',
        scannerTimer: null,

        init() {
            this.$nextTick(() => this.focusScanner());
            document.addEventListener('click', (e) => {
                if (e.target.closest('.pos-modal, .pos-checkout-btn, .pos-pay-pill, select, input[type="number"]')) return;
                if (!e.target.closest('.pos-search') && !e.target.closest('.pos-product-card')) {
                    this.focusScanner();
                }
            });
        },

        focusScanner() {
            if (this.$refs.scanner) this.$refs.scanner.focus();
        },

        syncSession() {
            const sessions = @json($openSessions->map(fn ($s) => ['id' => $s->id, 'pos_device_id' => $s->pos_device_id]));
            const match = sessions.find(s => s.pos_device_id === this.deviceId);
            this.sessionId = match ? match.id : null;
        },

        selectCategory(id) {
            this.selectedCategoryId = id;
            this.loadProducts();
        },

        async loadProducts() {
            const params = new URLSearchParams();
            if (this.searchQuery.trim()) params.set('q', this.searchQuery.trim());
            if (this.selectedCategoryId) params.set('category_id', this.selectedCategoryId);
            const res = await fetch(`${this.apiBase}/products?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            this.products = data.products || [];
        },

        async handleScannerEnter() {
            const term = this.searchQuery.trim();
            if (!term) return;

            const lookup = await fetch(`${this.apiBase}/products/lookup?barcode=${encodeURIComponent(term)}`, { headers: { 'Accept': 'application/json' } });
            if (lookup.ok) {
                const data = await lookup.json();
                if (data.product) {
                    this.addProduct(data.product);
                    this.searchQuery = '';
                    this.loadProducts();
                    return;
                }
            }

            const exact = this.products.find(p => p.barcode === term || p.sku === term);
            if (exact) {
                this.addProduct(exact);
                this.searchQuery = '';
                return;
            }

            await this.loadProducts();
            const fromGrid = this.products.find(p => p.barcode === term || p.sku === term);
            if (fromGrid) {
                this.addProduct(fromGrid);
                this.searchQuery = '';
            }
        },

        addProduct(product) {
            const existing = this.cart.find(l => l.id === product.id);
            if (existing) {
                existing.quantity += 1;
                this.recalcLine(existing);
                return;
            }
            const line = {
                key: `${product.id}-${Date.now()}`,
                id: product.id,
                name: product.name,
                quantity: 1,
                unit_price: Number(product.sale_price),
                vat_percent: Number(product.vat_percent || 0),
                line_subtotal: 0,
                line_vat: 0,
                line_total: 0,
            };
            this.recalcLine(line);
            this.cart.unshift(line);
            this.errorMessage = '';
        },

        recalcLine(line) {
            line.line_subtotal = round4(line.quantity * line.unit_price);
            line.line_vat = round4(line.line_subtotal * (line.vat_percent / 100));
            line.line_total = round4(line.line_subtotal + line.line_vat);
        },

        changeQty(index, delta) {
            const line = this.cart[index];
            line.quantity = Math.max(1, round4(line.quantity + delta));
            this.recalcLine(line);
        },

        removeLine(index) {
            this.cart.splice(index, 1);
        },

        get totals() {
            let subtotal = 0, vat = 0;
            this.cart.forEach(l => {
                subtotal += l.line_subtotal;
                vat += l.line_vat;
            });
            return { subtotal: round4(subtotal), vat: round4(vat), total: round4(subtotal + vat) };
        },

        openSplitModal() {
            this.paymentMethod = 'mixed';
            this.splitCash = round2(this.totals.total / 2);
            this.splitCard = round2(this.totals.total - this.splitCash);
            this.splitModalOpen = true;
        },

        confirmSplit() {
            const sum = round2(this.splitCash + this.splitCard);
            if (Math.abs(sum - this.totals.total) > 0.02) {
                this.errorMessage = 'مجموع الدفع المقسم يجب أن يساوي الإجمالي.';
                return;
            }
            this.splitModalOpen = false;
            this.errorMessage = '';
        },

        async checkout() {
            if (!this.deviceId || this.cart.length === 0) return;
            this.checkoutLoading = true;
            this.errorMessage = '';

            const payload = {
                pos_device_id: this.deviceId,
                pos_session_id: this.sessionId,
                payment_method: this.paymentMethod,
                lines: this.cart.map(l => ({
                    pos_product_id: l.id,
                    quantity: l.quantity,
                    ...(this.canManualPrice ? { unit_price: l.unit_price } : {}),
                })),
            };

            if (this.paymentMethod === 'mixed') {
                payload.payment_splits = [
                    { method: 'cash', amount: this.splitCash },
                    { method: 'card', amount: this.splitCard },
                ];
            }

            try {
                const res = await fetch(`${this.apiBase}/checkout`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.errorMessage = data.message || 'تعذّر إتمام البيع.';
                    return;
                }
                this.cart = [];
                this.paymentMethod = 'cash';
                if (data.receipt_url) {
                    const w = window.open(data.receipt_url, '_blank', 'width=420,height=720');
                    if (w) w.focus();
                }
                await this.loadProducts();
            } catch (e) {
                this.errorMessage = 'خطأ في الاتصال بالخادم.';
            } finally {
                this.checkoutLoading = false;
                this.focusScanner();
            }
        },

        formatMoney(value) {
            return `${this.currencyCode} ${Number(value || 0).toFixed(2)}`;
        },
    };
}

function round4(n) { return Math.round(Number(n) * 10000) / 10000; }
function round2(n) { return Math.round(Number(n) * 100) / 100; }
</script>
@endpush
