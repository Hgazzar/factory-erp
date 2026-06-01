@extends('layouts.app')

@section('title', 'إشعار ائتمان جديد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.credit-notes.index') }}" class="text-gray-500 hover:text-blue-600">إشعارات الائتمان</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إشعار جديد</span>
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
        <h1 class="text-3xl font-bold text-gray-900">إشعار ائتمان جديد</h1>
    </header>

    <form method="POST" action="{{ route('finance.credit-notes.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="status" value="draft">

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">تفاصيل إشعار الائتمان</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="customer_id-trigger" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>العميل <span class="text-red-500">*</span></span>
                        <x-info field="credit_note_customer" />
                    </label>
                    <x-searchable-select
                        name="customer_id"
                        id="customer_id"
                        :options="$creditNoteCustomerOptions"
                        :value="old('customer_id')"
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
                    <input id="date" name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="sales_invoice_id" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>فاتورة الأصل</span>
                        <x-info field="credit_note_ref" />
                    </label>
                    <select id="sales_invoice_id" name="sales_invoice_id" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">لا شيء</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" data-customer-id="{{ $invoice->customer_id }}" @selected((string) old('sales_invoice_id') === (string) $invoice->id)>
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
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
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
                        :selected="old('reason_type')"
                        :required="true"
                        :error="$errors->has('reason_type')"
                        empty-label="اختر نوع السبب"
                        placeholder="ابحث عن نوع السبب..."
                    />
                    @error('reason_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
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
            <textarea id="notes" name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.credit-notes.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                إنشاء
            </button>
        </div>
    </form>
</div>
@endsection

@php
    $defaultCreditNoteLines = old('lines', [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_percent' => $defaultVatPercent],
    ]);
@endphp

@push('scripts')
    @include('finance.partials.note-line-items-script', [
        'tableBodyId' => 'credit-note-lines',
        'addButtonId' => 'add-line-btn',
        'subtotalId' => 'subtotal-text',
        'taxId' => 'tax-text',
        'grandTotalId' => 'grand-total-text',
        'partySelectId' => 'customer_id',
        'invoiceSelectId' => 'sales_invoice_id',
        'invoicePartyAttr' => 'data-customer-id',
        'currencyLabel' => 'SAR',
        'lineDefaults' => $defaultCreditNoteLines,
        'defaultVatJs' => (float) $defaultVatPercent,
    ])
@endpush

