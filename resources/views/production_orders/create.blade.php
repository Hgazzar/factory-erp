@extends('layouts.app')

@section('title', 'أمر إنتاج جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('production-orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر الإنتاج</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جديد</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">إنشاء أمر إنتاج</h1>
            <p class="text-sm text-gray-500 mt-1">اختر المنتج التام والمواد الخام؛ يمكن الاقتراح من BOM المبدئي عند توفر مكونات محفوظة للصنف</p>
        </div>
        <a href="{{ route('production-orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">رجوع</a>
    </div>

    <form method="POST" action="{{ route('production-orders.store') }}" id="po-create-form" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">تاريخ البداية <x-info field="production.start_date" /></span>
                </label>
                <input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500">
                @error('start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">مستودع الخامات <x-info field="production.raw_materials_warehouse" /></span>
                </label>
                <x-custom-select
                    name="raw_materials_warehouse_id"
                    id="raw_materials_warehouse_id"
                    :options="$warehouseOptions"
                    :value="old('raw_materials_warehouse_id', '')"
                    :required="true"
                    :error="$errors->has('raw_materials_warehouse_id')"
                    :empty-option="true"
                    empty-label="— اختر مستودع الخامات —"
                    placeholder="ابحث باسم المستودع أو الكود..."
                />
                @error('raw_materials_warehouse_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">مستودع المنتج التام <x-info field="production.finished_goods_warehouse" /></span>
                </label>
                <x-custom-select
                    name="finished_goods_warehouse_id"
                    id="finished_goods_warehouse_id"
                    :options="$warehouseOptions"
                    :value="old('finished_goods_warehouse_id', '')"
                    :required="true"
                    :error="$errors->has('finished_goods_warehouse_id')"
                    :empty-option="true"
                    empty-label="— اختر مستودع المنتج التام —"
                    placeholder="ابحث باسم المستودع أو الكود..."
                />
                @error('finished_goods_warehouse_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">المنتج التام <x-info field="production.finished_item" /></span>
                </label>
                <select name="finished_item_id" id="finished_item_select" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">— اختر —</option>
                    @foreach($finishedGoods as $it)
                        <option value="{{ $it->id }}" @selected(old('finished_item_id') == $it->id)>{{ $it->code }} — {{ $it->name_ar }}</option>
                    @endforeach
                </select>
                @error('finished_item_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="inline-flex items-center gap-1">الكمية المخططة <x-info field="production.planned_quantity" /></span>
                </label>
                <input type="number" inputmode="decimal" name="planned_quantity" id="planned_quantity_input" value="{{ old('planned_quantity', '1') }}" min="0.0001" step="any" class="w-full max-w-xs py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                @error('planned_quantity')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="bg-indigo-50/80 border border-indigo-100 rounded-xl p-4 space-y-3">
            <label class="flex items-start gap-3 cursor-pointer text-sm text-gray-800">
                <input type="checkbox" id="bom_auto_fill" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="inline-flex flex-wrap items-center gap-1">عند تفعيله، يُعاد ملء جدول الخامات تلقائياً عند تغيير المنتج التام (إن وُجدت مكونات في BOM المبدئي). <x-info field="production.bom_auto" /></span>
            </label>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="bom-apply-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-indigo-200 bg-white text-indigo-800 text-sm font-medium hover:bg-indigo-50">
                    تطبيق اقتراح BOM
                    <x-info field="production.bom_apply" />
                </button>
                <span id="bom-message" class="text-sm text-gray-600 hidden"></span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                <span class="font-semibold text-gray-800 inline-flex items-center gap-1">المواد الخام <x-info field="production.ingredients_section" /></span>
                <button type="button" id="add-ingredient-row" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">+ إضافة سطر</button>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-sm text-right" id="ingredients-table">
                    <thead class="text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">المادة <x-info field="production.ingredient_item" /></span></th>
                            <th class="py-2 px-2 font-medium w-40"><span class="inline-flex items-center gap-1">الكمية <x-info field="production.quantity_to_consume" /></span></th>
                            <th class="py-2 px-2 font-medium w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="ingredients-body">
                        @php
                            $oldIng = old('ingredients');
                            if (! is_array($oldIng) || $oldIng === []) {
                                $oldIng = [['item_id' => '', 'quantity_to_consume' => '']];
                            }
                        @endphp
                        @foreach($oldIng as $idx => $row)
                            <tr class="ingredient-row border-b border-gray-100">
                                <td class="py-2 px-2">
                                    <select name="ingredients[{{ $idx }}][item_id]" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">— اختر —</option>
                                        @foreach($rawMaterials as $it)
                                            <option value="{{ $it->id }}" @selected(($row['item_id'] ?? '') == $it->id)>{{ $it->code }} — {{ $it->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" name="ingredients[{{ $idx }}][quantity_to_consume]" value="{{ $row['quantity_to_consume'] ?? '' }}" min="0.0001" step="any" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" class="remove-row text-red-600 hover:text-red-800 text-sm font-medium" title="حذف السطر">حذف</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @error('ingredients')<p class="text-red-600 text-xs mt-2 px-2">{{ $message }}</p>@enderror
                @error('ingredients.*.item_id')<p class="text-red-600 text-xs mt-2 px-2">{{ $message }}</p>@enderror
                @error('ingredients.*.quantity_to_consume')<p class="text-red-600 text-xs mt-2 px-2">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">حفظ أمر الإنتاج</button>
        </div>
    </form>

    <template id="ingredient-row-template">
        <tr class="ingredient-row border-b border-gray-100">
            <td class="py-2 px-2">
                <select name="ingredients[__IDX__][item_id]" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">— اختر —</option>
                    @foreach($rawMaterials as $it)
                        <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name_ar }}</option>
                    @endforeach
                </select>
            </td>
            <td class="py-2 px-2">
                <input type="number" inputmode="decimal" name="ingredients[__IDX__][quantity_to_consume]" value="" min="0.0001" step="any" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
            </td>
            <td class="py-2 px-2 text-center">
                <button type="button" class="remove-row text-red-600 hover:text-red-800 text-sm font-medium" title="حذف السطر">حذف</button>
            </td>
        </tr>
    </template>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const body = document.getElementById('ingredients-body');
    const tpl = document.getElementById('ingredient-row-template');
    const addBtn = document.getElementById('add-ingredient-row');
    const bomBase = @json($bomSuggestionsBaseUrl);
    const finishedSelect = document.getElementById('finished_item_select');
    const plannedInput = document.getElementById('planned_quantity_input');
    const bomAuto = document.getElementById('bom_auto_fill');
    const bomApplyBtn = document.getElementById('bom-apply-btn');
    const bomMessage = document.getElementById('bom-message');

    function nextIndex() {
        return body.querySelectorAll('.ingredient-row').length;
    }

    function bindRemove(row) {
        row.querySelector('.remove-row')?.addEventListener('click', function () {
            if (body.querySelectorAll('.ingredient-row').length <= 1) return;
            row.remove();
            reindexRows();
        });
    }

    function reindexRows() {
        body.querySelectorAll('.ingredient-row').forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/ingredients\[\d+\]/, 'ingredients[' + i + ']');
            });
        });
    }

    function appendEmptyRow() {
        const html = tpl.innerHTML.replace(/__IDX__/g, String(nextIndex()));
        const wrap = document.createElement('tbody');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        body.appendChild(row);
        bindRemove(row);
    }

    function clearIngredientRows() {
        body.innerHTML = '';
    }

    function setBomMessage(text, isError) {
        if (! bomMessage) return;
        bomMessage.textContent = text;
        bomMessage.classList.remove('hidden', 'text-red-600', 'text-gray-600');
        bomMessage.classList.add(isError ? 'text-red-600' : 'text-gray-600');
        if (! text) bomMessage.classList.add('hidden');
    }

    function fillFromComponents(components) {
        const planned = parseFloat(String(plannedInput?.value || '1').replace(',', '.')) || 1;
        clearIngredientRows();
        if (! components.length) {
            appendEmptyRow();
            reindexRows();
            return;
        }
        components.forEach(function (c, idx) {
            const per = parseFloat(String(c.quantity_per_unit).replace(',', '.')) || 0;
            const qty = per * planned;
            const html = tpl.innerHTML.replace(/__IDX__/g, String(idx));
            const wrap = document.createElement('tbody');
            wrap.innerHTML = html.trim();
            const row = wrap.firstElementChild;
            const sel = row.querySelector('select[name*="[item_id]"]');
            const inp = row.querySelector('input[name*="[quantity_to_consume]"]');
            if (sel) sel.value = String(c.item_id);
            if (inp) inp.value = qty > 0 ? String(qty) : '';
            body.appendChild(row);
            bindRemove(row);
        });
        reindexRows();
    }

    function fetchBomAndFill(showEmptyFallback) {
        const itemId = finishedSelect?.value;
        if (! itemId) {
            setBomMessage('اختر المنتج التام أولاً.', true);
            return;
        }
        setBomMessage('جاري التحميل…', false);
        fetch(bomBase + '/' + encodeURIComponent(itemId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (! res.ok) {
                    setBomMessage(res.j.message || 'تعذر جلب BOM.', true);
                    return;
                }
                const list = res.j.components || [];
                if (! list.length) {
                    setBomMessage('لا توجد مكونات محفوظة لهذا المنتج في BOM المبدئي؛ أدخل الخامات يدوياً.', false);
                    clearIngredientRows();
                    appendEmptyRow();
                    reindexRows();
                    return;
                }
                fillFromComponents(list);
                setBomMessage('تم تطبيق ' + list.length + ' مكوّناً (الكمية = لكل وحدة × الكمية المخططة).', false);
            })
            .catch(function () {
                setBomMessage('خطأ في الاتصال.', true);
            });
    }

    body.querySelectorAll('.ingredient-row').forEach(bindRemove);

    addBtn?.addEventListener('click', function () {
        const html = tpl.innerHTML.replace(/__IDX__/g, String(nextIndex()));
        const wrap = document.createElement('tbody');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        body.appendChild(row);
        bindRemove(row);
    });

    bomApplyBtn?.addEventListener('click', function () {
        fetchBomAndFill(true);
    });

    finishedSelect?.addEventListener('change', function () {
        if (bomAuto?.checked) {
            fetchBomAndFill(false);
        } else {
            setBomMessage('', false);
        }
    });

    plannedInput?.addEventListener('change', function () {
        if (bomAuto?.checked && finishedSelect?.value) {
            fetchBomAndFill(false);
        }
    });
})();
</script>
@endpush
