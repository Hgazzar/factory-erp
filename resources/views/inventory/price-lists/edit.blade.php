@extends('layouts.app')

@section('title', 'تعديل قائمة الأسعار - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.price-lists.index') }}" class="text-gray-500 hover:text-indigo-600">قوائم الأسعار</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تعديل: {{ $priceList->name }}</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تعديل قائمة الأسعار</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $priceList->code }}</p>
        </div>
        <a href="{{ route('inventory.price-lists.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
    </header>

    @if(session('success'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800"><x-info field="inventory.pricelist_percent_adjust" /> تحديث الأسعار بنسبة مئوية</div>
        <div class="p-4 md:p-6">
            <form action="{{ route('inventory.price-lists.update-percent', $priceList) }}" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[200px] flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-600">النسبة % (موجب = زيادة، سالب = تخفيض)</label>
                    <input type="number" inputmode="decimal" name="percent" step="any" class="h-10 w-full max-w-xs rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="مثال: 5 أو -10" required>
                </div>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">تطبيق على كل الأصناف</button>
            </form>
        </div>
    </section>

    <form action="{{ route('inventory.price-lists.update', $priceList) }}" method="POST" id="price-list-form" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">البيانات الأساسية</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_code" /> الرمز <span class="text-red-600">*</span></label>
                        <input type="text" name="code" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('code') border-red-500 @enderror" value="{{ old('code', $priceList->code) }}" required maxlength="50">
                        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_name" /> الاسم <span class="text-red-600">*</span></label>
                        <input type="text" name="name" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('name') border-red-500 @enderror" value="{{ old('name', $priceList->name) }}" required>
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_currency" /> العملة</label>
                        <input type="text" name="currency" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('currency') border-red-500 @enderror" value="{{ old('currency', $priceList->currency) }}" maxlength="10">
                        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_type" /> نوع القائمة <span class="text-red-600">*</span></label>
                        <select name="type" id="list-type" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('type') border-red-500 @enderror" required>
                            @foreach($types as $k => $v)
                                <option value="{{ $k }}" {{ old('type', $priceList->type) == $k ? 'selected' : '' }}>{{ $v }}</option>
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_pricing_method" /> طريقة التسعير</label>
                        <select name="pricing_method" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('pricing_method') border-red-500 @enderror">
                            @foreach($pricingMethods as $k => $v)
                                <option value="{{ $k }}" {{ old('pricing_method', $priceList->pricing_method ?? 'fixed') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('pricing_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_default_margin" /> الهامش الافتراضي (%)</label>
                        <input type="number" inputmode="decimal" name="default_margin_percent" step="any" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('default_margin_percent') border-red-500 @enderror" value="{{ old('default_margin_percent', $priceList->default_margin_percent) }}">
                        @error('default_margin_percent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">الصلاحية والإعدادات</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_valid_from" /> صالح من</label>
                        <input type="date" name="valid_from" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('valid_from') border-red-500 @enderror" value="{{ old('valid_from', $priceList->valid_from?->format('Y-m-d')) }}">
                        @error('valid_from')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_valid_to" /> صالح إلى</label>
                        <input type="date" name="valid_to" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('valid_to') border-red-500 @enderror" value="{{ old('valid_to', $priceList->valid_to?->format('Y-m-d')) }}">
                        @error('valid_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.pricelist_priority" /> الأولوية</label>
                        <input type="number" inputmode="decimal" name="priority" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('priority') border-red-500 @enderror" value="{{ old('priority', $priceList->priority) }}" min="0" step="any">
                        @error('priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $priceList->is_default) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800"><x-info field="inventory.pricelist_is_default" /> افتراضي</span>
                        </label>
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $priceList->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800"><x-info field="inventory.pricelist_is_active" /> نشط</span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm font-semibold text-gray-800">جدول الأصناف</span>
                <button type="button" class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50" id="add-line">+ إضافة صنف</button>
            </div>
            <div class="p-4 md:p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[560px] border-collapse text-sm" id="lines-table">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_product" /> الصنف</th>
                                <th class="w-40 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_line_new_price" /> السعر</th>
                                <th class="w-12 border-b border-gray-200 px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-tbody">
                            @php $boundLines = old('lines'); if ($boundLines === null) { $boundLines = $priceList->items; } @endphp
                            @foreach($boundLines as $idx => $line)
                            @php
                                $itemId = is_object($line) ? $line->item_id : ($line['item_id'] ?? null);
                                $price = is_object($line) ? $line->price : ($line['price'] ?? 0);
                                $others = collect($boundLines)->filter(fn($l, $i) => $i != $idx)->map(fn($l) => is_object($l) ? $l->item_id : ($l['item_id'] ?? null))->filter()->values()->all();
                            @endphp
                            <tr class="line-row border-b border-gray-100 hover:bg-gray-50/60">
                                <td class="px-3 py-2">
                                    <select name="lines[{{ $idx }}][item_id]" class="line-item h-10 w-full min-w-[12rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                                        <option value="">— اختر الصنف —</option>
                                        @foreach($items as $it)
                                            @if($it->id == $itemId || !in_array($it->id, $others))
                                                @php $defPrice = ($priceList->type ?? 'sale') === 'sale' ? ($it->selling_price ?? $it->cost ?? 0) : ($it->cost ?? $it->selling_price ?? 0); @endphp
                                                <option value="{{ $it->id }}" data-price="{{ $defPrice }}" {{ $it->id == $itemId ? 'selected' : '' }}>
                                                    {{ $it->name_ar ?? $it->name_en ?? $it->code }} ({{ $it->code }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input type="number" inputmode="decimal" step="any" min="0" name="lines[{{ $idx }}][price]" class="line-price h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" value="{{ old("lines.{$idx}.price", $price) }}" required></td>
                                <td class="px-2 py-2"><button type="button" class="remove-line inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50">×</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800"><x-info field="inventory.pricelist_description" /> ملاحظات</div>
            <div class="p-4 md:p-6">
                <textarea name="notes" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2">{{ old('notes', $priceList->notes) }}</textarea>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventory.price-lists.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">حفظ التعديلات</button>
        </div>
    </form>
</div>

<script>
(function() {
    const items = @json($items->map(fn($i) => ['id' => $i->id, 'code' => $i->code, 'name_ar' => $i->name_ar, 'name_en' => $i->name_en, 'selling_price' => (float)($i->selling_price ?? 0), 'cost' => (float)($i->cost ?? 0)])->values());
    const tbody = document.getElementById('lines-tbody');
    const addBtn = document.getElementById('add-line');
    const listType = document.getElementById('list-type');
    const finp = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';
    const fsel = 'line-item h-10 w-full min-w-[12rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';

    function usedItemIds(excludeRow) {
        const rows = tbody.querySelectorAll('tr.line-row');
        const used = [];
        rows.forEach(function(tr) {
            if (tr === excludeRow) return;
            const sel = tr.querySelector('select.line-item');
            if (sel && sel.value) used.push(parseInt(sel.value, 10));
        });
        return used;
    }

    function defaultPrice(item) {
        return listType.value === 'sale' ? (item.selling_price || item.cost || 0) : (item.cost || item.selling_price || 0);
    }

    function buildItemOptions(selectedId, excludeRow) {
        const used = usedItemIds(excludeRow);
        let html = '<option value="">— اختر الصنف —</option>';
        items.forEach(function(it) {
            if (selectedId == it.id || used.indexOf(it.id) === -1) {
                const label = (it.name_ar || it.name_en || it.code) + ' (' + it.code + ')';
                html += '<option value="' + it.id + '" data-price="' + defaultPrice(it) + '"' + (selectedId == it.id ? ' selected' : '') + '>' + label + '</option>';
            }
        });
        return html;
    }

    function addRow(itemId, price) {
        const idx = tbody.querySelectorAll('tr.line-row').length;
        const tr = document.createElement('tr');
        tr.className = 'line-row border-b border-gray-100 hover:bg-gray-50/60';
        const item = items.find(i => i.id == itemId);
        const defPrice = item ? defaultPrice(item) : (price || 0);
        tr.innerHTML =
            '<td class="px-3 py-2"><select name="lines[' + idx + '][item_id]" class="' + fsel + '" required>' + buildItemOptions(itemId, tr) + '</select></td>' +
            '<td class="px-3 py-2"><input type="number" inputmode="decimal" step="any" min="0" name="lines[' + idx + '][price]" class="line-price ' + finp + '" value="' + (price != null ? price : defPrice) + '" required></td>' +
            '<td class="px-2 py-2"><button type="button" class="remove-line inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50">×</button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.line-item').addEventListener('change', function() {
            const opt = this.selectedOptions[0];
            const priceEl = tr.querySelector('.line-price');
            if (opt && opt.dataset.price) priceEl.value = opt.dataset.price;
        });
        tr.querySelector('.remove-line').addEventListener('click', function() { tr.remove(); });
    }

    addBtn.addEventListener('click', function() { addRow(null, null); });

    tbody.querySelectorAll('.remove-line').forEach(function(btn) {
        btn.addEventListener('click', function() { btn.closest('tr').remove(); });
    });

    if (tbody.querySelectorAll('tr.line-row').length === 0) addRow(null, null);
})();
</script>
@endsection
