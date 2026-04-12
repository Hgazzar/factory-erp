@extends('layouts.app')

@section('title', 'إنشاء خطة أقساط - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.installments.index') }}" class="text-gray-500 hover:text-indigo-600">الأقساط</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">إنشاء</span>
@endsection

@section('content')
<div class="max-w-full" x-data="installmentPlanForm()" x-cloak>
    @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">إنشاء خطة أقساط</h1>
        <a href="{{ route('sales.installments.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
    </div>

    <form method="POST" action="{{ route('sales.installments.store') }}" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="sales_invoice_id" :value="salesInvoiceId">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">الفاتورة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
                    <select x-model="customerId" @change="onCustomerChange()" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">اختر العميل</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفاتورة <span class="text-red-500">*</span></label>
                    <select x-model="salesInvoiceId" @change="onInvoiceChange()" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" :disabled="!customerId">
                        <option value="">اختر فاتورة</option>
                        <template x-for="inv in invoices" :key="inv.id">
                            <option :value="inv.id" x-text="inv.label"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" x-show="invoiceBalance > 0" x-text="'رصيد الفاتورة: SAR ' + invoiceBalance.toFixed(2)"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود الأقساط</h2>
                <button type="button" @click="addRow()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إضافة قسط</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600">المبلغ</th>
                            <th class="py-3 px-3 font-medium text-gray-600">تاريخ الاستحقاق <span class="text-red-500">*</span></th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" step="any" min="0.01" :name="'rows['+index+'][amount]'" x-model.number="row.amount" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="0.00">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="date" :name="'rows['+index+'][due_date]'" x-model="row.due_date" required class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <button type="button" @click="removeRow(index)" x-show="rows.length > 1" class="p-1.5 rounded text-red-600 hover:bg-red-50 transition">حذف</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="erp-totals-left mt-2">
                <p class="text-sm text-gray-600 text-right">المجموع: <span x-text="'SAR ' + rowsSum.toFixed(2)"></span> <span x-show="invoiceBalance > 0 && Math.abs(rowsSum - invoiceBalance) > 0.01" class="text-red-600">(يجب أن يساوي رصيد الفاتورة)</span></p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.installments.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">إنشاء خطة الأقساط</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    window.installmentPlanForm = function() {
        return {
            customerId: '',
            salesInvoiceId: '',
            invoices: [],
            invoiceBalance: 0,
            rows: [{ due_date: '', amount: 0 }],
            async onCustomerChange() {
                this.salesInvoiceId = '';
                this.invoiceBalance = 0;
                this.rows = [{ due_date: '', amount: 0 }];
                if (!this.customerId) { this.invoices = []; return; }
                try {
                    var r = await fetch('{{ route("sales.installments.invoices-for-customer") }}?customer_id=' + encodeURIComponent(this.customerId), { headers: { 'Accept': 'application/json' } });
                    var d = await r.json();
                    this.invoices = d.invoices || [];
                } catch (e) { this.invoices = []; }
            },
            async onInvoiceChange() {
                var inv = this.invoices.find(function(i) { return i.id == this.salesInvoiceId; }.bind(this));
                this.invoiceBalance = inv ? inv.balance : 0;
            },
            addRow() {
                this.rows.push({ due_date: '', amount: 0 });
            },
            removeRow(i) {
                if (this.rows.length > 1) this.rows.splice(i, 1);
            },
            get rowsSum() {
                return this.rows.reduce(function(s, r) { return s + (parseFloat(r.amount) || 0); }, 0);
            },
            submitForm() {
                if (!this.salesInvoiceId) { alert('اختر الفاتورة.'); return; }
                if (this.rows.length === 0 || this.rows.every(function(r) { return !r.due_date || !r.amount; })) {
                    alert('أضف بنداً واحداً على الأقل بتاريخ ومبلغ.');
                    return;
                }
                if (this.invoiceBalance > 0 && Math.abs(this.rowsSum - this.invoiceBalance) > 0.01) {
                    alert('مجموع مبالغ الأقساط يجب أن يساوي رصيد الفاتورة.');
                    return;
                }
                this.$el.querySelector('form').submit();
            }
        };
    };
});
</script>
@endpush
@endsection
