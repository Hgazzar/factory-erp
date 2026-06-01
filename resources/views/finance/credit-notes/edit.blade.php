@extends('layouts.app')

@section('title', 'تعديل إشعار ائتمان - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.credit-notes.index') }}" class="text-gray-500 hover:text-blue-600">إشعارات الائتمان</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل إشعار</span>
@endsection

@section('content')
@php
    $creditNoteCustomerOptions = $customers->map(fn ($customer) => [
        'value' => $customer->id,
        'label' => trim((string) ($customer->code ?? '').' - '.(string) ($customer->name_ar ?: $customer->name ?? '')),
    ])->all();
    $creditNoteReasonTypeOpts = [
        ['value' => 'مرتجع مبيعات', 'label' => 'مرتجع مبيعات'],
        ['value' => 'تسوية سعر', 'label' => 'تسوية سعر'],
        ['value' => 'خصم لاحق', 'label' => 'خصم لاحق'],
        ['value' => 'خطأ فاتورة', 'label' => 'خطأ فاتورة'],
        ['value' => 'أخرى', 'label' => 'أخرى'],
    ];
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <h1 class="text-3xl font-bold text-gray-900">تعديل إشعار ائتمان</h1>
    </header>

    <form method="POST" action="{{ route('finance.credit-notes.update', $creditNote) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">تفاصيل إشعار الائتمان</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>رقم الإشعار</span>
                        <x-info field="credit_note_number" />
                    </label>
                    <input type="text" value="{{ $creditNote->note_number }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-500" disabled>
                </div>
                <div class="space-y-1">
                    <label for="customer_id-trigger" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>العميل <span class="text-red-500">*</span></span>
                        <x-info field="credit_note_customer" />
                    </label>
                    <x-searchable-select
                        name="customer_id"
                        id="customer_id"
                        :options="$creditNoteCustomerOptions"
                        :value="old('customer_id', $creditNote->customer_id)"
                        :error="$errors->has('customer_id')"
                        empty-label="اختر العميل"
                        placeholder="ابحث باسم العميل أو الرمز..."
                    />
                    @error('customer_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="date" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>تاريخ الإصدار <span class="text-red-500">*</span></span>
                        <x-info field="credit_note_date" />
                    </label>
                    <input id="date" name="date" type="date" value="{{ old('date', optional($creditNote->date)->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="sales_invoice_id" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الفاتورة المرتبطة</span>
                        <x-info field="credit_note_ref" />
                    </label>
                    <select id="sales_invoice_id" name="sales_invoice_id" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">لا شيء</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" data-customer-id="{{ $invoice->customer_id }}" @selected((string) old('sales_invoice_id', $creditNote->sales_invoice_id) === (string) $invoice->id)>
                                {{ $invoice->reference ?: ('SINV-' . $invoice->id) }} - {{ $invoice->customer?->name_ar ?: $invoice->customer?->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sales_invoice_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="reference" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>المرجع</span>
                        <x-info field="credit_note_manual_ref" />
                    </label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference', $creditNote->reference) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="reason_type" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>نوع السبب <span class="text-red-500">*</span></span>
                        <x-info field="credit_note_reason_type" />
                    </label>
                    <x-custom-select
                        id="reason_type"
                        name="reason_type"
                        class="w-full"
                        :options="$creditNoteReasonTypeOpts"
                        :selected="old('reason_type', $creditNote->reason_type)"
                        :required="true"
                        :error="$errors->has('reason_type')"
                        empty-label="اختر نوع السبب"
                        placeholder="ابحث عن نوع السبب..."
                    />
                    @error('reason_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="original_invoice_ref" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>مرجع فاتورة الأصل</span>
                        <x-info field="credit_note_ref" />
                    </label>
                    <input id="original_invoice_ref" name="original_invoice_ref" type="text" value="{{ old('original_invoice_ref', $creditNote->original_invoice_ref) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('original_invoice_ref') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-gray-900">بنود إشعار الائتمان</h2>
                <button type="button" id="add-line-btn" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="text-base leading-none">+</span>
                    إضافة سطر
                </button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">الوصف</th>
                            <th class="px-4 py-3 text-right">الكمية</th>
                            <th class="px-4 py-3 text-right">سعر الوحدة</th>
                            <th class="px-4 py-3 text-right">نسبة الضريبة <x-info field="tax_percent" /></th>
                            <th class="px-4 py-3 text-right">الإجمالي</th>
                            <th class="px-4 py-3 text-left">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="credit-note-lines" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            @error('lines') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="erp-totals-left mt-4 max-w-sm space-y-2 text-right">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">المجموع الفرعي</span>
                    <span class="font-semibold text-gray-800"><span id="subtotal-text">SAR 0.00</span></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">الضريبة</span>
                    <span class="font-semibold text-gray-800"><span id="tax-text">SAR 0.00</span></span>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-bold text-gray-900">
                    <span>الإجمالي</span>
                    <span id="grand-total-text">SAR 0.00</span>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-2xl font-bold text-gray-900">ملاحظات</h2>
            <textarea id="notes" name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $creditNote->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.credit-notes.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const linesBody = document.getElementById('credit-note-lines');
        const addLineBtn = document.getElementById('add-line-btn');
        const subtotalText = document.getElementById('subtotal-text');
        const taxText = document.getElementById('tax-text');
        const grandTotalText = document.getElementById('grand-total-text');
        const customerSelect = document.getElementById('customer_id');
        const invoiceSelect = document.getElementById('sales_invoice_id');

        if (!linesBody || !addLineBtn) return;

        const DEFAULT_VAT = @json((float) $defaultVatPercent);

        const oldLines = @json(old('lines', $creditNote->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_percent' => (float) $item->tax_percent,
        ])->all()));

        function formatMoney(value) {
            return `SAR ${Number(value || 0).toFixed(2)}`;
        }

        function recalcTotals() {
            let subtotal = 0;
            let totalTax = 0;

            linesBody.querySelectorAll('tr').forEach((row) => {
                const qty = Number(row.querySelector('[data-field="quantity"]')?.value || 0);
                const price = Number(row.querySelector('[data-field="unit_price"]')?.value || 0);
                const taxPercent = Number(row.querySelector('[data-field="tax_percent"]')?.value || 0);

                const lineSubtotal = qty * price;
                const lineTax = lineSubtotal * taxPercent / 100;
                const lineTotal = lineSubtotal + lineTax;

                subtotal += lineSubtotal;
                totalTax += lineTax;

                const lineTotalCell = row.querySelector('[data-line-total]');
                if (lineTotalCell) {
                    lineTotalCell.textContent = formatMoney(lineTotal);
                }
            });

            subtotalText.textContent = formatMoney(subtotal);
            taxText.textContent = formatMoney(totalTax);
            grandTotalText.textContent = formatMoney(subtotal + totalTax);
        }

        function bindRowEvents(row) {
            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalcTotals);
            });

            const deleteBtn = row.querySelector('[data-delete-line]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if (linesBody.querySelectorAll('tr').length === 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            input.value = input.dataset.field === 'quantity' ? '1' : '0';
                            if (input.dataset.field === 'description') input.value = '';
                        });
                    } else {
                        row.remove();
                    }
                    reindexRows();
                    recalcTotals();
                });
            }
        }

        function reindexRows() {
            linesBody.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    const field = input.dataset.field;
                    input.name = `lines[${index}][${field}]`;
                });
            });
        }

        function buildRow(line = {}) {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <input data-field="description" type="text" value="${line.description ?? ''}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="quantity" type="number" inputmode="decimal" min="0.0001" step="any" value="${line.quantity ?? 1}" class="h-10 w-28 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="unit_price" type="number" inputmode="decimal" min="0" step="any" value="${line.unit_price ?? 0}" class="h-10 w-32 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="tax_percent" type="number" inputmode="decimal" min="0" max="100" step="any" value="${line.tax_percent ?? DEFAULT_VAT}" class="h-10 w-24 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800" data-line-total>${formatMoney(0)}</td>
                <td class="px-4 py-3">
                    <button type="button" data-delete-line class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف السطر">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            linesBody.appendChild(row);
            bindRowEvents(row);
            reindexRows();
            recalcTotals();
        }

        function filterInvoicesByCustomer() {
            const selectedCustomer = customerSelect.value;
            Array.from(invoiceSelect.options).forEach((option, idx) => {
                if (idx === 0) return;
                const optionCustomer = option.getAttribute('data-customer-id');
                option.hidden = Boolean(selectedCustomer) && optionCustomer !== selectedCustomer;
            });
            if (invoiceSelect.selectedOptions[0]?.hidden) {
                invoiceSelect.value = '';
            }
        }

        addLineBtn.addEventListener('click', () => buildRow());
        customerSelect.addEventListener('change', filterInvoicesByCustomer);

        linesBody.innerHTML = '';
        oldLines.forEach((line) => buildRow(line));
        if (!oldLines.length) {
            buildRow();
        }
        filterInvoicesByCustomer();
    })();
</script>
@endpush

