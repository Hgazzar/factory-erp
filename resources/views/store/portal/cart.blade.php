@extends('layouts.store-portal')

@section('title', 'سلة المشتريات — '.$storeName)

@section('content')
<div x-data="storeCartPage()" x-init="load()">
    <h1 class="h4 fw-bold mb-3"><x-info field="store.cart_heading" /> سلة المشتريات</h1>

    <template x-if="lines.length === 0">
        <div class="text-center py-5 bg-white rounded-3 border">
            <p class="text-muted mb-3">سلتك فارغة</p>
            <a href="{{ route('store.portal.home', ['tenant_slug' => $tenantSlug]) }}" class="store-btn d-inline-block" style="width:auto;padding:.65rem 1.5rem">تسوق الآن</a>
        </div>
    </template>

    <template x-if="lines.length > 0">
        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="bg-white rounded-3 border p-3">
                    <template x-for="(line, idx) in lines" :key="line.id">
                        <div class="d-flex justify-content-between align-items-start gap-2 py-3 border-bottom">
                            <div>
                                <div class="fw-bold" x-text="line.name"></div>
                                <div class="small text-muted" x-text="$root.formatMoney(unitTotal(line)) + ' / وحدة'"></div>
                            </div>
                            <div class="text-end">
                                <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(idx, -1)">−</button>
                                    <span class="px-2 fw-bold" x-text="line.quantity"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(idx, 1)">+</button>
                                </div>
                                <div class="fw-bold text-primary" x-text="$root.formatMoney(lineTotal(line))"></div>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0" @click="remove(idx)">حذف</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="bg-white rounded-3 border p-3 sticky-top" style="top:5rem">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">المجموع</span><span x-text="$root.formatMoney(totals.subtotal)"></span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">الضريبة</span><span x-text="$root.formatMoney(totals.vat)"></span></div>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3 pt-2 border-top"><span>الإجمالي</span><span x-text="$root.formatMoney(totals.total)"></span></div>
                    <a href="{{ route('store.portal.checkout', ['tenant_slug' => $tenantSlug]) }}" class="store-btn d-block text-center text-decoration-none">إتمام الطلب</a>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function storeCartPage() {
    return {
        lines: [],
        load() { this.lines = this.$root.readCart(); },
        save() {
            localStorage.setItem(this.$root.storageKey(), JSON.stringify(this.lines));
            window.dispatchEvent(new CustomEvent('store-cart-updated'));
        },
        unitTotal(line) {
            const sub = Number(line.sale_price);
            return sub + sub * (Number(line.vat_percent || 0) / 100);
        },
        lineTotal(line) { return this.unitTotal(line) * Number(line.quantity); },
        get totals() {
            let subtotal = 0, vat = 0;
            this.lines.forEach(l => {
                const sub = Number(l.sale_price) * Number(l.quantity);
                const v = sub * (Number(l.vat_percent || 0) / 100);
                subtotal += sub; vat += v;
            });
            return { subtotal, vat, total: subtotal + vat };
        },
        changeQty(idx, delta) {
            const line = this.lines[idx];
            line.quantity = Math.max(1, Number(line.quantity) + delta);
            this.save(); this.load();
        },
        remove(idx) { this.lines.splice(idx, 1); this.save(); this.load(); },
    };
}
</script>
@endpush
