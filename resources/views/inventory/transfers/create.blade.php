@extends('layouts.app')

@section('title', 'تحويل جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.transfers.index') }}" class="text-gray-500 hover:text-indigo-600">تحويلات المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تحويل جديد</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تحويل جديد</h1>
            <p class="mt-1 text-sm text-gray-500">نقل أصناف من مستودع مصدر إلى مستودع وجهة</p>
        </div>
        <a href="{{ route('inventory.transfers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
    </header>

    @if(session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
    @endif

    <form action="{{ route('inventory.transfers.store') }}" method="POST" id="transfer-form" class="space-y-6">
        @csrf

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">معلومات التحويل</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.transfer_source" /> المستودع المصدر <span class="text-red-600">*</span></label>
                        <select name="source_warehouse_id" id="source_warehouse_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('source_warehouse_id') border-red-500 @enderror" required>
                            <option value="">— اختر المستودع —</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('source_warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                        @error('source_warehouse_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.transfer_dest" /> المستودع الوجهة <span class="text-red-600">*</span></label>
                        <select name="dest_warehouse_id" id="dest_warehouse_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('dest_warehouse_id') border-red-500 @enderror" required>
                            <option value="">— اختر المستودع —</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('dest_warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                        @error('dest_warehouse_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.transfer_date" /> تاريخ التحويل <span class="text-red-600">*</span></label>
                        <input type="date" name="transfer_date" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('transfer_date') border-red-500 @enderror" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                        @error('transfer_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.transfer_expected_arrival" /> تاريخ الوصول المتوقع</label>
                        <input type="date" name="expected_arrival_date" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('expected_arrival_date') border-red-500 @enderror" value="{{ old('expected_arrival_date') }}">
                        @error('expected_arrival_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.transfer_reference" /> الرقم المرجعي</label>
                        <input type="text" class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-500" value="يُولّد تلقائياً (TRF-السنة-رقم)" readonly disabled>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm font-semibold text-gray-800">عناصر التحويل</span>
                <button type="button" class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50" id="add-row">+ إضافة عنصر</button>
            </div>
            <div class="p-4 md:p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[900px] border-collapse text-sm" id="items-table">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_item" /> المنتج</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_qty_available" /> الكمية المتوفرة حالياً</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_qty" /> الكمية</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_unit_cost" /> تكلفة الوحدة</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_total" /> الإجمالي</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_notes" /> ملاحظات</th>
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
                <p class="mt-2 text-xs text-gray-500">اختر المستودع المصدر أولاً ثم أضف الأصناف. لا تتجاوز الكمية المراد تحويلها الرصيد المتوفر.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800"><x-info field="inventory.transfer_notes" /> ملاحظات</div>
            <div class="p-4 md:p-6">
                <textarea name="notes" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2" placeholder="أضف ملاحظات حول هذا التحويل...">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventory.transfers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">إنشاء التحويل</button>
        </div>
    </form>
</div>

<script>
(function() {
    const itemsByWarehouseUrl = '{{ route("inventory.transfers.items-by-warehouse") }}';
    const sourceSelect = document.getElementById('source_warehouse_id');
    const destSelect = document.getElementById('dest_warehouse_id');
    const tbody = document.getElementById('items-tbody');
    const addRowBtn = document.getElementById('add-row');
    const inputRow = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm';
    const selectRow = 'h-10 w-full min-w-[8rem] rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm';

    function preventSameWarehouse() {
        const src = sourceSelect.value;
        const dest = destSelect.value;
        if (src && dest && src === dest) {
            destSelect.setCustomValidity('يجب أن يكون المستودع الوجهة مختلفاً عن المستودع المصدر.');
        } else {
            destSelect.setCustomValidity('');
        }
    }
    sourceSelect.addEventListener('change', preventSameWarehouse);
    destSelect.addEventListener('change', preventSameWarehouse);

    function getItems(warehouseId) {
        if (!warehouseId) return Promise.resolve([]);
        return fetch(itemsByWarehouseUrl + '?warehouse_id=' + encodeURIComponent(warehouseId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json());
    }

    function updateRowItemOptions(row, items) {
        const select = row.querySelector('.item-select');
        if (!select) return;
        const currentVal = select.value;
        select.innerHTML = '<option value="">اختر المنتج</option>';
        items.forEach(function(it) {
            const opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = (it.name_ar || it.name_en || it.code);
            opt.dataset.available = it.available_quantity;
            opt.dataset.unitCost = it.unit_cost != null ? it.unit_cost : 0;
            if (opt.value == currentVal) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function onSourceChange() {
        const src = sourceSelect.value;
        const dest = destSelect.value;
        if (src && dest && src === dest) destSelect.value = '';
        getItems(src).then(function(items) {
            tbody.querySelectorAll('.item-row').forEach(function(row) {
                updateRowItemOptions(row, items);
                updateRowDisplay(row);
            });
            updateSummary();
        });
    }
    sourceSelect.addEventListener('change', onSourceChange);

    function updateRowDisplay(row) {
        const select = row.querySelector('.item-select');
        const spanAvail = row.querySelector('.qty-available');
        const spanCost = row.querySelector('.unit-cost-display');
        const spanTotal = row.querySelector('.line-total-display');
        const qtyInput = row.querySelector('.qty-input');
        const errDiv = row.querySelector('.tr-qty-error');
        if (!select || !spanAvail) return;
        const opt = select.options[select.selectedIndex];
        const available = opt && opt.dataset.available !== undefined ? parseFloat(opt.dataset.available) : 0;
        const unitCost = opt && opt.dataset.unitCost !== undefined ? parseFloat(opt.dataset.unitCost) : 0;
        spanAvail.textContent = available;
        if (spanCost) spanCost.textContent = unitCost.toFixed(4);
        if (qtyInput) {
            qtyInput.setAttribute('max', available);
            const qty = parseFloat(qtyInput.value) || 0;
            if (spanTotal) spanTotal.textContent = (qty * unitCost).toFixed(2) + ' SAR';
            if (errDiv) {
                if (qty > available && available > 0) {
                    errDiv.textContent = 'الكمية تتجاوز الرصيد المتوفر (' + available + ')';
                    errDiv.classList.remove('hidden');
                } else {
                    errDiv.textContent = '';
                    errDiv.classList.add('hidden');
                }
            }
        }
    }

    function validateRowQty(row) {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.qty-input');
        const errDiv = row.querySelector('.tr-qty-error');
        if (!select || !qtyInput || !errDiv) return true;
        const opt = select.options[select.selectedIndex];
        const available = opt && opt.dataset.available !== undefined ? parseFloat(opt.dataset.available) : 0;
        const qty = parseFloat(qtyInput.value) || 0;
        if (qty > available && available >= 0) {
            errDiv.textContent = 'الكمية تتجاوز الرصيد المتوفر (' + available + ')';
            errDiv.classList.remove('hidden');
            return false;
        }
        errDiv.textContent = '';
        errDiv.classList.add('hidden');
        return true;
    }

    function updateSummary() {
        let count = 0, totalQty = 0, totalVal = 0;
        tbody.querySelectorAll('.item-row').forEach(function(row) {
            const sel = row.querySelector('.item-select');
            const qtyInput = row.querySelector('.qty-input');
            const costSpan = row.querySelector('.unit-cost-display');
            if (!sel || !sel.value) return;
            const qty = parseFloat(qtyInput && qtyInput.value ? qtyInput.value : 0) || 0;
            const cost = costSpan ? parseFloat(costSpan.textContent) || 0 : 0;
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
        if (select) select.addEventListener('change', function() { updateRowDisplay(row); updateSummary(); });
        if (qtyInput) {
            qtyInput.addEventListener('input', function() {
                updateRowDisplay(row);
                updateSummary();
            });
        }
        const remove = row.querySelector('.remove-row');
        if (remove) remove.addEventListener('click', function() { row.remove(); updateSummary(); });
    }

    function addRow() {
        const src = sourceSelect.value;
        if (!src) {
            alert('اختر المستودع المصدر أولاً.');
            return;
        }
        getItems(src).then(function(items) {
            const idx = tbody.querySelectorAll('.item-row').length;
            const tr = document.createElement('tr');
            tr.className = 'item-row border-b border-gray-100 hover:bg-gray-50/60';
            tr.innerHTML =
                '<td class="px-3 py-2"><select name="items[' + idx + '][item_id]" class="item-select ' + selectRow + '" required><option value="">اختر المنتج</option></select></td>' +
                '<td class="px-3 py-2"><span class="qty-available text-gray-500">—</span></td>' +
                '<td class="px-3 py-2"><input type="number" inputmode="decimal" name="items[' + idx + '][quantity]" class="qty-input ' + inputRow + '" step="any" min="0.0001" required><div class="tr-qty-error mt-1 hidden text-xs text-red-600"></div></td>' +
                '<td class="px-3 py-2"><span class="unit-cost-display text-gray-500">—</span></td>' +
                '<td class="px-3 py-2"><span class="line-total-display text-gray-500">0.00 SAR</span></td>' +
                '<td class="px-3 py-2"><input type="text" name="items[' + idx + '][notes]" class="' + inputRow + '" placeholder="اختياري"></td>' +
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
            const sel = row.querySelector('.item-select');
            const qty = row.querySelector('.qty-input');
            const notes = row.querySelector('input[name*="[notes]"]');
            if (sel) sel.name = 'items[' + i + '][item_id]';
            if (qty) qty.name = 'items[' + i + '][quantity]';
            if (notes) notes.name = 'items[' + i + '][notes]';
        });
    }

    addRowBtn.addEventListener('click', addRow);

    document.getElementById('transfer-form').addEventListener('submit', function(e) {
        const rows = Array.from(tbody.querySelectorAll('.item-row'));
        let hasError = false;
        rows.forEach(function(row) {
            if (!validateRowQty(row)) hasError = true;
        });
        if (hasError) {
            e.preventDefault();
            alert('تصحيح الأخطاء: لا تتجاوز الكمية المراد تحويلها الرصيد المتوفر في أي صنف.');
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
