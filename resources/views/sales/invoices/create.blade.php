@extends('layouts.app')

@section('title', 'فاتورة جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.invoices.index') }}" class="text-gray-500 hover:text-indigo-600">الفواتير</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">فاتورة جديدة</span>
@endsection

@section('content')
<div class="max-w-full" x-data="invoiceCreateForm(@js($items), @js($initialLines ?? []))" x-cloak>
    {{-- عنوان الصفحة + رجوع للقائمة فقط؛ الحفظ مرة واحدة في أسفل النموذج --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">فاتورة جديدة</h1>
        <a
            href="{{ route('sales.invoices.index') }}"
            class="inline-flex items-center justify-center gap-2 min-h-[2.75rem] px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 hover:border-gray-400 transition shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            title="الخروج دون حفظ والعودة لقائمة الفواتير"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="opacity-80 shrink-0" aria-hidden="true"><path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM5.5 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/></svg>
            <span>قائمة الفواتير</span>
        </a>
    </div>

    <form id="invoice-form" method="POST" action="{{ route('sales.invoices.store') }}" @submit.prevent="submitInvoice($event)">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
        @if(!empty($fromQuotationId))
            <input type="hidden" name="quotation_id" value="{{ $fromQuotationId }}">
        @endif

        {{-- تفاصيل الفاتورة --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">تفاصيل الفاتورة</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
                    <select name="customer_id" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('customer_id') border-red-500 ring-1 ring-red-200 @enderror">
                        <option value="">اختر العميل</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $initialCustomerId ?? '') == $c->id)>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">تاريخ الإصدار</span>
                    <label for="invoice-date" class="relative flex cursor-pointer">
                        <input type="date" id="invoice-date" name="date" value="{{ old('date', $initialDate ?? now()->format('Y-m-d')) }}" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('date') border-red-500 ring-1 ring-red-200 @else border-gray-300 @enderror">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                        </span>
                    </label>
                    @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">تاريخ الاستحقاق <span class="text-red-500">*</span></span>
                    <label for="invoice-due-date" class="relative flex cursor-pointer">
                        <input type="date" id="invoice-due-date" name="due_date" value="{{ old('due_date', $initialDueDate ?? '') }}" class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('due_date') border-red-500 ring-1 ring-red-200 @else border-gray-300 @enderror" placeholder="يوم/شهر/سنة">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                        </span>
                    </label>
                    @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- بنود الفاتورة --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-900">بنود الفاتورة</h2>
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
                                    <input type="number" inputmode="decimal" min="0.0001" step="any" :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price" class="w-full px-3 py-2 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
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
            @php
                $invoiceLineErrorKeys = collect($errors->keys())->filter(fn ($k) => $k === 'lines' || str_starts_with((string) $k, 'lines.'));
            @endphp
            @if ($invoiceLineErrorKeys->isNotEmpty())
                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 space-y-1">
                    @foreach ($invoiceLineErrorKeys as $errKey)
                        @foreach ($errors->get($errKey) as $msg)
                            <p class="m-0">{{ $msg }}</p>
                        @endforeach
                    @endforeach
                </div>
            @endif

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

        {{-- ملاحظات وتذييل --}}
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

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('sales.invoices.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</a>
            <button
                type="submit"
                :disabled="submitting"
                :class="submitting && 'opacity-70 pointer-events-none cursor-wait'"
                class="inline-flex items-center justify-center gap-2 min-h-[2.75rem] px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                x-bind:aria-busy="submitting ? 'true' : 'false'"
            >
                <span x-show="!submitting">حفظ الفاتورة</span>
                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <span>جاري الحفظ…</span>
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function erpExtract422MessageInvoice(data) {
    if (!data || typeof data !== 'object') return 'تعذر إتمام العملية.';
    if (data.errors && typeof data.errors === 'object') {
        const vals = Object.values(data.errors).flat().filter(Boolean);
        if (vals.length) return String(vals[0]);
    }
    if (data.message) return data.message;
    return 'تعذر إتمام العملية.';
}
async function erpExplainInvoicesFetchFailure(res) {
    if (res.status === 0) {
        return 'لم تُستلم استجابة HTTP واضحة (يحدث أحياناً بعد نجاح الخادم). راجع قائمة الفواتير قبل إعادة الإرسال حتى لا تُنشأ فاتورة مكررة.';
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
            const m = erpExtract422MessageInvoice(data);
            if (m && m !== 'تعذر إتمام العملية.') return m;
            if (data.message) return String(data.message);
        } catch (e) { /* ليست JSON */ }
    }
    return 'تعذر إتمام الحفظ (استجابة الخادم: ' + res.status + ').';
}
window.invoiceCreateForm = function(items, initialLines) {
        initialLines = initialLines || [];
        const emptyLine = () => ({
            item_id: '',
            quantity: 1,
            unit_price: 0,
            discount_percent: 0,
            tax_percent: 10,
        });
        const linesFromQuotation = Array.isArray(initialLines) && initialLines.length > 0
            ? initialLines.map((l) => ({
                item_id: l.item_id != null ? String(l.item_id) : '',
                quantity: parseFloat(l.quantity) || 1,
                unit_price: parseFloat(l.unit_price) || 0,
                discount_percent: parseFloat(l.discount_percent) || 0,
                tax_percent: parseFloat(l.tax_percent) || 10,
            }))
            : null;
        return {
            submitting: false,
            items: items || [],
            lines: linesFromQuotation || [emptyLine()],
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
            },
            async submitInvoice(ev) {
                ev.preventDefault();
                if (this.submitting) return;
                const form = ev.currentTarget || ev.target;
                this.submitting = true;
                const fd = new FormData(form);
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
                        const msg = erpExtract422MessageInvoice(data);
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
                            : @json(route('sales.invoices.index'));
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
                        window.location.href = res.url || @json(route('sales.invoices.index'));
                        return;
                    }
                    const errMsg = await erpExplainInvoicesFetchFailure(res);
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
