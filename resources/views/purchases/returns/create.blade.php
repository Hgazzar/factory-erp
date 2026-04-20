@extends('layouts.app')

@section('title', 'مرتجع مشتريات جديد - MIRADA ERP')

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
        'label' => trim((string) ($s->name ?? '').' ('.(string) ($s->code ?? '').')'),
    ])->all();
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
            <h1 class="text-2xl font-bold text-gray-900">مرتجع جديد</h1>
        </div>
        <a href="{{ route('purchases.returns.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">رجوع</a>
    </div>

    <form id="purchase-return-form" method="POST" action="{{ route('purchases.returns.store') }}">
        @csrf

        {{-- تفاصيل المرتجع --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل المرتجع</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="supplier_id-trigger">المورد <span class="text-red-500">*</span></label>
                    <x-searchable-select
                        class="w-full"
                        name="supplier_id"
                        id="supplier_id"
                        :options="$purchaseReturnSupplierOptions"
                        :value="old('supplier_id')"
                        :required="true"
                        empty-label="اختر المورد"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المستودع <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">اختر المستودع</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar ?? $w->name_en ?? $w->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الإرجاع <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع الإرجاع</label>
                    <select name="reason_type" required class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        @foreach($returnTypes as $rt)
                            <option value="{{ $rt }}" {{ old('reason_type') == $rt ? 'selected' : '' }}>{{ $rt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العملة</label>
                    <select name="currency" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="SAR" {{ old('currency', 'SAR') == 'SAR' ? 'selected' : '' }}>SAR</option>
                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" placeholder="المرجع" class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">السبب <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="أدخل سبب الإرجاع..." class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" dir="rtl">{{ old('reason') }}</textarea>
                </div>
            </div>
        </div>

        {{-- عناصر المرتجع --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 class="text-base font-semibold text-gray-900">عناصر المرتجع</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right min-w-[700px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[140px]">المنتج</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-28">تكلفة الوحدة</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-20">ض.ق.م %</th>
                            <th class="py-2 px-2 font-medium text-gray-600 w-28">الحالة</th>
                            <th class="py-2 px-2 font-medium text-gray-600 min-w-[90px]">إجمالي البند</th>
                            <th class="py-2 px-2 w-10 text-left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-2 px-2">
                                    <select :name="'lines['+index+'][item_id]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" x-model="line.item_id" @change="onItemSelect(index)">
                                        <option value="">اختر</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-cost="{{ $item->cost ?? 0 }}">{{ $item->name_ar ?? $item->name_en ?? $item->code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="'lines['+index+'][quantity]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.quantity" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" :name="'lines['+index+'][unit_price]'" required class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.unit_price" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" inputmode="decimal" step="any" min="0" max="100" :name="'lines['+index+'][vat_percent]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm" x-model="line.vat_percent" @input="calcLineTotal(index)">
                                </td>
                                <td class="py-2 px-2">
                                    <select :name="'lines['+index+'][line_status]'" class="w-full px-2 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" x-model="line.line_status">
                                        @foreach($lineStatuses as $ls)
                                            <option value="{{ $ls }}">{{ $ls }}</option>
                                        @endforeach
                                    </select>
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

        {{-- الخلاصة المالية --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">الخلاصة المالية</h2>
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-2 text-left">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">المجموع الفرعي</span>
                        <span class="font-medium text-gray-900" x-text="formatMoney(subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">ضريبة القيمة المضافة</span>
                        <span class="font-medium text-gray-900" x-text="formatMoney(vatAmount)"></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-900">الإجمالي</span>
                        <span class="text-lg font-bold text-gray-900" x-text="formatMoney(grandTotal)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- معلومات إضافية --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">معلومات إضافية</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" placeholder="ملاحظات عامة..." class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" dir="rtl">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                    <textarea name="internal_notes" rows="3" placeholder="ملاحظات داخلية..." class="w-full px-3 py-2.5 rounded-2xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500" dir="rtl">{{ old('internal_notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 justify-end">
            <a href="{{ route('purchases.returns.index') }}" class="px-5 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">حفظ</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const lineStatuses = @json($lineStatuses);
    window.purchaseReturnForm = function() {
        return {
            lines: [{ item_id: '', quantity: 1, unit_price: 0, vat_percent: 15, line_status: lineStatuses[0] || 'معيب' }],
            addLine() {
                this.lines.push({ item_id: '', quantity: 1, unit_price: 0, vat_percent: 15, line_status: lineStatuses[0] || 'معيب' });
            },
            removeLine(i) {
                if (this.lines.length <= 1) return;
                this.lines.splice(i, 1);
            },
            onItemSelect(index) {
                const sel = document.querySelector(`select[name="lines[${index}][item_id]"]`);
                if (!sel?.selectedOptions[0]) return;
                const cost = parseFloat(sel.selectedOptions[0].getAttribute('data-cost')) || 0;
                if (cost) this.lines[index].unit_price = cost;
                this.calcLineTotal(index);
            },
            lineTotal(index) {
                const l = this.lines[index];
                const q = parseFloat(l.quantity) || 0;
                const p = parseFloat(l.unit_price) || 0;
                const v = parseFloat(l.vat_percent) || 0;
                const net = q * p;
                return net + (net * v / 100);
            },
            calcLineTotal(index) {},
            get subtotal() {
                return this.lines.reduce((s, l, i) => s + (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0), 0);
            },
            get vatAmount() {
                return this.lines.reduce((s, l, i) => {
                    const q = parseFloat(l.quantity) || 0;
                    const p = parseFloat(l.unit_price) || 0;
                    const v = parseFloat(l.vat_percent) || 0;
                    return s + (q * p * v / 100);
                }, 0);
            },
            get grandTotal() {
                return this.subtotal + this.vatAmount;
            },
            formatMoney(v) {
                const n = typeof v === 'function' ? v() : (parseFloat(v) || 0);
                return 'SAR ' + n.toFixed(2);
            }
        };
    };
});
</script>
@endpush
@endsection
