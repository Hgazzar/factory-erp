@extends('layouts.app')

@section('title', 'أمر بيع جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر البيع</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أمر بيع جديد</span>
@endsection

@section('content')
@php
    $salesOrderCustomerOptions = $customers->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->display_name ?? $c->name ?? ''),
    ])->all();
@endphp
<div class="max-w-full" dir="rtl" x-data="salesOrderCreateForm(@js($items), @js($warehouses), @js($initialQuotationId ?? null), @js(old('customer_id', $initialCustomerId ?? '')), @js(old('order_date', $initialOrderDate ?? now()->format('Y-m-d'))), @js(old('expected_delivery', $initialExpectedDelivery ?? '')), @js(old('lines', $initialLines ?? [])), @js($defaultVatPercent))" @searchable-select-change="if ($event.detail.name === 'customer_id') { customerId = $event.detail.value != null && $event.detail.value !== '' ? String($event.detail.value) : ''; }" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">أمر بيع جديد</h1>
        <a href="{{ route('sales.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM5.5 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.5 7.5a.5.5 0 0 1 0 1h5.793l-2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L10.293 7.5H4.5z"/></svg>
            رجوع
        </a>
    </div>

    <form id="order-form" method="POST" action="{{ route('sales.orders.store') }}" enctype="multipart/form-data" @submit.prevent="submitForm($event)">
        @csrf
        <input type="hidden" name="quotation_id" :value="quotationId || ''">

        {{-- تفاصيل الأمر --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل الأمر</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم أمر البيع</label>
                    <input type="text" value="{{ $nextOrderNumber ?? '' }}" readonly class="w-full px-3 py-2.5 pr-4 text-right bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-600 cursor-not-allowed" title="يُسجَّل تلقائياً عند الحفظ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="sales.order_linked_quotation" /> عرض السعر المرتبط</label>
                    <select x-model="quotationId" @change="onQuotationChange()" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— بدون — (أمر يدوي)</option>
                        @foreach($quotations as $q)
                            <option value="{{ $q->id }}">{{ $q->quotation_number ?? ('QT-'.str_pad((string) $q->id, 3, '0', STR_PAD_LEFT)) }} — {{ $q->customer?->display_name }}</option>
                        @endforeach
                    </select>
                    @if($quotations->isEmpty())
                        <p class="mt-1.5 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">لا توجد عروض أسعار <strong>معتمدة</strong> متاحة للربط. اعتمد عرض سعر من شاشة العروض أو أنشئ الأمر يدوياً دون اختيار.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="customer_id-trigger">العميل <span class="text-red-500">*</span></label>
                    <input type="hidden" name="customer_id" id="customer_id" required x-model="customerId">
                    <x-searchable-select
                        class="w-full"
                        omit-hidden
                        name="customer_id"
                        id="customer_id"
                        :options="$salesOrderCustomerOptions"
                        :value="old('customer_id', $initialCustomerId ?? '')"
                        :error="$errors->has('customer_id')"
                        empty-label="اختر العميل"
                        placeholder="ابحث باسم العميل..."
                    />
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الأمر <span class="text-red-500">*</span></label>
                    <label for="order-date" class="relative flex cursor-pointer">
                        <input type="date" id="order-date" name="order_date" x-model="orderDate" required class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                        </span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><x-info field="sales.order_expected_delivery" /> التسليم المتوقع <span class="text-red-500">*</span></label>
                    <label for="expected-delivery" class="relative flex cursor-pointer">
                        <input type="date" id="expected-delivery" name="expected_delivery" x-model="expectedDelivery" required class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                        </span>
                    </label>
                    @error('expected_delivery')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="المرجع">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="ملاحظات">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- بنود الأمر --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود الأمر</h2>
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    إضافة بند
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-3 font-medium text-gray-600"><x-info field="sales.order_line_col_item" /> المنتج</th>
                            <th class="py-3 px-3 font-medium text-gray-600 min-w-[120px]">الوصف</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-20">الكمية</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">سعر الوحدة</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-20">الخصم %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-20">الضريبة %</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-36">المستودع</th>
                            <th class="py-3 px-3 font-medium text-gray-600 w-28">الإجمالي</th>
                            <th class="py-3 px-3 font-medium w-12"></th>
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
                                    <input type="text" :name="`lines[${index}][description]`" x-model="line.description" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="الوصف">
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
                                <td class="py-2 px-3">
                                    <select :name="`lines[${index}][warehouse_id]`" x-model="line.warehouse_id" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">اختر (اختياري)</option>
                                        <template x-for="wh in warehouses" :key="wh.id">
                                            <option :value="wh.id" x-text="wh.name_ar || wh.code"></option>
                                        </template>
                                    </select>
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

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <x-attachment-handler
                hint-field="sales.order_attachments"
                title="مرفقات أمر البيع"
                :existing="[]"
                :show-existing="false"
                :allow-delete="true"
                help-text="مستندات اختيارية (حتى 20 ملفاً، 10 ميجابايت لكل ملف). تُحفظ مع الأمر في مجلد sales-orders بنفس أسلوب المشتريات."
            />
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="submit" :disabled="submitting" :class="submitting && 'opacity-70 cursor-wait'" class="px-5 py-2.5 rounded-lg text-white text-sm font-medium transition" style="background: #2563eb;">حفظ</button>
            <button type="submit" name="print" value="1" :disabled="submitting" :class="submitting && 'opacity-70 cursor-wait'" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">حفظ وطباعة</button>
            <a href="{{ route('sales.orders.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
        </div>
    </form>
</div>

<script>
function erpExtract422Message(data) {
    if (!data || typeof data !== 'object') return 'تعذر إتمام العملية.';
    if (data.errors && typeof data.errors === 'object') {
        const vals = Object.values(data.errors).flat().filter(Boolean);
        if (vals.length) return String(vals[0]);
    }
    if (data.message) return data.message;
    return 'تعذر إتمام العملية.';
}
async function erpExplainOrdersFetchFailure(res) {
    if (res.status === 0) {
        return 'لم تُستلم استجابة HTTP واضحة (يحدث أحياناً بعد نجاح الخادم). راجع قائمة أوامر البيع قبل إعادة الإرسال حتى لا يُنشأ أمر مكرر.';
    }
    if (res.status === 419) {
        return 'انتهت صلاحية الجلسة أو رمز الحماية. حدّث الصفحة ثم أعد المحاولة.';
    }
    if (res.status === 401) {
        return 'انتهت الجلسة. سجّل الدخول من جديد.';
    }
    let raw = '';
    try { raw = await res.text(); } catch (e) { /* ignore */ }
    if (raw) {
        try {
            const data = JSON.parse(raw);
            const m = erpExtract422Message(data);
            if (m && m !== 'تعذر إتمام العملية.') return m;
            if (data.message) return String(data.message);
        } catch (e) { /* ليست JSON */ }
    }
    return 'تعذر إتمام الحفظ (استجابة الخادم: ' + res.status + ').';
}
window.salesOrderCreateForm = function(items, warehouses, initialQuotationId, initialCustomerId, initialOrderDate, initialExpectedDelivery, initialLines, defaultVatPercent) {
        const defaultWarehouseId = (warehouses && warehouses.length) ? String(warehouses[0].id) : '';
        const vatDef = defaultVatPercent != null ? Number(defaultVatPercent) : 15;
        const emptyLine = () => ({
            item_id: '',
            description: '',
            quantity: 1,
            unit_price: 0,
            discount_percent: 0,
            tax_percent: vatDef,
            warehouse_id: defaultWarehouseId,
        });
        const today = new Date().toISOString().slice(0, 10);
        const normalizedInitialLines = Array.isArray(initialLines) && initialLines.length
            ? initialLines.map((line) => ({
                item_id: line.item_id != null ? String(line.item_id) : '',
                description: line.description || '',
                quantity: parseFloat(line.quantity) || 1,
                unit_price: parseFloat(line.unit_price) || 0,
                discount_percent: parseFloat(line.discount_percent) || 0,
                tax_percent: line.tax_percent != null && line.tax_percent !== '' ? parseFloat(line.tax_percent) : vatDef,
                warehouse_id: line.warehouse_id ? String(line.warehouse_id) : defaultWarehouseId,
            }))
            : null;
        return {
            submitting: false,
            items: items || [],
            warehouses: warehouses || [],
            quotationId: initialQuotationId ? String(initialQuotationId) : '',
            customerId: initialCustomerId ? String(initialCustomerId) : '',
            orderDate: initialOrderDate || today,
            expectedDelivery: initialExpectedDelivery || '',
            lines: normalizedInitialLines || [emptyLine()],
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
            async onQuotationChange() {
                const id = this.quotationId;
                if (!id) return;
                try {
                    const url = '{{ route("sales.quotations.for-order", ["quotation" => "__ID__"]) }}'.replace('__ID__', id);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    this.customerId = String(data.customer_id || '');
                    this.$dispatch('erp-sync-searchable', { id: 'customer_id', value: this.customerId });
                    this.orderDate = data.order_date || today;
                    this.expectedDelivery = data.expected_delivery || '';
                    if (data.items && data.items.length) {
                        this.lines = data.items.map((i) => Object.assign(emptyLine(), {
                            item_id: String(i.item_id),
                            description: i.description || '',
                            quantity: i.quantity,
                            unit_price: i.unit_price,
                            discount_percent: i.discount_percent || 0,
                            tax_percent: (i.tax_percent != null && i.tax_percent !== '') ? parseFloat(i.tax_percent) : vatDef,
                            warehouse_id: defaultWarehouseId,
                        }));
                    }
                } catch (e) { console.warn('Failed to load quotation', e); }
            },
            addLine() { this.lines.push(emptyLine()); },
            removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
            lineTotal(line) {
                const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0;
                const d = parseFloat(line.discount_percent) || 0, t = parseFloat(line.tax_percent) || 0;
                const afterDiscount = q * p * (1 - d / 100);
                return afterDiscount * (1 + t / 100);
            },
            get subtotal() {
                return this.lines.reduce((sum, line) => sum + ((parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0)), 0);
            },
            get totalDiscount() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0, d = parseFloat(line.discount_percent) || 0;
                    return sum + (q * p * d / 100);
                }, 0);
            },
            get totalTax() {
                return this.lines.reduce((sum, line) => {
                    const q = parseFloat(line.quantity) || 0, p = parseFloat(line.unit_price) || 0, d = parseFloat(line.discount_percent) || 0, t = parseFloat(line.tax_percent) || 0;
                    const afterDiscount = q * p * (1 - d / 100);
                    return sum + (afterDiscount * t / 100);
                }, 0);
            },
            get grandTotal() { return this.subtotal - this.totalDiscount + this.totalTax; },
            async submitForm(ev) {
                ev.preventDefault();
                if (this.submitting) return;
                const form = ev.currentTarget || ev.target;
                this.submitting = true;
                const fd = new FormData(form);
                const sub = ev.submitter;
                if (sub && sub.name) {
                    fd.set(sub.name, sub.value || '1');
                }
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                        },
                        credentials: 'same-origin',
                        redirect: 'manual',
                    });
                    if (res.status === 422 || res.status === 403) {
                        const data = await res.json().catch(() => ({}));
                        const msg = erpExtract422Message(data);
                        if (window.Swal) {
                            await Swal.fire({
                                icon: res.status === 403 ? 'warning' : 'error',
                                title: 'تنبيه',
                                text: msg,
                                confirmButtonText: 'حسناً',
                            });
                        } else {
                            alert(msg);
                        }
                        return;
                    }
                    if (res.status >= 300 && res.status < 400) {
                        const loc = res.headers.get('Location');
                        window.location.href = loc
                            ? new URL(loc, window.location.href).href
                            : @json(route('sales.orders.index'));
                        return;
                    }
                    if (res.ok) {
                        const ct = (res.headers.get('Content-Type') || '').toLowerCase();
                        if (ct.includes('application/json')) {
                            try {
                                const data = await res.json();
                                if (data && data.redirect) {
                                    window.location.assign(data.redirect);
                                    return;
                                }
                            } catch (e) { /* ignore */ }
                        }
                        window.location.href = res.url || @json(route('sales.orders.index'));
                        return;
                    }
                    const errMsg = await erpExplainOrdersFetchFailure(res);
                    if (window.Swal) {
                        await Swal.fire({ icon: 'error', title: 'تعذر إتمام الحفظ', text: errMsg, confirmButtonText: 'حسناً' });
                    } else {
                        alert(errMsg);
                    }
                } catch (e) {
                    const m = (e && e.message) ? e.message : 'خطأ في الاتصال';
                    if (window.Swal) {
                        await Swal.fire({ icon: 'error', text: m, confirmButtonText: 'حسناً' });
                    } else {
                        alert(m);
                    }
                } finally {
                    this.submitting = false;
                }
            },
        };
};
</script>
@endsection

