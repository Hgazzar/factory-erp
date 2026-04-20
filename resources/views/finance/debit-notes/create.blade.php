@extends('layouts.app')

@section('title', 'إشعار مديونية جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.debit-notes.index') }}" class="text-gray-500 hover:text-blue-600">إشعارات المديونية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إشعار جديد</span>
@endsection

@section('content')
@php
    $debitNoteSupplierOptions = $suppliers->map(fn ($supplier) => [
        'value' => $supplier->id,
        'label' => trim((string) ($supplier->code ?? '').' - '.(string) ($supplier->name ?? '')),
    ])->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <h1 class="text-3xl font-bold text-gray-900">إشعار مديونية جديد</h1>
    </header>

    <form method="POST" action="{{ route('finance.debit-notes.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="status" value="draft">

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">تفاصيل إشعار المديونية</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>رقم الإشعار</span>
                        <x-info field="debit_note_number" />
                    </label>
                    <input type="text" value="تلقائي (DN-YYYY-0001)" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-500" disabled>
                </div>
                <div class="space-y-1">
                    <label for="supplier_id-trigger" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>المورد <span class="text-red-500">*</span></span>
                        <x-info field="debit_note_supplier" />
                    </label>
                    <x-searchable-select
                        name="supplier_id"
                        id="supplier_id"
                        :options="$debitNoteSupplierOptions"
                        :value="old('supplier_id')"
                        :error="$errors->has('supplier_id')"
                        empty-label="اختر المورد"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="date" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>تاريخ الإصدار <span class="text-red-500">*</span></span>
                        <x-info field="debit_note_date" />
                    </label>
                    <input id="date" name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="purchase_invoice_id" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>الفاتورة المرتبطة</span>
                        <x-info field="debit_note_ref" />
                    </label>
                    <select id="purchase_invoice_id" name="purchase_invoice_id" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">لا شيء</option>
                        @foreach($purchaseInvoices as $invoice)
                            <option value="{{ $invoice->id }}" data-supplier-id="{{ $invoice->supplier_id }}" @selected((string) old('purchase_invoice_id') === (string) $invoice->id)>
                                {{ $invoice->reference ?: ('PINV-' . $invoice->id) }} - {{ $invoice->supplier?->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_invoice_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="reference" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>المرجع</span>
                        <x-info field="debit_note_manual_ref" />
                    </label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="reason_type" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>نوع السبب <span class="text-red-500">*</span></span>
                        <x-info field="debit_note_reason_type" />
                    </label>
                    <select id="reason_type" name="reason_type" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">اختر نوع السبب</option>
                        <option value="مرتجع مشتريات" @selected(old('reason_type') === 'مرتجع مشتريات')>مرتجع مشتريات</option>
                        <option value="خصم مورد" @selected(old('reason_type') === 'خصم مورد')>خصم مورد</option>
                        <option value="تسوية سعر" @selected(old('reason_type') === 'تسوية سعر')>تسوية سعر</option>
                        <option value="أخرى" @selected(old('reason_type') === 'أخرى')>أخرى</option>
                    </select>
                    @error('reason_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1">
                    <label for="original_invoice_ref" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        <span>مرجع الفاتورة الأصل</span>
                        <x-info field="debit_note_ref" />
                    </label>
                    <input id="original_invoice_ref" name="original_invoice_ref" type="text" value="{{ old('original_invoice_ref') }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('original_invoice_ref') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold text-gray-900">بنود إشعار المديونية</h2>
                <button type="button" id="debit-add-line-btn" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
                    <tbody id="debit-note-lines" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            @error('lines') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="erp-totals-left mt-4 max-w-sm space-y-2 text-right">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">المجموع الفرعي</span>
                    <span class="font-semibold text-gray-800"><span id="debit-subtotal-text">SAR 0.00</span></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">الضريبة</span>
                    <span class="font-semibold text-gray-800"><span id="debit-tax-text">SAR 0.00</span></span>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-bold text-gray-900">
                    <span>الإجمالي</span>
                    <span id="debit-grand-total-text">SAR 0.00</span>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-2xl font-bold text-gray-900">ملاحظات</h2>
            <textarea id="notes" name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.debit-notes.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">إنشاء</button>
        </div>
    </form>
</div>
@endsection

@php
    $debitLineDefaults = old('lines', [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_percent' => $defaultVatPercent],
    ]);
@endphp

@push('scripts')
    @include('finance.partials.note-line-items-script', [
        'tableBodyId' => 'debit-note-lines',
        'addButtonId' => 'debit-add-line-btn',
        'subtotalId' => 'debit-subtotal-text',
        'taxId' => 'debit-tax-text',
        'grandTotalId' => 'debit-grand-total-text',
        'partySelectId' => 'supplier_id',
        'invoiceSelectId' => 'purchase_invoice_id',
        'invoicePartyAttr' => 'data-supplier-id',
        'currencyLabel' => 'SAR',
        'lineDefaults' => $debitLineDefaults,
        'defaultVatJs' => (float) $defaultVatPercent,
    ])
@endpush

