@extends('layouts.app')

@section('title', 'فاتورة مشتريات جديدة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.invoices.index') }}" class="text-gray-500 hover:text-indigo-600">فواتير الموردين</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">فاتورة جديدة</span>
@endsection

@push('styles')
<style>
    .inv-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .inv-card-title { font-weight: 600; color: #1f2937; font-size: 1rem; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
@php
    $purchaseInvoiceSupplierOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->name ?? '').' ('.(string) ($s->code ?? '').')'),
    ])->all();
@endphp
<div class="max-w-full" dir="rtl" x-data="purchaseInvoiceCreate(@js($defaultVatPercent))">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">فاتورة جديدة</h1>
        </div>
        <a href="{{ route('purchases.invoices.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="erp-alert-error mb-4">{{ session('error') }}</div>
    @endif
    <form method="POST" action="{{ route('purchases.invoices.store') }}" id="purchase-invoice-form">
        @csrf
        @if(!empty($fromPurchaseOrderId))
            <input type="hidden" name="purchase_order_id" value="{{ $fromPurchaseOrderId }}">
            <input type="hidden" name="posting_source" value="order">
        @else
            <input type="hidden" name="posting_source" value="{{ $postingSource ?? 'direct' }}">
        @endif

        {{-- تفاصيل الفاتورة --}}
        <div class="inv-card p-5 mb-6">
            <h2 class="inv-card-title">تفاصيل الفاتورة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="supplier_id-trigger">المورد <span class="text-red-500">*</span></label>
                    <x-searchable-select
                        class="w-full"
                        name="supplier_id"
                        id="supplier_id"
                        :options="$purchaseInvoiceSupplierOptions"
                        :value="old('supplier_id')"
                        :required="true"
                        empty-label="اختر المورد"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم فاتورة المورد</label>
                    <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="رقم فاتورة المورد">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="المرجع">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الفاتورة <span class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الاستحقاق <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" required value="{{ old('due_date') }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                    @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العملة</label>
                    <select name="currency" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="SAR" {{ old('currency', 'SAR') == 'SAR' ? 'selected' : '' }}>SAR</option>
                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                    </select>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">المستودع <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" required class="w-full max-w-xs px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">اختر المستودع</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar ?? $w->name_en ?? $w->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- بنود الفاتورة --}}
        <div class="inv-card p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 class="inv-card-title mb-0">بنود الفاتورة</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[140px]">المنتج</th>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[100px]">الوصف</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-20">الكمية</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-24">سعر الوحدة</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-20">الخصم</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-20">ض.ق.م %</th>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[90px]">إجمالي البند</th>
                            <th class="py-2 px-2 w-10 text-left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-2 px-2">
                                    <select :name="'lines[' + index + '][item_id]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" x-model="line.item_id" @change="onItemSelect(index)">
                                        <option value="">اختر</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-price="{{ $item->cost ?? 0 }}">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="text" :name="'lines[' + index + '][description]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" placeholder="الوصف" x-model="line.description">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="'lines[' + index + '][quantity]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.quantity" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="'lines[' + index + '][unit_price]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.unit_price" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="'lines[' + index + '][discount]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.discount" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" max="100" :name="'lines[' + index + '][vat_percent]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.vat_percent" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <span class="block px-2 py-2 text-gray-700 font-medium" x-text="formatMoney(lineTotal(index))"></span>
                                </td>
                                <td class="py-2 px-2 text-left">
                                    <button type="button" @click="removeLine(index)" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- الحسابات الإجمالية + معلومات إضافية --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="inv-card p-5 order-2 lg:order-1">
                <h2 class="inv-card-title">معلومات إضافية</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات عامة</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات عامة">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                        <textarea name="internal_notes" rows="3" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="ملاحظات داخلية">{{ old('internal_notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="inv-card p-5 order-1 lg:order-2">
                <h2 class="inv-card-title">الحسابات الإجمالية</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">المجموع الفرعي</span>
                        <span class="font-medium" x-text="formatMoney(subtotal)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الخصم</span>
                        <span class="font-medium text-red-600" x-text="formatMoney(totalDiscount) + '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ضريبة القيمة المضافة</span>
                        <span class="font-medium" x-text="formatMoney(vatAmount)"></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-900">الإجمالي النهائي</span>
                        <span class="font-bold text-lg text-gray-900" x-text="formatMoney(grandTotal)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6 justify-end">
            <a href="{{ route('purchases.invoices.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-white text-sm font-semibold transition bg-blue-600 hover:bg-blue-700 shadow-sm">حفظ</button>
        </div>
    </form>
</div>

<script>
function purchaseInvoiceCreate(defaultVatPercent) {
    const vatDef = defaultVatPercent != null ? Number(defaultVatPercent) : 15;
    return {
        lines: [{ item_id: '', description: '', quantity: 1, unit_price: 0, discount: 0, vat_percent: vatDef }],
        addLine() {
            this.lines.push({ item_id: '', description: '', quantity: 1, unit_price: 0, discount: 0, vat_percent: vatDef });
        },
        removeLine(i) {
            if (this.lines.length <= 1) return;
            this.lines.splice(i, 1);
        },
        onItemSelect(index) {
            const sel = document.querySelector(`select[name="lines[${index}][item_id]"]`);
            if (!sel?.selectedOptions[0]) return;
            const price = parseFloat(sel.selectedOptions[0].getAttribute('data-price')) || 0;
            if (price) this.lines[index].unit_price = price;
            this.calcLineTotal(index);
        },
        lineTotal(index) {
            const l = this.lines[index];
            const q = parseFloat(l.quantity) || 0;
            const p = parseFloat(l.unit_price) || 0;
            const d = parseFloat(l.discount) || 0;
            const v = parseFloat(l.vat_percent);
            const vatRate = (l.vat_percent != null && l.vat_percent !== '' && !Number.isNaN(v)) ? v : vatDef;
            const net = q * p - d;
            return net + (net * vatRate / 100);
        },
        calcLineTotal(index) {
            this.lines[index]._lineTotal = this.lineTotal(index);
        },
        get subtotal() {
            return this.lines.reduce((s, l, i) => s + (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0), 0);
        },
        get totalDiscount() {
            return this.lines.reduce((s, l) => s + (parseFloat(l.discount) || 0), 0);
        },
        get vatAmount() {
            return this.lines.reduce((s, l, i) => {
                const q = parseFloat(l.quantity) || 0;
                const p = parseFloat(l.unit_price) || 0;
                const d = parseFloat(l.discount) || 0;
                const v = parseFloat(l.vat_percent);
                const vatRate = (l.vat_percent != null && l.vat_percent !== '' && !Number.isNaN(v)) ? v : vatDef;
                const net = q * p - d;
                return s + (net * vatRate / 100);
            }, 0);
        },
        get grandTotal() {
            return this.subtotal - this.totalDiscount + this.vatAmount;
        },
        formatMoney(v) {
            const n = typeof v === 'function' ? v() : (parseFloat(v) || 0);
            return 'SAR ' + n.toFixed(2);
        }
    };
}
</script>
@endsection
