@extends('layouts.app')

@section('title', 'عرض سعر جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.quotations.index') }}" class="text-gray-500 hover:text-indigo-600">عروض الأسعار</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عرض سعر جديد</span>
@endsection

@section('content')
@php
    $quotationCustomerOptions = $customers->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->display_name ?? $c->name ?? ''),
    ])->all();
@endphp
<div class="max-w-full" x-data="quotationCreateForm(@js($items), @js($initialLines ?? []), @js($defaultVatPercent))" x-cloak>
    {{-- عنوان الصفحة وزر الرجوع --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.quotations.index') }}" class="w-10 h-10 rounded-full border border-gray-300 bg-white flex items-center justify-center text-gray-600 hover:bg-gray-50 transition" title="الرجوع">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">عرض سعر جديد</h1>
        </div>
    </div>

    <form id="quotation-form" method="POST" action="{{ route('sales.quotations.store') }}" class="space-y-6">
        @csrf

        {{-- تفاصيل عرض السعر --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل عرض السعر</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم عرض السعر</label>
                    <input type="text" value="{{ $nextQuotationNumber ?? '' }}" readonly class="w-full py-2.5 px-4 text-right bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-600 cursor-not-allowed" title="يُسجَّل تلقائياً عند الحفظ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="customer_id-trigger">العميل <span class="text-red-500">*</span></label>
                    <x-searchable-select
                        class="w-full"
                        name="customer_id"
                        id="customer_id"
                        :options="$quotationCustomerOptions"
                        :value="old('customer_id')"
                        :required="true"
                        :error="$errors->has('customer_id')"
                        empty-label="اختر العميل"
                        placeholder="ابحث باسم العميل..."
                    />
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">تاريخ العرض</span>
                    <label for="quotation-date" class="relative flex cursor-pointer">
                        <input type="date" id="quotation-date" name="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/><path d="M2.5 3V2h11v1h-11z"/></svg>
                        </span>
                    </label>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1"><x-info field="sales.quotation_valid_until" /> صالح حتى <span class="text-red-500">*</span></span>
                    <label for="quotation-valid-until" class="relative flex cursor-pointer">
                        <input type="date" id="quotation-valid-until" name="valid_until" required value="{{ old('valid_until') }}" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="يوم/شهر/سنة">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/><path d="M2.5 3V2h11v1h-11z"/></svg>
                        </span>
                    </label>
                    @error('valid_until')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- بنود العرض --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود العرض</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600"><x-info field="sales.invoice_line_product" /> المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">سعر الوحدة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الخصم %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-24">الضريبة %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-32">إجمالي البند</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-3">
                                    <select
                                        :name="`lines[${index}][item_id]`"
                                        x-model="line.item_id"
                                        @change="onItemChange(index)"
                                        required
                                        class="w-full min-w-[12rem] px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="">اختر المنتج</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item['id'] }}">{{ ($item['name_ar'] ?? $item['code'] ?? '—') }} — {{ $item['code'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0.0001" step="any" :name="`lines[${index}][quantity]`" x-model.number="line.quantity" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" step="any" :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" inputmode="decimal" min="0" max="100" step="any" :name="`lines[${index}][discount_percent]`" x-model.number="line.discount_percent" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
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

            {{-- ملخص الحسابات --}}
            <div class="erp-totals-left mt-4">
                <div class="w-full max-w-xs space-y-2 text-right">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">المجموع الفرعي</span>
                        <span class="text-gray-900" x-text="'SAR ' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">الخصم</span>
                        <span class="text-gray-900" x-text="'SAR ' + totalDiscount.toFixed(2) + '-'"></span>
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

        {{-- التذييل: ملاحظات وشروط --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="ملاحظات للعميل">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                    <textarea name="internal_notes" rows="3" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="للاستخدام الداخلي فقط">{{ old('internal_notes') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الشروط والأحكام</label>
                    <textarea name="terms" rows="3" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="الشروط والأحكام">{{ old('terms') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.quotations.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ</button>
        </div>
    </form>
</div>

<script>
window.quotationCreateForm = function(items, initialLines, defaultVatPercent) {
        initialLines = initialLines || [];
        const vatDef = defaultVatPercent != null ? Number(defaultVatPercent) : 15;
        const emptyLine = () => ({
            item_id: '',
            quantity: 1,
            unit_price: 0,
            discount_percent: 0,
            tax_percent: vatDef,
        });
        let lines;
        if (Array.isArray(initialLines) && initialLines.length > 0) {
            lines = initialLines.map(function (l) {
                return {
                    item_id: l.item_id != null ? String(l.item_id) : '',
                    quantity: parseFloat(l.quantity) || 1,
                    unit_price: parseFloat(l.unit_price) || 0,
                    discount_percent: parseFloat(l.discount_percent) || 0,
                    tax_percent: l.tax_percent != null ? parseFloat(l.tax_percent) : vatDef,
                };
            });
        } else {
            lines = [emptyLine()];
        }
        return {
            items: items || [],
            lines: lines,
            onItemChange(index) {
                const line = this.lines[index];
                const row = (this.items || []).find((i) => String(i.id) === String(line.item_id));
                if (row) {
                    const sp = parseFloat(row.sale_price ?? 0);
                    const sell = parseFloat(row.selling_price ?? 0);
                    line.unit_price = sp > 0 ? sp : sell;
                } else {
                    line.unit_price = 0;
                }
            },
            addLine() {
                this.lines.push(emptyLine());
            },
            removeLine(index) {
                if (this.lines.length > 1) this.lines.splice(index, 1);
            },
            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0;
                const p = parseFloat(line.unit_price) || 0;
                const d = parseFloat(line.discount_percent) || 0;
                const t = parseFloat(line.tax_percent) || 0;
                const afterDiscount = q * p * (1 - d / 100);
                return afterDiscount * (1 + t / 100);
            },
            get subtotal() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0;
                    const p = parseFloat(line.unit_price) || 0;
                    return sum + (q * p);
                }, 0);
            },
            get totalDiscount() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0;
                    const p = parseFloat(line.unit_price) || 0;
                    const d = parseFloat(line.discount_percent) || 0;
                    return sum + (q * p * d / 100);
                }, 0);
            },
            get totalTax() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0;
                    const p = parseFloat(line.unit_price) || 0;
                    const d = parseFloat(line.discount_percent) || 0;
                    const t = parseFloat(line.tax_percent) || 0;
                    const afterDiscount = q * p * (1 - d / 100);
                    return sum + (afterDiscount * t / 100);
                }, 0);
            },
            get grandTotal() {
                return this.subtotal - this.totalDiscount + this.totalTax;
            }
        };
};
</script>
@endsection
