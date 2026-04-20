@extends('layouts.app')

@section('title', 'إذن إضافة مخزني جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إذن إضافة جديد</span>
@endsection

@section('content')
@php
    $stockInSupplierOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->name ?? '').' ('.(string) ($s->code ?? '').')'),
    ])->all();
@endphp
<div class="max-w-full" dir="rtl" x-data="stockInCreateForm(@js($items), @js($warehouses), @js(route('api.products.search')))" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">إذن إضافة مخزني جديد</h1>
        <a href="{{ route('inventory.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('inventory.stock-in.store') }}" class="space-y-6" @submit="if (!guardLineItems($event)) return">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">بيانات الإذن</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="supplier_id-trigger">المورد <span class="text-red-500">*</span> <x-info field="inventory.stock_in_supplier" /></label>
                    <x-searchable-select
                        class="w-full"
                        name="supplier_id"
                        id="supplier_id"
                        :options="$stockInSupplierOptions"
                        :value="old('supplier_id')"
                        :required="true"
                        :error="$errors->has('supplier_id')"
                        empty-label="اختر المورد"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تسوية الشراء <span class="text-red-500">*</span> <x-info field="inventory.stock_in_settlement" /></label>
                    <select name="settlement_type" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 @error('settlement_type') border-red-500 @enderror">
                        <option value="on_account" @selected(old('settlement_type', 'on_account') === 'on_account')>على ذمة المورد (ذمم دائنة 2010)</option>
                        <option value="cash" @selected(old('settlement_type') === 'cash')>دفع نقدي من الصندوق (1010)</option>
                    </select>
                    @error('settlement_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">التاريخ <span class="text-red-500">*</span> <x-info field="inventory.stock_in_date" /></label>
                    <input type="date" name="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">مرجع خارجي <x-info field="inventory.stock_in_reference" /></label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="اختياري">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات <x-info field="inventory.stock_in_notes" /></label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="اختياري">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود الإضافة</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_product" /> المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28"><x-info field="inventory.stock_in_qty" /> الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-32"><x-info field="inventory.stock_in_purchase_price" /> سعر الشراء</th>
                            <th class="py-3 px-3 font-medium text-gray-600 min-w-[140px]"><x-info field="inventory.stock_in_warehouse" /> المستودع</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-32"><x-info field="inventory.stock_in_line_total" /> الإجمالي</th>
                            <th class="py-3 px-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <div class="relative">
                                        <input
                                            type="search"
                                            x-model="line.item_search"
                                            @focus="onProductFocus(index)"
                                            @input="onProductInput(index)"
                                            @keydown.escape="line.dropdownOpen = false"
                                            placeholder="ابحث بالاسم أو الـ SKU..."
                                            class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                                        >
                                        <input type="hidden" :name="`lines[${index}][item_id]`" :value="line.item_id">
                                        <p class="mt-0.5 text-xs text-gray-500 min-h-[1rem]" x-show="line.unit_label" x-text="'الوحدة: ' + line.unit_label"></p>
                                        <div
                                            x-show="line.dropdownOpen"
                                            @click.away="line.dropdownOpen = false"
                                            class="absolute z-10 right-0 left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-sm max-h-48 overflow-y-auto"
                                        >
                                            <template x-if="line.searchLoading">
                                                <div class="px-3 py-2 text-xs text-gray-500">جاري البحث...</div>
                                            </template>
                                            <template x-if="!line.searchLoading">
                                                <template x-for="item in line.searchResults" :key="item.id">
                                                    <button
                                                        type="button"
                                                        class="w-full px-3 py-2 text-right hover:bg-gray-50 text-sm border-b border-gray-50 last:border-0"
                                                        @click.prevent="selectItem(index, item)"
                                                    >
                                                        <span class="font-medium" x-text="item.display_name || item.name_ar || item.name_en || item.code"></span>
                                                        <span class="block text-xs text-gray-500" x-show="item.sku" x-text="'SKU: ' + item.sku"></span>
                                                    </button>
                                                </template>
                                            </template>
                                            <template x-if="!line.searchLoading && line.searchResults.length === 0">
                                                <div class="px-3 py-2 text-xs text-gray-500">لا توجد نتائج</div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0.0001" step="any" :name="`lines[${index}][quantity]`" x-model.number="line.quantity" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" step="any" :name="`lines[${index}][purchase_price]`" x-model.number="line.purchase_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                                </td>
                                <td class="py-2 px-3">
                                    <select :name="`lines[${index}][warehouse_id]`" x-model="line.warehouse_id" required class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">اختر</option>
                                        <template x-for="wh in warehouses" :key="wh.id">
                                            <option :value="String(wh.id)" x-text="wh.name_ar || wh.code"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-2 px-3 text-gray-900 font-medium" x-text="'SAR ' + lineTotal(line).toFixed(2)"></td>
                                <td class="py-2 px-3">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 1" class="p-1.5 rounded text-red-600 hover:bg-red-50 transition" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 justify-end">
            <a href="{{ route('inventory.dashboard') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ إذن الإضافة</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    window.stockInCreateForm = function(items, warehouses, searchUrl) {
        searchUrl = searchUrl || '';
        const defaultWh = (warehouses && warehouses.length) ? String(warehouses[0].id) : '';
        const findItemDisplay = (id) => {
            if (!id) return '';
            const item = (items || []).find(i => String(i.id) === String(id));
            return item ? (item.name_ar || item.name_en || item.code) : '';
        };
        const emptyLine = () => ({
            item_id: '',
            item_search: '',
            dropdownOpen: false,
            searchResults: [],
            searchLoading: false,
            unit_label: '',
            quantity: 1,
            purchase_price: 0,
            warehouse_id: defaultWh,
        });
        return {
            items: items || [],
            warehouses: warehouses || [],
            searchUrl,
            searchTimers: {},
            lines: [emptyLine()],
            guardLineItems(e) {
                if (this.lines.some((l) => !String(l.item_id || '').trim())) {
                    e.preventDefault();
                    alert('يرجى اختيار صنفاً من نتائج البحث لكل بند.');
                    return false;
                }
                return true;
            },
            onProductFocus(lineIndex) {
                this.lines[lineIndex].dropdownOpen = true;
                this.runProductSearch(lineIndex);
            },
            onProductInput(lineIndex) {
                const line = this.lines[lineIndex];
                line.dropdownOpen = true;
                line.item_id = '';
                line.purchase_price = 0;
                line.unit_label = '';
                clearTimeout(this.searchTimers[lineIndex]);
                this.searchTimers[lineIndex] = setTimeout(() => this.runProductSearch(lineIndex), 300);
            },
            async runProductSearch(lineIndex) {
                const line = this.lines[lineIndex];
                if (!this.searchUrl) return;
                line.searchLoading = true;
                try {
                    const q = (line.item_search || '').trim();
                    const url = new URL(this.searchUrl, window.location.origin);
                    url.searchParams.set('q', q);
                    const res = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const json = await res.json();
                    line.searchResults = json.data || [];
                } catch (e) {
                    line.searchResults = [];
                } finally {
                    line.searchLoading = false;
                }
            },
            selectItem(lineIndex, item) {
                const cost = parseFloat(item.cost ?? 0) || 0;
                this.lines[lineIndex].item_id = String(item.id);
                this.lines[lineIndex].item_search = item.display_name || item.name_ar || item.name_en || item.code || '';
                this.lines[lineIndex].purchase_price = cost >= 0 ? cost : 0;
                this.lines[lineIndex].unit_label = item.unit || '';
                this.lines[lineIndex].dropdownOpen = false;
                this.lines[lineIndex].searchResults = [];
            },
            addLine() {
                this.lines.push(emptyLine());
            },
            removeLine(index) {
                if (this.lines.length > 1) this.lines.splice(index, 1);
            },
            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0;
                const p = parseFloat(line.purchase_price) || 0;
                return q * p;
            },
        };
    };
});
</script>
@endpush
@endsection
