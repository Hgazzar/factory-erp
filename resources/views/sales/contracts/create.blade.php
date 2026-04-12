@extends('layouts.app')

@section('title', 'عقد جديد - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.contracts.index') }}" class="text-gray-500 hover:text-indigo-600">العقود</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عقد جديد</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl" x-data="contractCreateForm(@js($items))" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.contracts.index') }}" class="text-gray-500 hover:text-indigo-600" title="الرجوع">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">عقد جديد</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.contracts.store') }}" class="space-y-6">
        @csrf

        {{-- تفاصيل العقد --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل العقد</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="اسم العقد" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم بالعربية</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar') }}" placeholder="اسم العقد (عربي)" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">النوع <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="service" {{ old('type', 'service') === 'service' ? 'selected' : '' }}>خدمة</option>
                        <option value="product" {{ old('type') === 'product' ? 'selected' : '' }}>منتج</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
                    <select name="customer_id" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">اختر العميل</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ البدء <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required value="{{ old('start_date', now()->format('Y-m-d')) }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الانتهاء</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">دورة الفوترة <span class="text-red-500">*</span></label>
                    <select name="billing_cycle" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="monthly" {{ old('billing_cycle', 'monthly') === 'monthly' ? 'selected' : '' }}>شهري</option>
                        <option value="quarterly" {{ old('billing_cycle') === 'quarterly' ? 'selected' : '' }}>ربع سنوي</option>
                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>سنوي</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العملة</label>
                    <select name="currency" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="SAR" {{ old('currency', 'SAR') === 'SAR' ? 'selected' : '' }}>SAR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الضريبة % <span class="text-red-500">*</span></label>
                    <input type="number" inputmode="decimal" name="tax_percent" step="any" min="0" max="100" value="{{ old('tax_percent', '10') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">أيام التذكير</label>
                    <input type="number" inputmode="decimal" name="reminder_days" min="0" step="any" value="{{ old('reminder_days', '3') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" name="auto_renew" value="1" id="auto_renew" {{ old('auto_renew') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="auto_renew" class="text-sm text-gray-700">تجديد تلقائي</label>
                </div>
                @if($warehouses->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المخزن (للفواتير)</label>
                    <select name="warehouse_id" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">—</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar ?? $w->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        {{-- بنود العقد --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود العقد</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600">المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600">الوصف</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">سعر الوحدة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الضريبة %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-32">الإجمالي</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <select :name="`lines[${index}][item_id]`" x-model="line.item_id" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">اختر المنتج</option>
                                        <template x-for="item in items" :key="item.id">
                                            <option :value="item.id" x-text="item.name_ar || item.name_en || item.code"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="text" :name="`lines[${index}][description]`" x-model="line.description" placeholder="وصف" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0.0001" step="any" :name="`lines[${index}][quantity]`" x-model.number="line.quantity" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" step="any" :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" max="100" step="any" :name="`lines[${index}][tax_percent]`" x-model.number="line.tax_percent" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3 text-gray-900 font-medium" x-text="'SAR ' + lineTotal(line).toFixed(2)"></td>
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
                        <span class="text-gray-600">الخصم</span>
                        <span class="text-gray-900">- SAR 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">الضريبة</span>
                        <span class="text-gray-900" x-text="'SAR ' + totalTax.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                        <span class="font-semibold text-gray-900">الإجمالي</span>
                        <span class="text-lg font-bold text-gray-900" x-text="'SAR ' + grandTotal.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.contracts.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">إنشاء</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    window.contractCreateForm = function(items) {
        return {
            items: items || [],
            lines: [
                { item_id: '', description: '', quantity: 1, unit_price: 0, tax_percent: 10 }
            ],
            addLine() {
                this.lines.push({ item_id: '', description: '', quantity: 1, unit_price: 0, tax_percent: 10 });
            },
            removeLine(index) {
                if (this.lines.length > 1) this.lines.splice(index, 1);
            },
            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0;
                const p = parseFloat(line.unit_price) || 0;
                const t = parseFloat(line.tax_percent) || 0;
                return (q * p) * (1 + t / 100);
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
            }
        };
    };
});
</script>
@endpush
@endsection
