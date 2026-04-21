@extends('layouts.app')

@section('title', 'أمر عمل جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-blue-600">التصنيع</a>
    <span>›</span>
    <a href="{{ route('manufacturing.runs.index') }}" class="text-gray-500 hover:text-blue-600">أوامر العمل</a>
    <span>›</span>
    <span class="text-gray-900 font-semibold">أمر عمل جديد</span>
@endsection

@section('content')
@php
    $oldLines = old('lines');
    if (! is_array($oldLines)) {
        $oldLines = [];
    }
    $woBomOptions = $bomLists->map(fn ($b) => ['value' => $b->id, 'label' => $b->name.' — '.$b->version])->all();
    $woWhOptions = $warehouses->map(fn ($w) => ['value' => $w->id, 'label' => $w->code.' — '.$w->name_ar])->all();
    $woMachineOptions = $machines->map(fn ($m) => ['value' => $m->id, 'label' => $m->code.' — '.$m->name_ar])->all();
@endphp
<div class="max-w-5xl mx-auto" dir="rtl">
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-6 mb-6">
        <div class="flex items-start gap-3">
            <a href="{{ route('manufacturing.runs.index') }}" class="mt-1 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 shrink-0" title="رجوع">
                <span class="text-lg leading-none" aria-hidden="true">→</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                    أمر عمل جديد
                    <x-info field="manufacturing.create_intro" />
                </h1>
                <p class="text-sm text-gray-500 mt-1">إنشاء أمر عمل تصنيع جديد — يُحفظ كمسودة حتى الترحيل</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('manufacturing.store') }}" class="space-y-6" id="wo-create-form">
        @csrf

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 md:p-8">
            <h2 class="text-base font-semibold text-gray-900 mb-6 pb-3 border-b border-gray-100 inline-flex items-center gap-1">
                تفاصيل أمر العمل
                <x-info field="manufacturing.wo_details_card" />
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">قائمة المواد <x-info field="manufacturing.wo_field_bom_list" /></span>
                    </label>
                    <x-custom-select
                        name="bom_list_id"
                        id="wo-bom-list-id"
                        class="w-full"
                        :options="$woBomOptions"
                        :selected="old('bom_list_id')"
                        placeholder="ابحث في قوائم المواد..."
                        empty-label="اختر قائمة مواد"
                        required
                        :error="$errors->has('bom_list_id')"
                    />
                    @error('bom_list_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    @if($bomLists->isEmpty())
                        <p class="text-amber-700 text-xs mt-2">لا توجد قوائم مواد «نشطة». عرّف قائمة من <a href="{{ route('manufacturing.bom-lists.index') }}" class="underline font-medium">قوائم المواد</a>.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">المنتج <x-info field="manufacturing.wo_field_product_readonly" /></span>
                    </label>
                    <input type="text" id="wo-product-display" readonly class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right bg-gray-50 text-gray-700" placeholder="يُملأ تلقائياً من قائمة المواد">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">الكمية <x-info field="manufacturing.field_qty_produced" /></span>
                    </label>
                    <input type="number" inputmode="decimal" name="quantity_produced" id="wo-qty-produced" value="{{ old('quantity_produced', '1') }}" min="0.0001" step="any" class="w-full max-w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600 focus:border-blue-600" required>
                    @error('quantity_produced')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">المستودع <x-info field="manufacturing.field_warehouse" /></span>
                    </label>
                    <x-custom-select
                        name="warehouse_id"
                        class="w-full"
                        :options="$woWhOptions"
                        :selected="old('warehouse_id')"
                        placeholder="ابحث في المستودعات..."
                        empty-label="اختر مستودع"
                        required
                        :error="$errors->has('warehouse_id')"
                    />
                    @error('warehouse_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">تاريخ البدء <x-info field="manufacturing.wo_field_start_date" /></span>
                    </label>
                    <input type="date" name="start_date" id="wo-start-date" value="{{ old('start_date', now()->format('Y-m-d')) }}" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600 focus:border-blue-600" required>
                    @error('start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">تاريخ الاستحقاق <x-info field="manufacturing.wo_field_due_date" /></span>
                    </label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                    @error('due_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">الماكينة <x-info field="manufacturing.wo_field_machine" /></span>
                    </label>
                    <div class="max-w-xl">
                        <x-custom-select
                            name="machine_id"
                            class="w-full"
                            :options="$woMachineOptions"
                            :selected="old('machine_id')"
                            placeholder="ابحث في الماكينات..."
                            empty-label="— اختياري —"
                            :error="$errors->has('machine_id')"
                        />
                    </div>
                    @error('machine_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span class="inline-flex items-center gap-1">ملاحظات <x-info field="manufacturing.field_notes" /></span>
                    </label>
                    <textarea name="notes" rows="3" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600 focus:border-blue-600">{{ old('notes') }}</textarea>
                    @error('notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-base font-semibold text-gray-900 inline-flex items-center gap-1">
                    المكوّنات
                    <x-info field="manufacturing.wo_components_card" />
                </h2>
                <p class="text-xs text-gray-500 mt-1" id="wo-components-hint">اختر قائمة مواد لعرض المكوّنات من النظام — يمكن تعديل الكمية الفعلية ونسبة الهالك فقط.</p>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full text-sm text-right min-w-[640px]" id="wo-lines-table" hidden>
                    <thead class="text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2.5 px-2 font-medium"><span class="inline-flex items-center gap-1">المكوّن <x-info field="manufacturing.line_ingredient" /></span></th>
                            <th class="py-2.5 px-2 font-medium w-24"><span class="inline-flex items-center gap-1">الوحدة <x-info field="manufacturing.wo_col_unit" /></span></th>
                            <th class="py-2.5 px-2 font-medium w-36"><span class="inline-flex items-center gap-1">كمية مخططة <x-info field="manufacturing.wo_col_planned_qty" /></span></th>
                            <th class="py-2.5 px-2 font-medium w-28"><span class="inline-flex items-center gap-1">هدر مخطط % <x-info field="manufacturing.wo_col_planned_scrap" /></span></th>
                            <th class="py-2.5 px-2 font-medium w-36"><span class="inline-flex items-center gap-1">كمية فعلية <x-info field="manufacturing.wo_col_actual_qty" /></span></th>
                            <th class="py-2.5 px-2 font-medium w-32"><span class="inline-flex items-center gap-1">هدر فعلي % <x-info field="manufacturing.wo_col_actual_scrap" /></span></th>
                        </tr>
                    </thead>
                    <tbody id="wo-lines-body"></tbody>
                </table>
                @error('lines')<p class="text-red-600 text-xs mt-2">{{ $message }}</p>@enderror
                @error('lines.*')<p class="text-red-600 text-xs mt-2">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 pb-8">
            <a href="{{ route('manufacturing.runs.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                إنشاء
            </button>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function () {
    const BOM_PAYLOAD = @json($bomPayload);
    const OLD_LINES = @json($oldLines);
    const OLD_BOM = @json(old('bom_list_id'));
    const MERGE_OLD_LINES = @json($errors->any());

    function plannedQty(qtyPerFg, scrapPct, woQty) {
        return Math.round(qtyPerFg * woQty * (1 + scrapPct / 100) * 10000) / 10000;
    }

    function formatNum(n) {
        const s = String(n);
        if (!s.includes('.')) return s;
        return s.replace(/\.?0+$/, '');
    }

    const bomSelect = document.getElementById('wo-bom-list-id');
    const qtyInput = document.getElementById('wo-qty-produced');
    const productDisplay = document.getElementById('wo-product-display');
    const tbody = document.getElementById('wo-lines-body');
    const table = document.getElementById('wo-lines-table');
    const hint = document.getElementById('wo-components-hint');

    function oldRowMap() {
        const m = {};
        if (!MERGE_OLD_LINES || !Array.isArray(OLD_LINES)) return m;
        OLD_LINES.forEach(function (r) {
            if (r && r.bom_list_line_id != null) m[String(r.bom_list_line_id)] = r;
        });
        return m;
    }

    function renderLines() {
        const bomId = bomSelect && bomSelect.value != null && String(bomSelect.value) !== '' ? String(bomSelect.value) : '';
        const bom = bomId ? BOM_PAYLOAD[bomId] : null;
        if (!tbody || !table || !hint) return;

        if (!bom || !Array.isArray(bom.lines) || bom.lines.length === 0) {
            tbody.innerHTML = '';
            table.hidden = true;
            hint.hidden = false;
            if (productDisplay) productDisplay.value = '';
            return;
        }

        table.hidden = false;
        hint.hidden = true;
        if (productDisplay) productDisplay.value = bom.product_label || '';

        const woQty = parseFloat(qtyInput && qtyInput.value ? qtyInput.value : '1') || 1;
        const oldMap = oldRowMap();
        tbody.innerHTML = '';

        bom.lines.forEach(function (line, idx) {
            const planned = plannedQty(line.quantity_per_fg, line.scrap_percent, woQty);
            const o = oldMap[String(line.bom_list_line_id)] || {};
            const actualQty = o.quantity_consumed != null && o.quantity_consumed !== '' ? o.quantity_consumed : String(planned);
            const actualScrap = o.actual_scrap_percent != null && o.actual_scrap_percent !== '' ? o.actual_scrap_percent : String(line.scrap_percent);

            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-100 align-middle';
            tr.innerHTML =
                '<td class="py-2.5 px-2 text-gray-800">' + escapeHtml(line.label) +
                    '<input type="hidden" name="lines[' + idx + '][bom_list_line_id]" value="' + line.bom_list_line_id + '">' +
                    '<input type="hidden" name="lines[' + idx + '][ingredient_item_id]" value="' + line.ingredient_item_id + '">' +
                '</td>' +
                '<td class="py-2.5 px-2 text-gray-600">' + escapeHtml(line.unit || '—') + '</td>' +
                '<td class="py-2.5 px-2 text-gray-700 font-medium">' + formatNum(planned) + '</td>' +
                '<td class="py-2.5 px-2 text-gray-700">' + formatNum(line.scrap_percent) + '</td>' +
                '<td class="py-2.5 px-2">' +
                    '<input type="number" inputmode="decimal" name="lines[' + idx + '][quantity_consumed]" value="' + escapeAttr(actualQty) + '" min="0.0001" step="any" required class="w-full py-2 px-2 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600">' +
                '</td>' +
                '<td class="py-2.5 px-2">' +
                    '<input type="number" inputmode="decimal" name="lines[' + idx + '][actual_scrap_percent]" value="' + escapeAttr(actualScrap) + '" min="0" max="100" step="any" class="w-full py-2 px-2 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-600">' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return String(s == null ? '' : s).replace(/"/g, '&quot;');
    }

    if (bomSelect) bomSelect.addEventListener('change', renderLines);
    if (bomSelect) bomSelect.addEventListener('input', renderLines);
    if (qtyInput) qtyInput.addEventListener('input', renderLines);
    if (qtyInput) qtyInput.addEventListener('change', renderLines);

    if (OLD_BOM && bomSelect) {
        window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'wo-bom-list-id', value: String(OLD_BOM) } }));
    }
    renderLines();
})();
</script>
@endpush
@endsection
