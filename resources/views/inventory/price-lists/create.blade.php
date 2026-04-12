@extends('layouts.app')

@section('title', 'قائمة أسعار جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.price-lists.index') }}" class="text-gray-500 hover:text-indigo-600">قوائم الأسعار</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">قائمة أسعار جديدة</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">قائمة أسعار جديدة</h1>
            <p class="mt-1 text-sm text-gray-500">إنشاء قائمة أسعار لربطها لاحقاً بالمبيعات أو المشتريات</p>
        </div>
        <a href="{{ route('inventory.price-lists.index') }}" class="btn-cancel inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
    </header>

    <form action="{{ route('inventory.price-lists.store') }}" method="POST" id="price-list-form" class="space-y-6">
        @csrf

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">المعلومات الأساسية</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_code" /> الرمز <span class="text-red-600">*</span></label>
                        <input type="text" name="code" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('code') border-red-500 @enderror" value="{{ old('code') }}" required maxlength="50" placeholder="مثال: PL-001">
                        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_name" /> الاسم <span class="text-red-600">*</span></label>
                        <input type="text" name="name" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('name') border-red-500 @enderror" value="{{ old('name') }}" required placeholder="مثال: أسعار التجزئة 2024">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_currency" /> العملة</label>
                        <input type="text" name="currency" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('currency') border-red-500 @enderror" value="{{ old('currency', 'SAR') }}" maxlength="10">
                        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_type" /> نوع القائمة <span class="text-red-600">*</span></label>
                        <select name="type" id="list-type" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('type') border-red-500 @enderror" required>
                            @foreach($types as $k => $v)
                                <option value="{{ $k }}" {{ old('type', 'sale') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">قواعد التسعير</div>
            <div class="p-4 md:p-6">
                <p class="mb-4 text-xs text-gray-500">قوائم الأسعار ذات الأولوية الأعلى لها الأسبقية على الأقل.</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_pricing_method" /> طريقة التسعير</label>
                        <select name="pricing_method" id="pricing-method" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('pricing_method') border-red-500 @enderror">
                            @foreach($pricingMethods as $k => $v)
                                <option value="{{ $k }}" {{ old('pricing_method', 'fixed') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">اختر طريقة حساب الأسعار لهذه القائمة</p>
                        @error('pricing_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_default_margin" /> الهامش الافتراضي (%)</label>
                        <input type="number" inputmode="decimal" name="default_margin_percent" id="default-margin-percent" step="any" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('default_margin_percent') border-red-500 @enderror" value="{{ old('default_margin_percent') }}" placeholder="مثال: 10">
                        <p class="mt-1 text-xs text-gray-500">نسبة الهامش المطبقة على الأصناف لاقتراح السعر</p>
                        @error('default_margin_percent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_priority" /> الأولوية</label>
                        <input type="number" inputmode="decimal" name="priority" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('priority') border-red-500 @enderror" value="{{ old('priority', 0) }}" min="0">
                        @error('priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">الصلاحية والإعدادات</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_valid_from" /> تاريخ البداية</label>
                        <input type="date" name="valid_from" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('valid_from') border-red-500 @enderror" value="{{ old('valid_from') }}">
                        @error('valid_from')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_valid_to" /> تاريخ النهاية</label>
                        <input type="date" name="valid_to" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('valid_to') border-red-500 @enderror" value="{{ old('valid_to') }}">
                        @error('valid_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="mb-2 flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800"><x-info field="inventory.pricelist_is_default" /> افتراضي</span>
                        </label>
                        <p class="text-xs text-gray-500">تعيين هذه كقائمة الأسعار الافتراضية للعملاء الجدد</p>
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="mb-2 flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800"><x-info field="inventory.pricelist_is_active" /> نشط</span>
                        </label>
                        <p class="text-xs text-gray-500">تفعيل أو تعطيل قائمة الأسعار هذه</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm font-semibold text-gray-800">المنتجات</span>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" id="apply-margin-all">تطبيق الهامش على الكل</button>
                    <button type="button" class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50" id="add-line">إضافة منتج</button>
                </div>
            </div>
            <div class="p-4 md:p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[720px] border-collapse text-sm" id="lines-table">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_product" /> اسم المنتج</th>
                                <th class="w-32 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_cost" /> التكلفة الحالية</th>
                                <th class="w-32 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_old_price" /> السعر القديم</th>
                                <th class="w-36 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_new_price" /> السعر الجديد</th>
                                <th class="w-12 border-b border-gray-200 px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-tbody"></tbody>
                    </table>
                </div>
                <p id="lines-empty-msg" class="mt-2 text-xs text-gray-500">لم تتم إضافة منتجات بعد. اختر المنتجات من القائمة أعلاه.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800"><x-info field="inventory.pricelist_description" /> الوصف</div>
            <div class="p-4 md:p-6">
                <textarea name="notes" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2" placeholder="اختياري: يُولّد وصف تلقائي عند تركه فارغاً">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventory.price-lists.index') }}" class="btn-cancel inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">إنشاء</button>
        </div>
    </form>
</div>

<script>
(function() {
    const items = @json($itemsForJs);
    const tbody = document.getElementById('lines-tbody');
    const addBtn = document.getElementById('add-line');
    const listType = document.getElementById('list-type');
    const pricingMethod = document.getElementById('pricing-method');
    const defaultMarginPercent = document.getElementById('default-margin-percent');
    const applyMarginAll = document.getElementById('apply-margin-all');
    const linesEmptyMsg = document.getElementById('lines-empty-msg');
    const form = document.getElementById('price-list-form');
    const finp = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';
    const fsel = 'h-10 w-full min-w-[10rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';

    function usedItemIds() {
        return Array.from(tbody.querySelectorAll('select.line-item')).map(s => s.value).filter(Boolean).map(Number);
    }

    function suggestedNewPrice(cost, marginPercent) {
        if (cost == null || marginPercent === '' || marginPercent == null) return null;
        const p = parseFloat(marginPercent);
        return Math.round((cost * (1 + p / 100)) * 10000) / 10000;
    }

    function buildItemOptions(selectedId) {
        const used = usedItemIds();
        let html = '<option value="">— اختر المنتج —</option>';
        items.forEach(function(it) {
            if (selectedId == it.id || !used.includes(it.id)) {
                const label = (it.name_ar || it.name_en || it.code) + ' (' + it.code + ')';
                const cost = it.cost || 0;
                const oldPrice = listType.value === 'sale' ? (it.selling_price || it.cost || 0) : (it.cost || 0);
                html += '<option value="' + it.id + '" data-cost="' + cost + '" data-old="' + oldPrice + '"' + (selectedId == it.id ? ' selected' : '') + '>' + label + '</option>';
            }
        });
        return html;
    }

    function updateRowSuggestedPrice(tr) {
        const method = pricingMethod && pricingMethod.value;
        const margin = defaultMarginPercent && defaultMarginPercent.value;
        const costEl = tr.querySelector('.line-cost');
        const newPriceEl = tr.querySelector('.line-price');
        if (!newPriceEl || method !== 'margin' || !margin) return;
        const cost = parseFloat(costEl && costEl.textContent ? costEl.textContent.replace(/,/g, '') : 0);
        const suggested = suggestedNewPrice(cost, margin);
        if (suggested != null) newPriceEl.value = suggested;
    }

    function addRow(itemId, newPrice) {
        const idx = tbody.querySelectorAll('tr.line-row').length;
        const tr = document.createElement('tr');
        tr.className = 'line-row border-b border-gray-100 hover:bg-gray-50/60';
        const item = items.find(i => i.id == itemId);
        const cost = item ? (item.cost || 0) : 0;
        const oldPrice = item ? (listType.value === 'sale' ? (item.selling_price || item.cost || 0) : (item.cost || 0)) : 0;
        let suggested = newPrice;
        if (suggested == null && pricingMethod && pricingMethod.value === 'margin' && defaultMarginPercent && defaultMarginPercent.value !== '')
            suggested = suggestedNewPrice(cost, defaultMarginPercent.value);
        if (suggested == null) suggested = oldPrice || cost;
        const costFormatted = (cost || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        const oldFormatted = (oldPrice || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        tr.innerHTML =
            '<td class="px-3 py-2"><select name="lines[' + idx + '][item_id]" class="line-item ' + fsel + '" required>' + buildItemOptions(itemId) + '</select></td>' +
            '<td class="line-cost px-3 py-2 align-middle tabular-nums text-gray-800">' + costFormatted + '</td>' +
            '<td class="line-old px-3 py-2 align-middle tabular-nums text-gray-800">' + oldFormatted + '</td>' +
            '<td class="px-3 py-2"><input type="number" inputmode="decimal" step="any" min="0" name="lines[' + idx + '][price]" class="line-price ' + finp + '" value="' + suggested + '" required></td>' +
            '<td class="px-2 py-2"><button type="button" class="remove-line inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50" aria-label="حذف">×</button></td>';
        tbody.appendChild(tr);
        if (linesEmptyMsg) linesEmptyMsg.classList.add('hidden');

        const sel = tr.querySelector('.line-item');
        sel.addEventListener('change', function() {
            const opt = this.selectedOptions[0];
            if (!opt || !opt.value) return;
            const cost = parseFloat(opt.dataset.cost || 0);
            const oldP = parseFloat(opt.dataset.old || 0);
            tr.querySelector('.line-cost').textContent = cost.toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
            tr.querySelector('.line-old').textContent = oldP.toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
            updateRowSuggestedPrice(tr);
        });
        tr.querySelector('.remove-line').addEventListener('click', function() {
            tr.remove();
            if (tbody.querySelectorAll('tr.line-row').length === 0 && linesEmptyMsg) linesEmptyMsg.classList.remove('hidden');
        });
    }

    addBtn.addEventListener('click', function() { addRow(null, null); });

    if (applyMarginAll) {
        applyMarginAll.addEventListener('click', function() {
            const margin = defaultMarginPercent && defaultMarginPercent.value;
            if (margin === '' || margin == null) {
                alert('أدخل نسبة الهامش الافتراضي أولاً.');
                return;
            }
            tbody.querySelectorAll('tr.line-row').forEach(function(tr) {
                const costEl = tr.querySelector('.line-cost');
                const cost = parseFloat(costEl && costEl.textContent ? costEl.textContent.replace(/,/g, '') : 0);
                const suggested = suggestedNewPrice(cost, margin);
                if (suggested != null) tr.querySelector('.line-price').value = suggested;
            });
        });
    }

    document.querySelectorAll('.btn-cancel').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (tbody.querySelectorAll('tr.line-row').length > 0 && !confirm('لديك منتجات مضافة. هل تريد إلغاء إنشاء القائمة؟')) e.preventDefault();
        });
    });
})();
</script>
@endsection
