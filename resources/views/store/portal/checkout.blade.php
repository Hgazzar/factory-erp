@extends('layouts.store-portal')

@section('title', 'إتمام الطلب — '.$storeName)

@section('content')
<div x-data="storeCheckout(@json($apiBase), '{{ route('store.portal.home', ['tenant_slug' => $tenantSlug]) }}')" x-init="loadCart()">
    <h1 class="h4 fw-bold mb-3"><x-info field="store.checkout_heading" /> إتمام الطلب</h1>
    <p class="small text-muted mb-3"><x-info field="store.checkout_cod" /> الدفع عند الاستلام (COD)</p>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <form class="bg-white rounded-3 border p-3" @submit.prevent="submit()">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><x-info field="store.customer_name" /> الاسم</label>
                    <input type="text" class="form-control" x-model="form.name" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><x-info field="store.customer_phone" /> الهاتف</label>
                    <input type="tel" class="form-control" x-model="form.phone" required maxlength="32" dir="ltr">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><x-info field="store.customer_address" /> العنوان</label>
                    <textarea class="form-control" rows="3" x-model="form.address" required maxlength="2000"></textarea>
                </div>
                <p x-show="error" class="text-danger small" x-text="error"></p>
                <button type="submit" class="store-btn" :disabled="loading || lines.length === 0">
                    <span x-text="loading ? 'جاري إرسال الطلب…' : 'تأكيد الطلب'"></span>
                </button>
            </form>
        </div>
        <div class="col-12 col-lg-5">
            <div class="bg-white rounded-3 border p-3">
                <h2 class="h6 fw-bold mb-3">ملخص الطلب</h2>
                <template x-for="line in lines" :key="line.id">
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span x-text="line.name + ' × ' + line.quantity"></span>
                        <span x-text="$root.formatMoney(lineTotal(line))"></span>
                    </div>
                </template>
                <div class="d-flex justify-content-between fw-bold mt-3 pt-2 border-top">
                    <span>الإجمالي</span>
                    <span x-text="$root.formatMoney(totals.total)"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function storeCheckout(apiBase, homeUrl) {
    return {
        apiBase, homeUrl, lines: [], loading: false, error: '',
        form: { name: '', phone: '', address: '' },
        loadCart() { this.lines = this.$root.readCart(); },
        lineTotal(line) {
            const sub = Number(line.sale_price) * Number(line.quantity);
            return sub + sub * (Number(line.vat_percent || 0) / 100);
        },
        get totals() {
            let subtotal = 0, vat = 0;
            this.lines.forEach(l => {
                const sub = Number(l.sale_price) * Number(l.quantity);
                const v = sub * (Number(l.vat_percent || 0) / 100);
                subtotal += sub; vat += v;
            });
            return { subtotal, vat, total: subtotal + vat };
        },
        async submit() {
            if (this.lines.length === 0) { this.error = 'السلة فارغة.'; return; }
            this.loading = true; this.error = '';
            try {
                const res = await fetch(`${this.apiBase}/checkout`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        customer_name: this.form.name,
                        customer_phone: this.form.phone,
                        customer_address: this.form.address,
                        lines: this.lines.map(l => ({ pos_product_id: l.id, quantity: l.quantity })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) { this.error = data.message || 'تعذّر إتمام الطلب.'; return; }
                localStorage.removeItem(this.$root.storageKey());
                window.dispatchEvent(new CustomEvent('store-cart-updated'));
                window.location.href = data.success_url;
            } catch {
                this.error = 'خطأ في الاتصال.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
