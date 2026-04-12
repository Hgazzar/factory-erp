@extends('layouts.app')

@section('title', 'تسوية جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.adjustments.index') }}" class="text-gray-500 hover:text-indigo-600">تسويات المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تسوية جديدة</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تسوية جديدة</h1>
            <p class="mt-1 text-sm text-gray-500">إضافة أو خصم كميات من رصيد المستودع مع تسجيل السبب للمحاسبة</p>
        </div>
        <a href="{{ route('inventory.adjustments.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
    </header>

    @if(session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
    @endif

    <form action="{{ route('inventory.adjustments.store') }}" method="POST" id="adjustment-form" class="space-y-6">
        @csrf

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">بيانات التسوية</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.adjustment_warehouse" /> المستودع <span class="text-red-600">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('warehouse_id') border-red-500 @enderror" required>
                            <option value="">— اختر المستودع —</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.adjustment_date" /> تاريخ التسوية <span class="text-red-600">*</span></label>
                        <input type="date" name="adjustment_date" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('adjustment_date') border-red-500 @enderror" value="{{ old('adjustment_date', date('Y-m-d')) }}" required>
                        @error('adjustment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.adjustment_type" /> نوع التسوية <span class="text-red-600">*</span></label>
                        <select name="type" id="type" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('type') border-red-500 @enderror" required>
                            @foreach($types as $k => $v)
                            <option value="{{ $k }}" {{ old('type', 'add') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-3" id="cost-center-wrap">
                        <label class="mb-1 block text-sm font-medium text-gray-700" id="cost-center-label">
                            <x-info field="inventory.adjustment_cost_center" /> مركز التكلفة
                            <span class="text-red-600" id="cost-center-asterisk">*</span>
                        </label>
                        <select name="cost_center_id" id="cost_center_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('cost_center_id') border-red-500 @enderror">
                            <option value="">— اختر مركز التكلفة —</option>
                            @foreach($costCenters as $cc)
                            <option value="{{ $cc->id }}" {{ old('cost_center_id') == $cc->id ? 'selected' : '' }}>{{ $cc->name }} ({{ $cc->code }})</option>
                            @endforeach
                        </select>
                        @error('cost_center_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">إجباري عند تسوية من نوع «خصم» (تلف، هالك، عينات) لتحميل قيمة المصروف على المركز.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm font-semibold text-gray-800">عناصر التسوية</span>
                <button type="button" class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50" id="add-row">+ إضافة عنصر</button>
            </div>
            <div class="p-4 md:p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[960px] border-collapse text-sm" id="items-table">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_item" /> الصنف</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_current_balance" /> الرصيد الحالي</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_qty_line" /> كمية التسوية</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_unit_cost_line" /> السعر/التكلفة</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_total" /> الإجمالي</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_reason" /> سبب التسوية</th>
                                <th class="w-14 border-b border-gray-200 px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody"></tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap gap-6 border-t border-gray-100 pt-4 text-sm text-gray-600">
                    <span>إجمالي العناصر : <strong class="text-gray-900" id="summary-count">0</strong></span>
                    <span>إجمالي الكمية : <strong class="text-gray-900" id="summary-qty">0</strong></span>
                    <span>إجمالي القيمة : <strong class="text-gray-900" id="summary-value">0.00 SAR</strong></span>
                </div>
                <p class="mt-2 text-xs text-gray-500">نوع «خصم كمية»: لا تتجاوز كمية التسوية الرصيد المتوفر.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">ملاحظات</div>
            <div class="p-4 md:p-6">
                <textarea name="notes" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2" placeholder="ملاحظات عن التسوية...">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventory.adjustments.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">إنشاء التسوية</button>
        </div>
    </form>
</div>

<script>
(function() {
    const itemsUrl = '{{ route("inventory.adjustments.items-for-adjustment") }}';
    const reasons = @json($reasons);
    const typeDeduct = 'deduct';
    const warehouseSelect = document.getElementById('warehouse_id');
    const typeSelect = document.getElementById('type');
    const costCenterSelect = document.getElementById('cost_center_id');
    const costCenterAsterisk = document.getElementById('cost-center-asterisk');
    const tbody = document.getElementById('items-tbody');
    const addRowBtn = document.getElementById('add-row');
    const inputRow = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm';
    const selectRow = 'h-10 w-full min-w-[8rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm';

    function toggleCostCenterRequired() {
        const isDeduct = typeSelect && typeSelect.value === typeDeduct;
        if (costCenterSelect) {
            costCenterSelect.required = isDeduct;
            costCenterSelect.setCustomValidity(isDeduct && !costCenterSelect.value ? 'اختر مركز التكلفة عند تسوية الخصم.' : '');
        }
        if (costCenterAsterisk) costCenterAsterisk.style.display = isDeduct ? 'inline' : 'none';
    }
    if (typeSelect) typeSelect.addEventListener('change', toggleCostCenterRequired);
    if (costCenterSelect) costCenterSelect.addEventListener('change', function() { if (typeSelect.value === typeDeduct) costCenterSelect.setCustomValidity(''); });
    toggleCostCenterRequired();

    function getItems() {
        const wh = warehouseSelect.value;
        const type = typeSelect.value;
        if (!wh) return Promise.resolve([]);
        return fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(wh) + '&type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json());
    }

    function buildReasonOptions(selected) {
        let html = '<option value="">— اختر السبب —</option>';
        for (const [key, label] of Object.entries(reasons)) {
            html += '<option value="' + key + '"' + (selected === key ? ' selected' : '') + '>' + label + '</option>';
        }
        return html;
    }

    function updateRowItemOptions(row, items) {
        const select = row.querySelector('.item-select');
        if (!select) return;
        const cur = select.value;
        select.innerHTML = '<option value="">اختر الصنف</option>';
        items.forEach(function(it) {
            const opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = (it.name_ar || it.name_en || it.code) + (it.available_quantity != null ? ' — رصيد: ' + it.available_quantity : '');
            opt.dataset.available = it.available_quantity != null ? it.available_quantity : 0;
            opt.dataset.unitCost = it.unit_cost != null ? it.unit_cost : 0;
            if (opt.value == cur) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function updateRowDisplay(row) {
        const select = row.querySelector('.item-select');
        const spanAvail = row.querySelector('.qty-available');
        const spanCost = row.querySelector('.unit-cost-display');
        const spanTotal = row.querySelector('.line-total-display');
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const errDiv = row.querySelector('.adj-qty-error');
        if (!select || !spanAvail) return;
        const opt = select.options[select.selectedIndex];
        const available = opt && opt.dataset.available !== undefined ? parseFloat(opt.dataset.available) : 0;
        let unitCost = opt && opt.dataset.unitCost !== undefined ? parseFloat(opt.dataset.unitCost) : 0;
        if (costInput && costInput.value) unitCost = parseFloat(costInput.value) || unitCost;
        spanAvail.textContent = available;
        if (spanCost) spanCost.textContent = unitCost.toFixed(4);
        if (costInput && !costInput.value && opt && opt.dataset.unitCost !== undefined) costInput.value = opt.dataset.unitCost;
        if (qtyInput) {
            if (typeSelect.value === typeDeduct) qtyInput.setAttribute('max', available);
            const qty = parseFloat(qtyInput.value) || 0;
            if (spanTotal) spanTotal.textContent = (qty * unitCost).toFixed(2) + ' SAR';
            if (errDiv && typeSelect.value === typeDeduct) {
                if (qty > available && available >= 0) {
                    errDiv.textContent = 'الكمية تتجاوز الرصيد (' + available + ')';
                    errDiv.classList.remove('hidden');
                } else {
                    errDiv.textContent = '';
                    errDiv.classList.add('hidden');
                }
            }
        }
    }

    function updateSummary() {
        let count = 0, totalQty = 0, totalVal = 0;
        tbody.querySelectorAll('.item-row').forEach(function(row) {
            const sel = row.querySelector('.item-select');
            const qtyInput = row.querySelector('.qty-input');
            const costInput = row.querySelector('.cost-input');
            const spanCost = row.querySelector('.unit-cost-display');
            if (!sel || !sel.value) return;
            const qty = parseFloat(qtyInput && qtyInput.value ? qtyInput.value : 0) || 0;
            const cost = costInput && costInput.value ? parseFloat(costInput.value) : (spanCost ? parseFloat(spanCost.textContent) : 0) || 0;
            count++;
            totalQty += qty;
            totalVal += qty * cost;
        });
        const countEl = document.getElementById('summary-count');
        const qtyEl = document.getElementById('summary-qty');
        const valEl = document.getElementById('summary-value');
        if (countEl) countEl.textContent = count;
        if (qtyEl) qtyEl.textContent = totalQty;
        if (valEl) valEl.textContent = totalVal.toFixed(2) + ' SAR';
    }

    function bindRowEvents(row) {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        if (select) select.addEventListener('change', function() { updateRowDisplay(row); updateSummary(); });
        if (qtyInput) qtyInput.addEventListener('input', function() { updateRowDisplay(row); updateSummary(); });
        if (costInput) costInput.addEventListener('input', function() { updateRowDisplay(row); updateSummary(); });
        const remove = row.querySelector('.remove-row');
        if (remove) remove.addEventListener('click', function() { row.remove(); updateSummary(); });
    }

    function validateRowDeduct(row) {
        if (typeSelect.value !== typeDeduct) return true;
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.qty-input');
        const errDiv = row.querySelector('.adj-qty-error');
        if (!select || !qtyInput || !errDiv) return true;
        const opt = select.options[select.selectedIndex];
        const available = opt && opt.dataset.available !== undefined ? parseFloat(opt.dataset.available) : 0;
        const qty = parseFloat(qtyInput.value) || 0;
        if (qty > available) {
            errDiv.textContent = 'الكمية تتجاوز الرصيد (' + available + ')';
            errDiv.classList.remove('hidden');
            return false;
        }
        errDiv.classList.add('hidden');
        return true;
    }

    function addRow() {
        const wh = warehouseSelect.value;
        const type = typeSelect.value;
        if (!wh) {
            alert('اختر المستودع أولاً.');
            return;
        }
        getItems().then(function(items) {
            const idx = tbody.querySelectorAll('.item-row').length;
            const tr = document.createElement('tr');
            tr.className = 'item-row border-b border-gray-100 hover:bg-gray-50/60';
            tr.innerHTML =
                '<td class="px-3 py-2"><select name="items[' + idx + '][item_id]" class="item-select ' + selectRow + '" required><option value="">اختر الصنف</option></select></td>' +
                '<td class="px-3 py-2"><span class="qty-available text-gray-500">—</span></td>' +
                '<td class="px-3 py-2"><input type="number" inputmode="decimal" name="items[' + idx + '][quantity]" class="qty-input ' + inputRow + '" step="any" min="0.0001" required><div class="adj-qty-error mt-1 hidden text-xs text-red-600"></div></td>' +
                '<td class="px-3 py-2"><input type="number" inputmode="decimal" name="items[' + idx + '][unit_cost]" class="cost-input ' + inputRow + '" step="any" min="0" placeholder="0"><span class="unit-cost-display hidden">—</span></td>' +
                '<td class="px-3 py-2"><span class="line-total-display text-gray-500">0.00 SAR</span></td>' +
                '<td class="px-3 py-2"><select name="items[' + idx + '][reason]" class="' + selectRow + '">' + buildReasonOptions('') + '</select></td>' +
                '<td class="px-2 py-2"><button type="button" class="remove-row inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50">×</button></td>';
            tbody.appendChild(tr);
            updateRowItemOptions(tr, items);
            updateRowDisplay(tr);
            bindRowEvents(tr);
            reindexRows();
            updateSummary();
        });
    }

    function reindexRows() {
        tbody.querySelectorAll('.item-row').forEach(function(row, i) {
            row.querySelector('.item-select').name = 'items[' + i + '][item_id]';
            row.querySelector('.qty-input').name = 'items[' + i + '][quantity]';
            row.querySelector('.cost-input').name = 'items[' + i + '][unit_cost]';
            row.querySelector('select[name*="[reason]"]').name = 'items[' + i + '][reason]';
        });
    }

    addRowBtn.addEventListener('click', addRow);
    typeSelect.addEventListener('change', function() {
        getItems().then(function(items) {
            tbody.querySelectorAll('.item-row').forEach(function(row) {
                updateRowItemOptions(row, items);
                updateRowDisplay(row);
            });
            updateSummary();
        });
    });
    warehouseSelect.addEventListener('change', function() {
        getItems().then(function(items) {
            tbody.querySelectorAll('.item-row').forEach(function(row) {
                updateRowItemOptions(row, items);
                updateRowDisplay(row);
            });
            updateSummary();
        });
    });

    document.getElementById('adjustment-form').addEventListener('submit', function(e) {
        const rows = Array.from(tbody.querySelectorAll('.item-row'));
        let hasError = false;
        rows.forEach(function(row) {
            if (!validateRowDeduct(row)) hasError = true;
        });
        if (hasError) {
            e.preventDefault();
            alert('تصحيح الأخطاء: في نوع خصم، لا تتجاوز كمية التسوية الرصيد المتوفر.');
            return;
        }
        const filled = rows.filter(function(row) {
            const sel = row.querySelector('.item-select');
            const qty = row.querySelector('.qty-input');
            return sel && sel.value && qty && parseFloat(qty.value) > 0;
        });
        if (filled.length === 0) {
            e.preventDefault();
            alert('أضف صنفاً واحداً على الأقل مع كمية أكبر من صفر.');
            return;
        }
        rows.forEach(function(row) {
            const sel = row.querySelector('.item-select');
            const qty = row.querySelector('.qty-input');
            if (!sel || !sel.value || !qty || parseFloat(qty.value) <= 0) row.remove();
        });
        reindexRows();
    });

    if (tbody.querySelectorAll('.item-row').length === 0) addRow();
})();
</script>
@endsection
