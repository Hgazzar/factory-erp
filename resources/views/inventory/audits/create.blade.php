@extends('layouts.app')

@section('title', 'جرد جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.audits.index') }}" class="text-gray-500 hover:text-indigo-600">جرد المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جرد جديد</span>
@endsection

@push('styles')
<style>
    .audit-row-highlight { background-color: rgb(219 234 254) !important; transition: background-color 0.3s ease; }
</style>
@endpush

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">جرد جديد</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة عمليات الجرد الفعلي للمخزون</p>
        </div>
        <a href="{{ route('inventory.audits.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
    </header>

    <form action="{{ route('inventory.audits.store') }}" method="POST" id="audit-form" class="space-y-6">
        @csrf
        <input type="hidden" name="audit_date" id="audit_date" value="{{ old('audit_date', date('Y-m-d')) }}">
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="">
        <input type="hidden" name="type" id="type" value="full">
        <input type="hidden" name="category" id="category" value="">

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">تفاصيل الجرد</div>
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.audit_warehouse" /> المستودع <span class="text-red-600">*</span></label>
                        <select id="warehouse_select" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                            <option value="">اختر المستودع لجرد المخزون</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name_ar }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.audit_date" /> تاريخ الجرد الفعلي <span class="text-red-600">*</span></label>
                        <input type="date" id="audit_date_visible" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" value="{{ old('audit_date', date('Y-m-d')) }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" id="full_audit_toggle" checked>
                            <span class="text-sm font-medium text-gray-800"><x-info field="inventory.audit_full" /> جرد كامل</span>
                        </label>
                        <p class="mr-7 mt-1 text-xs text-gray-500">جرد جميع المنتجات في المستودع المحدد</p>
                    </div>
                    <div class="hidden md:col-span-2" id="category-wrap">
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.audit_category" /> تصنيف محدد</label>
                        <select id="category_select" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">— اختر التصنيف —</option>
                            @foreach($auditCategories as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.audit_description" /> الوصف</label>
                        <textarea name="description" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2" placeholder="أدخل وصفاً لهذا الجرد">{{ old('description') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700"><x-info field="inventory.audit_notes" /> ملاحظات</label>
                        <textarea name="notes" class="min-h-[5rem] w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" rows="2" placeholder="أدخل الملاحظات هنا">{{ old('notes') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" id="btn-generate">إنشاء الجرد</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm" id="lines-card">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm font-semibold text-gray-800">قائمة الجرد</span>
                <span class="text-xs text-gray-600">إجمالي قيمة الفروقات: <strong id="total-diff-value" class="text-sm text-gray-900">0.00 SAR</strong></span>
            </div>
            <div class="p-4 md:p-6">
                <div class="mb-4">
                    <input type="text" id="audit-table-search" class="h-10 max-w-sm rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="بحث سريع: اسم الصنف، الرمز، أو مسح الباركود...">
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[800px] border-collapse text-sm" id="lines-table">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.items_table_code" /> الرمز</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_line_name" /> اسم الصنف</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_book" /> الرصيد الدفتري</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_actual" /> الرصيد الفعلي</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_diff" /> الفرق</th>
                                <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_diff_value" /> قيمة الفرق</th>
                            </tr>
                        </thead>
                        <tbody id="lines-tbody"></tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500">أدخل الرصيد الفعلي الذي تم عدّه في المستودع. يُحسب الفرق وقيمته فوراً (أحمر للعجز، أخضر للزيادة).</p>
            </div>
        </section>

        <div class="hidden justify-end gap-2" id="submit-wrap">
            <a href="{{ route('inventory.audits.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">حفظ الجرد (مسودة)</button>
        </div>
    </form>
</div>

<script>
(function() {
    const itemsUrl = '{{ route("inventory.audits.items-for-audit") }}';
    const warehouseSelect = document.getElementById('warehouse_select');
    const fullAuditToggle = document.getElementById('full_audit_toggle');
    const categorySelect = document.getElementById('category_select');
    const categoryWrap = document.getElementById('category-wrap');
    const auditDateVisible = document.getElementById('audit_date_visible');
    const btnGenerate = document.getElementById('btn-generate');
    const linesCard = document.getElementById('lines-card');
    const submitWrap = document.getElementById('submit-wrap');
    const tbody = document.getElementById('lines-tbody');
    const totalDiffEl = document.getElementById('total-diff-value');
    const form = document.getElementById('audit-form');
    const hiddenWh = document.getElementById('warehouse_id');
    const hiddenType = document.getElementById('type');
    const hiddenCategory = document.getElementById('category');
    const hiddenDate = document.getElementById('audit_date');
    const inputActual = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';

    fullAuditToggle.addEventListener('change', function() {
        categoryWrap.classList.toggle('hidden', this.checked);
        document.getElementById('type').value = this.checked ? 'full' : 'partial';
    });

    auditDateVisible.addEventListener('change', function() {
        hiddenDate.value = auditDateVisible.value;
    });

    function getItems() {
        const wh = warehouseSelect.value;
        const type = fullAuditToggle.checked ? 'full' : 'partial';
        const cat = type === 'partial' ? categorySelect.value : '';
        if (!wh) return Promise.resolve([]);
        let url = itemsUrl + '?warehouse_id=' + encodeURIComponent(wh) + '&type=' + encodeURIComponent(type);
        if (cat) url += '&category=' + encodeURIComponent(cat);
        return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
    }

    function diffClass(val, isValue) {
        if (val > 0) return 'font-semibold text-emerald-600';
        if (val < 0) return 'font-semibold text-red-600';
        return 'text-gray-500';
    }

    function updateLineDiff(row) {
        const book = parseFloat(row.dataset.book) || 0;
        const actualInput = row.querySelector('.actual-qty');
        const actual = actualInput.value === '' ? null : parseFloat(actualInput.value);
        const cost = parseFloat(row.dataset.cost) || 0;
        const diffSpan = row.querySelector('.diff-val');
        const valueSpan = row.querySelector('.diff-value');
        if (actual === null) {
            diffSpan.textContent = '—';
            diffSpan.className = 'diff-val text-sm text-gray-500';
            valueSpan.textContent = '—';
            valueSpan.className = 'diff-value text-sm text-gray-500';
        } else {
            const diff = actual - book;
            diffSpan.textContent = diff > 0 ? '+' + diff : String(diff);
            diffSpan.className = 'diff-val text-sm ' + diffClass(diff, false);
            const val = diff * cost;
            valueSpan.textContent = (val >= 0 ? '+' : '') + val.toFixed(2) + ' SAR';
            valueSpan.className = 'diff-value text-sm ' + diffClass(val, true);
        }
        updateTotalDiff();
    }

    function updateTotalDiff() {
        let total = 0;
        tbody.querySelectorAll('tr[data-book]').forEach(function(row) {
            const actualInput = row.querySelector('.actual-qty');
            const actual = actualInput.value === '' ? null : parseFloat(actualInput.value);
            if (actual === null) return;
            const book = parseFloat(row.dataset.book) || 0;
            const cost = parseFloat(row.dataset.cost) || 0;
            total += (actual - book) * cost;
        });
        totalDiffEl.textContent = (total >= 0 ? '+' : '') + total.toFixed(2) + ' SAR';
        totalDiffEl.className = 'text-sm font-semibold ' + (total > 0 ? 'text-emerald-600' : total < 0 ? 'text-red-600' : 'text-gray-900');
    }

    btnGenerate.addEventListener('click', function() {
        const wh = warehouseSelect.value;
        const type = fullAuditToggle.checked ? 'full' : 'partial';
        if (!wh) {
            alert('اختر المستودع أولاً.');
            return;
        }
        if (type === 'partial' && !categorySelect.value) {
            alert('اختر التصنيف المحدد للجرد الجزئي.');
            return;
        }
        getItems().then(function(items) {
            if (items.length === 0) {
                alert('لا توجد أصناف في هذا المستودع' + (type === 'partial' ? ' للتصنيف المحدد' : '') + '.');
                return;
            }
            hiddenWh.value = wh;
            hiddenType.value = type;
            hiddenCategory.value = type === 'partial' ? categorySelect.value : '';
            tbody.innerHTML = '';
            items.forEach(function(it, idx) {
                const tr = document.createElement('tr');
                tr.dataset.book = it.book_quantity;
                tr.dataset.cost = it.unit_cost;
                tr.dataset.code = (it.code || '').toString();
                tr.dataset.nameAr = (it.name_ar || '').toString();
                tr.dataset.nameEn = (it.name_en || '').toString();
                tr.dataset.barcode = (it.barcode || '').toString();
                tr.classList.add('audit-row', 'border-b', 'border-gray-100', 'hover:bg-gray-50/60');
                tr.innerHTML =
                    '<td class="px-3 py-2 font-medium text-gray-900"><input type="hidden" name="lines[' + idx + '][item_id]" value="' + it.id + '"><input type="hidden" name="lines[' + idx + '][book_quantity]" value="' + it.book_quantity + '"><input type="hidden" name="lines[' + idx + '][unit_cost]" value="' + it.unit_cost + '">' + (it.code || '—') + '</td>' +
                    '<td class="px-3 py-2 text-gray-800">' + (it.name_ar || it.name_en || it.code || '—') + '</td>' +
                    '<td class="px-3 py-2 tabular-nums text-gray-800">' + it.book_quantity + '</td>' +
                    '<td class="px-3 py-2"><input type="number" inputmode="decimal" name="lines[' + idx + '][actual_quantity]" class="actual-qty ' + inputActual + '" step="any" min="0" placeholder="الرصيد الفعلي"></td>' +
                    '<td class="px-3 py-2"><span class="diff-val text-sm text-gray-500">—</span></td>' +
                    '<td class="px-3 py-2"><span class="diff-value text-sm text-gray-500">—</span></td>';
                tbody.appendChild(tr);
                tr.querySelector('.actual-qty').addEventListener('input', function() { updateLineDiff(tr); });
            });
            linesCard.classList.remove('hidden');
            submitWrap.classList.remove('hidden');
            submitWrap.classList.add('flex', 'flex-wrap', 'justify-end', 'gap-2');
            updateTotalDiff();
            applyAuditSearch();
        });
    });

    var auditSearchEl = document.getElementById('audit-table-search');
    if (auditSearchEl) {
        auditSearchEl.addEventListener('input', applyAuditSearch);
        auditSearchEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') e.preventDefault();
        });
    }
    function applyAuditSearch() {
        var q = (auditSearchEl && auditSearchEl.value ? auditSearchEl.value : '').trim().toLowerCase();
        var firstMatch = null;
        tbody.querySelectorAll('tr.audit-row').forEach(function(row) {
            var show = true;
            if (q) {
                var code = (row.dataset.code || '').toLowerCase();
                var nameAr = (row.dataset.nameAr || '').toLowerCase();
                var nameEn = (row.dataset.nameEn || '').toLowerCase();
                var barcode = (row.dataset.barcode || '').toLowerCase();
                show = code.indexOf(q) !== -1 || nameAr.indexOf(q) !== -1 || nameEn.indexOf(q) !== -1 || barcode.indexOf(q) !== -1;
            }
            row.classList.toggle('hidden', !show);
            if (show && !firstMatch) firstMatch = row;
        });
        if (firstMatch) {
            firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstMatch.classList.add('audit-row-highlight');
            setTimeout(function() { firstMatch.classList.remove('audit-row-highlight'); }, 1500);
        }
    }

    form.addEventListener('submit', function() {
        hiddenDate.value = auditDateVisible.value;
        hiddenWh.value = warehouseSelect.value;
        hiddenType.value = fullAuditToggle.checked ? 'full' : 'partial';
        hiddenCategory.value = fullAuditToggle.checked ? '' : categorySelect.value;
        let idx = 0;
        tbody.querySelectorAll('tr[data-book]').forEach(function(row) {
            row.querySelectorAll('input[name*="lines["]').forEach(function(inp) {
                inp.name = inp.name.replace(/lines\[\d+\]/, 'lines[' + idx + ']');
            });
            idx++;
        });
    });
})();
</script>
@endsection
