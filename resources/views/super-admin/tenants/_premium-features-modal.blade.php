{{-- مودال المزايا البريميوم — يُستخدم داخل عنصر x-data="superAdminPremiumFeatures()" --}}
<div
    x-on:keydown.escape.window="close()"
    class="relative z-50"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-gray-900/50"
        @click="close()"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="'premium-modal-title'"
    >
        <div
            class="w-full max-w-lg rounded-xl bg-white shadow-xl border border-gray-200 max-h-[min(90vh,640px)] flex flex-col"
            @click.stop
            dir="rtl"
        >
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 id="premium-modal-title" class="text-lg font-bold text-gray-900 flex items-center gap-1">
                        <x-info field="super_admin_premium_features" />
                        المزايا البريميوم
                    </h2>
                    <p class="mt-1 text-sm text-gray-500" x-show="panel.tenant_name" x-text="panel.tenant_name"></p>
                    <p class="mt-0.5 text-xs text-indigo-700" x-show="panel.niche_name">
                        النيش: <span x-text="panel.niche_name"></span>
                    </p>
                </div>
                <button type="button" @click="close()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="إغلاق">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                <div x-show="loading" class="py-8 text-center text-sm text-gray-500">جاري التحميل…</div>

                <div x-show="!loading && error" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800" x-text="error"></div>

                <div x-show="!loading && !error && panel.has_catalog === false" class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                    لا توجد مزايا بريميوم معرّفة لنيش هذا المستأجر. يمكنك تعديل <strong>موديولات النظام</strong> من صفحة التفاصيل.
                </div>

                <ul x-show="!loading && !error && panel.features && panel.features.length" class="space-y-3">
                    <template x-for="feature in panel.features" :key="feature.key">
                        <li>
                            <template x-if="feature.group">
                                <p class="text-xs font-semibold text-gray-500 mb-1 mt-2 first:mt-0" x-text="feature.group"></p>
                            </template>
                            <label
                                class="flex items-start gap-3 rounded-lg border p-4"
                                :class="feature.locked ? 'border-gray-200 bg-gray-50 opacity-75 cursor-not-allowed' : 'border-gray-200 cursor-pointer hover:bg-gray-50'"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
                                    :value="feature.key"
                                    x-model="selected"
                                    :disabled="feature.locked"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-gray-900" x-text="feature.name_ar"></span>
                                    <span class="block text-xs text-gray-500 mt-0.5" x-text="feature.description_ar"></span>
                                    <span
                                        x-show="feature.requires_module"
                                        class="inline-block mt-1 text-xs rounded-full bg-gray-200 px-2 py-0.5 text-gray-700"
                                    >
                                        يتطلب موديول: <span x-text="feature.requires_module"></span>
                                    </span>
                                    <span
                                        x-show="feature.locked && feature.locked_reason"
                                        class="block text-xs text-amber-700 mt-1"
                                        x-text="feature.locked_reason"
                                    ></span>
                                    <span class="block text-xs text-gray-400 font-mono mt-1" dir="ltr" x-text="feature.key"></span>
                                </span>
                            </label>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="border-t border-gray-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <a
                    x-show="tenantId"
                    :href="detailUrl"
                    class="text-xs text-indigo-600 hover:text-indigo-800"
                >موديولات النظام (صفحة كاملة) ←</a>
                <div class="flex gap-2 mr-auto">
                    <button type="button" @click="close()" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        إلغاء
                    </button>
                    <button
                        type="button"
                        @click="save()"
                        :disabled="saving || loading || !panel.has_catalog"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span x-text="saving ? 'جاري الحفظ…' : 'حفظ'"></span>
                    </button>
                </div>
            </div>

            <div x-show="successMessage" class="border-t border-green-100 bg-green-50 px-5 py-2 text-sm text-green-800" x-text="successMessage"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    Alpine.data('superAdminPremiumFeatures', () => ({
        open: false,
        loading: false,
        saving: false,
        error: null,
        successMessage: null,
        tenantId: null,
        panel: { features: [], has_catalog: false, tenant_name: '', niche_name: null },
        selected: [],
        premiumFeaturesBase: @json(url('super-admin/tenants')),
        get detailUrl() {
            return this.tenantId ? `${this.premiumFeaturesBase}/${this.tenantId}` : '#';
        },
        async openFor(tenantId) {
            this.tenantId = tenantId;
            this.open = true;
            this.error = null;
            this.successMessage = null;
            this.loading = true;
            document.body.classList.add('overflow-hidden');

            try {
                const res = await fetch(`${this.premiumFeaturesBase}/${tenantId}/premium-features`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    throw new Error('تعذّر تحميل المزايا.');
                }
                const data = await res.json();
                this.applyPanel(data);
            } catch (e) {
                this.error = e.message || 'حدث خطأ أثناء التحميل.';
            } finally {
                this.loading = false;
            }
        },
        applyPanel(data) {
            this.panel = data;
            this.selected = (data.features || [])
                .filter(f => f.enabled && !f.locked)
                .map(f => f.key);
        },
        close() {
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        },
        async save() {
            if (!this.tenantId || !this.panel.has_catalog) return;
            this.saving = true;
            this.error = null;
            this.successMessage = null;

            try {
                const res = await fetch(`${this.premiumFeaturesBase}/${this.tenantId}/premium-features`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ features: this.selected }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'تعذّر الحفظ.');
                }
                if (data.panel) {
                    this.applyPanel(data.panel);
                }
                this.successMessage = data.message || 'تم الحفظ.';
                setTimeout(() => { this.successMessage = null; }, 4000);
            } catch (e) {
                this.error = e.message || 'حدث خطأ أثناء الحفظ.';
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush
