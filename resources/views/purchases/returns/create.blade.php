@extends('layouts.app')

@section('title', 'مرتجع مشتريات جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.returns.index') }}" class="text-gray-500 hover:text-indigo-600">مرتجعات المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مرتجع جديد</span>
@endsection

@section('content')
@php
    $purchaseReturnSupplierOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim($s->getLocalizedDisplayName().' ('.($s->code ?? '').')'),
    ])->all();
    $purchaseReturnWarehouseOptions = $warehouses->map(fn ($w) => [
        'value' => $w->id,
        'label' => trim((string) ($w->name_ar ?? $w->name_en ?? $w->code ?? '')),
    ])->all();
    $returnTypeOptions = collect($returnTypes)->map(fn ($rt) => ['value' => $rt, 'label' => $rt])->all();
@endphp
<div class="max-w-full" dir="rtl" x-data="purchaseReturnForm()" x-cloak>
    @if(session('error'))
        <div class="erp-alert-error mb-4">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">مرتجع مشتريات جديد</h1>
        </div>
        <a href="{{ route('purchases.returns.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">رجوع</a>
    </div>

    <form id="purchase-return-form" method="POST" action="{{ route('purchases.returns.store') }}">
        @csrf
        <input type="hidden" name="purchase_invoice_id" id="purchase_invoice_id" :value="selectedInvoiceId">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل المرتجع</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-info field="procurement.purchase_return_supplier" for="supplier_id-trigger" class="block text-sm font-medium text-gray-700 mb-1">المورد <span class="text-red-500">*</span></x-info>
                    <x-searchable-select class="w-full" name="supplier_id" id="supplier_id" :options="$purchaseReturnSupplierOptions" :value="old('supplier_id')" :required="true" empty-label="اختر المورد" placeholder="ابحث باسم المورد..." />
                </div>
                <div>
                    <x-info field="procurement.purchase_return_invoice" class="block text-sm font-medium text-gray-700 mb-1">فاتورة المشتريات</x-info>
                    <select id="invoice_select" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" @change="onInvoiceChange($event)">
                        <option value="">— بدون ربط —</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">اختياري: يحمّل بنود الفاتورة ويحدّث رصيدها بعد الترحيل.</p>
                </div>
                <div>
                    <x-info field="procurement.purchase_return_warehouse" for="warehouse_id-trigger" class="block text-sm font-medium text-gray-700 mb-1">المستودع <span class="text-red-500">*</span></x-info>
                    <x-searchable-select class="w-full" name="warehouse_id" id="warehouse_id" :options="$purchaseReturnWarehouseOptions" :value="old('warehouse_id')" :required="true" empty-label="اختر المستودع" placeholder="ابحث عن مستودع..." />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الإرجاع <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <x-info field="procurement.purchase_return_reason" for="reason_type-trigger" class="block text-sm font-medium text-gray-700 mb-1">نوع الإرجاع <span class="text-red-500">*</span></x-info>
                    <x-searchable-select class="w-full" name="reason_type" id="reason_type" :options="$returnTypeOptions" :value="old('reason_type', $returnTypes[0] ?? '')" :required="true" :searchable="false" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العملة</label>
                    <x-searchable-select class="w-full" name="currency" id="currency" :options="[['value' => 'SAR', 'label' => 'SAR'], ['value' => 'USD', 'label' => 'USD'], ['value' => 'EUR', 'label' => 'EUR']]" :value="old('currency', 'SAR')" :searchable="false" />
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">السبب التفصيلي</label>
                    <textarea name="reason" rows="2" placeholder="أدخل سبب الإرجاع..." class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" dir="rtl">{{ old('reason') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود المرتجع</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100 transition" x-show="!selectedInvoiceId">
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right min-w-[780px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[140px]">الصنف</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-24">سعر الوحدة</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-20">ض.ق.م %</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-28">الحالة</th>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[90px]">إجمالي</th>
                            <th class="py-2 px-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-2 px-2">
                                    <input type="hidden" :name="'lines['+index+'][purchase_invoice_item_id]'" :value="line.purchase_invoice_item_id || ''">
                                    <template x-if="selectedInvoiceId">
                                        <span class="block px-2 py-2 text-gray-800" x-text="line.item_label"></span>
                                        <input type="hidden" :name="'lines['+index+'][item_id]'" :value="line.item_id">
                                    </template>
                                    <template x-if="!selectedInvoiceId">
                                        <select :name="'lines['+index+'][item_id]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.item_id" @change="onItemSelect(index)">
                                            <option value="">اختر</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-cost="{{ $item->cost ?? 0 }}">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</option>
                                            @endforeach
                                        </select>
                                    </template>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" step="any" min="0.0001" :max="line.max_returnable || null" :name="'lines['+index+'][quantity]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.quantity" @input="calcLineTotal(index)">
                                    <p class="text-xs text-gray-400 mt-0.5" x-show="line.max_returnable" x-text="'حد أقصى: ' + line.max_returnable"></p>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" step="any" min="0" :name="'lines['+index+'][unit_price]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.unit_price" @input="calcLineTotal(index)" :readonly="!!selectedInvoiceId">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" step="any" min="0" max="100" :name="'lines['+index+'][vat_percent]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.vat_percent" @input="calcLineTotal(index)" :readonly="!!selectedInvoiceId">
                                </td>
                                <td class="py-2 px-2">
                                    <select :name="'lines['+index+'][line_status]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.line_status">
                                        @foreach($lineStatuses as $ls)
                                            <option value="{{ $ls }}">{{ $ls }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <span class="block px-2 py-2 font-medium text-gray-700" x-text="formatMoney(lineTotal(index))"></span>
                                </td>
                                <td class="py-2 px-2 text-left">
                                    <button type="button" @click="removeLine(index)" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" x-show="!selectedInvoiceId && lines.length > 1" title="حذف">×</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">الخلاصة المالية</h2>
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-2 text-left">
                    <div class="flex justify-between text-sm"><span class="text-gray-600">المجموع الفرعي</span><span class="font-medium" x-text="formatMoney(subtotal)"></span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-600">ضريبة القيمة المضافة</span><span class="font-medium" x-text="formatMoney(vatAmount)"></span></div>
                    <div class="flex justify-between pt-2 border-t border-gray-200"><span class="font-bold">الإجمالي</span><span class="text-lg font-bold" x-text="formatMoney(grandTotal)"></span></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                    <textarea name="internal_notes" rows="2" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm">{{ old('internal_notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 justify-end">
            <a href="{{ route('purchases.returns.index') }}" class="px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm">ترحيل المرتجع</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const lineStatuses = @json($lineStatuses);
    const defaultVat = {{ (float) $defaultVatPercent }};
    const invoicesUrl = @json(route('purchases.returns.invoices-by-supplier'));
    const invoiceItemsBase = @json(url('purchases/returns/invoice-items'));

    window.purchaseReturnForm = function() {
        return {
            selectedInvoiceId: '',
            lines: [{ item_id: '', quantity: 1, unit_price: 0, vat_percent: defaultVat, line_status: lineStatuses[0] || 'معيب', purchase_invoice_item_id: null, max_returnable: null, item_label: '' }],
            addLine() {
                this.lines.push({ item_id: '', quantity: 1, unit_price: 0, vat_percent: defaultVat, line_status: lineStatuses[0] || 'معيب', purchase_invoice_item_id: null, max_returnable: null, item_label: '' });
            },
            removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
            onItemSelect(index) {
                const sel = document.querySelector(`select[name="lines[${index}][item_id]"]`);
                if (!sel?.selectedOptions[0]) return;
                const cost = parseFloat(sel.selectedOptions[0].getAttribute('data-cost')) || 0;
                if (cost) this.lines[index].unit_price = cost;
            },
            lineTotal(index) {
                const l = this.lines[index];
                const net = (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0);
                return net + net * (parseFloat(l.vat_percent) || 0) / 100;
            },
            calcLineTotal() {},
            get subtotal() {
                return this.lines.reduce((s, l) => s + (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0), 0);
            },
            get vatAmount() {
                return this.lines.reduce((s, l) => {
                    const net = (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0);
                    return s + net * (parseFloat(l.vat_percent) || 0) / 100;
                }, 0);
            },
            get grandTotal() { return this.subtotal + this.vatAmount; },
            formatMoney(v) { return 'SAR ' + (parseFloat(v) || 0).toFixed(2); },
            onInvoiceChange(e) {
                this.selectedInvoiceId = e.target.value || '';
                if (!this.selectedInvoiceId) {
                    this.lines = [{ item_id: '', quantity: 1, unit_price: 0, vat_percent: defaultVat, line_status: lineStatuses[0] || 'معيب', purchase_invoice_item_id: null, max_returnable: null, item_label: '' }];
                    return;
                }
                fetch(invoiceItemsBase + '/' + encodeURIComponent(this.selectedInvoiceId), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.warehouse_id) {
                            window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'warehouse_id', value: String(data.warehouse_id) } }));
                        }
                        this.lines = (data.items || []).map(row => ({
                            item_id: row.item_id,
                            item_label: row.item_name,
                            purchase_invoice_item_id: row.purchase_invoice_item_id,
                            quantity: Math.min(1, row.max_returnable),
                            max_returnable: row.max_returnable,
                            unit_price: row.unit_price,
                            vat_percent: row.vat_percent || defaultVat,
                            line_status: lineStatuses[0] || 'معيب',
                        }));
                        if (this.lines.length === 0) alert('لا توجد بنود قابلة للإرجاع في هذه الفاتورة.');
                    });
            },
            init() {
                const supplierInput = document.querySelector('input[name="supplier_id"]');
                const invoiceSelect = document.getElementById('invoice_select');
                const loadInvoices = () => {
                    const sid = supplierInput?.value;
                    invoiceSelect.innerHTML = '<option value="">— بدون ربط —</option>';
                    this.selectedInvoiceId = '';
                    if (!sid) return;
                    fetch(invoicesUrl + '?supplier_id=' + encodeURIComponent(sid), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => {
                            (data.invoices || []).forEach(inv => {
                                const opt = document.createElement('option');
                                opt.value = inv.id;
                                opt.textContent = inv.label;
                                invoiceSelect.appendChild(opt);
                            });
                        });
                };
                supplierInput?.addEventListener('change', loadInvoices);
                window.addEventListener('searchable-select-change', e => {
                    if (e.detail?.name === 'supplier_id') loadInvoices();
                });
            }
        };
    };
});
</script>
@endpush
@endsection
