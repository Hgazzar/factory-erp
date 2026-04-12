@extends('layouts.app')

@section('title', 'إضافة مرتجع مبيعات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.returns.index') }}" class="text-gray-500 hover:text-indigo-600">مرتجعات المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إضافة مرتجع مبيعات</span>
@endsection

@section('content')
<div class="max-w-full" x-data="salesReturnForm()" x-cloak>
    @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">إضافة مرتجع مبيعات</h1>
    </div>

    <form id="return-form" method="POST" action="{{ route('sales.returns.store') }}" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="customer_id" :value="customerId">
        <input type="hidden" name="sales_invoice_id" :value="salesInvoiceId">

        {{-- تفاصيل المرتجع --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل المرتجع</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
                    <select x-model="customerId" @change="onCustomerChange()" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">اختر العميل</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفاتورة الأصلية</label>
                    <select x-model="salesInvoiceId" @change="onInvoiceChange()" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" :disabled="!customerId">
                        <option value="">اختر فاتورة</option>
                        <template x-for="inv in invoices" :key="inv.id">
                            <option :value="inv.id" x-text="inv.label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الإرجاع <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required :value="returnDate" @change="returnDate = $event.target.value" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" placeholder="المرجع" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع السبب <span class="text-red-500">*</span></label>
                    <select name="reason_type" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">اختر نوع السبب</option>
                        @foreach($reasonTypes as $rt)
                            <option value="{{ $rt }}">{{ $rt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">السبب</label>
                    <input type="text" name="reason" placeholder="السبب" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- بنود المرتجع --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود المرتجع</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة سطر
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600">المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">سعر الوحدة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">نسبة الضريبة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">الإجمالي</th>
                            <th class="py-3 px-3 font-medium text-gray-600">سبب البند</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <input type="hidden" :name="'lines['+index+'][item_id]'" :value="line.item_id">
                                    <span class="block px-3 py-2 bg-gray-100 rounded-lg text-gray-700" x-text="line.item_name || 'اختر المنتج'"></span>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" :min="0" :max="line.max_returnable" step="any" :name="'lines['+index+'][quantity]'" x-model.number="line.quantity" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" :title="'الحد الأقصى: ' + line.max_returnable">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" step="any" :name="'lines['+index+'][unit_price]'" x-model.number="line.unit_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" max="100" step="any" :name="'lines['+index+'][tax_percent]'" x-model.number="line.tax_percent" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3 text-gray-900 font-medium" x-text="'SAR ' + lineTotal(line).toFixed(2)"></td>
                                <td class="py-2 px-3">
                                    <input type="text" :name="'lines['+index+'][line_reason]'" x-model="line.line_reason" placeholder="أدخل السبب" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 1" class="p-1.5 rounded text-red-600 hover:bg-red-50 transition" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="erp-totals-left mt-4">
                <div class="w-full max-w-xs space-y-2 text-right">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">المجموع الفرعي</span>
                        <span class="text-gray-900" x-text="'SAR ' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">الضريبة</span>
                        <span class="text-gray-900" x-text="'SAR ' + totalTax.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                        <span class="font-semibold text-gray-900">إجمالي الاسترداد:</span>
                        <span class="text-lg font-bold text-gray-900" x-text="'SAR ' + grandTotal.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ملاحظات --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">ملاحظات</h2>
            <textarea name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y min-h-[100px]" dir="rtl">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.returns.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    window.salesReturnForm = function() {
        return {
            customerId: '',
            salesInvoiceId: '',
            returnDate: new Date().toISOString().slice(0, 10),
            invoices: [],
            invoiceItems: [],
            lines: [{ item_id: '', item_name: '', quantity: 1, unit_price: 0, tax_percent: 10, line_reason: '', max_returnable: 999999 }],

            async onCustomerChange() {
                this.salesInvoiceId = '';
                this.invoiceItems = [];
                this.lines = [{ item_id: '', item_name: '', quantity: 1, unit_price: 0, tax_percent: 10, line_reason: '', max_returnable: 999999 }];
                if (!this.customerId) { this.invoices = []; return; }
                try {
                    const r = await fetch('{{ route("sales.returns.invoices-by-customer") }}?customer_id=' + encodeURIComponent(this.customerId), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.invoices = d.invoices || [];
                } catch (e) { this.invoices = []; }
            },

            async onInvoiceChange() {
                this.lines = [];
                if (!this.salesInvoiceId) {
                    this.lines = [{ item_id: '', item_name: '', quantity: 1, unit_price: 0, tax_percent: 10, line_reason: '', max_returnable: 999999 }];
                    return;
                }
                try {
                    const url = '{{ route("sales.returns.invoice-items", ["invoice" => "__ID__"]) }}'.replace('__ID__', this.salesInvoiceId);
                    const r = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    const items = d.items || [];
                    if (items.length === 0) {
                        this.lines = [{ item_id: '', item_name: '', quantity: 1, unit_price: 0, tax_percent: 10, line_reason: '', max_returnable: 0 }];
                        return;
                    }
                    this.lines = items.map(i => ({
                        item_id: i.item_id,
                        item_name: i.item_name,
                        quantity: Math.min(1, i.max_returnable),
                        unit_price: i.unit_price,
                        tax_percent: i.tax_percent || 10,
                        line_reason: '',
                        max_returnable: i.max_returnable
                    }));
                } catch (e) {
                    this.lines = [{ item_id: '', item_name: '', quantity: 1, unit_price: 0, tax_percent: 10, line_reason: '', max_returnable: 999999 }];
                }
            },

            addLine() {
                const last = this.lines[this.lines.length - 1] || {};
                this.lines.push({
                    item_id: last.item_id || '',
                    item_name: last.item_name || '',
                    quantity: 1,
                    unit_price: last.unit_price || 0,
                    tax_percent: last.tax_percent ?? 10,
                    line_reason: '',
                    max_returnable: last.max_returnable ?? 999999
                });
            },

            removeLine(index) {
                if (this.lines.length > 1) this.lines.splice(index, 1);
            },

            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0;
                const p = parseFloat(line.unit_price) || 0;
                const t = parseFloat(line.tax_percent) || 0;
                return q * p * (1 + t / 100);
            },

            get subtotal() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0;
                    const p = parseFloat(line.unit_price) || 0;
                    return sum + (q * p);
                }, 0);
            },

            get totalTax() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0;
                    const p = parseFloat(line.unit_price) || 0;
                    const t = parseFloat(line.tax_percent) || 0;
                    return sum + (q * p * t / 100);
                }, 0);
            },

            get grandTotal() {
                return this.subtotal + this.totalTax;
            },

            submitForm() {
                const form = document.getElementById('return-form');
                if (!this.customerId || !this.salesInvoiceId) {
                    alert('يرجى اختيار العميل والفاتورة الأصلية.');
                    return;
                }
                const hasValidLine = this.lines.some(l => l.item_id && (parseFloat(l.quantity) || 0) > 0);
                if (!hasValidLine) {
                    alert('يرجى إضافة بند واحد على الأقل بكمية صحيحة.');
                    return;
                }
                for (const line of this.lines) {
                    const q = parseFloat(line.quantity) || 0;
                    const max = parseFloat(line.max_returnable) ?? 999999;
                    if (line.item_id && q > max) {
                        alert('الكمية المرتجعة لا يمكن أن تتجاوز الكمية المسموح بها للصنف.');
                        return;
                    }
                }
                form.submit();
            }
        };
    };
});
</script>
@endpush
@endsection
