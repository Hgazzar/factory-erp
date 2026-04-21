@extends('layouts.app')

@section('title', 'سند صرف جديد - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.payments.index') }}" class="text-gray-500 hover:text-indigo-600">سندات الصرف</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">سند جديد</span>
@endsection

@section('content')
@php
    $paymentSupplierOpts = collect($suppliers ?? [])->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->code ?? '').' — '.(string) ($s->name ?? '')),
    ])->all();
    $paymentExpenseAccountOpts = collect($expenseAccounts ?? [])->map(fn ($a) => [
        'value' => $a->id,
        'label' => trim((string) ($a->code ?? '').' — '.(string) ($a->name_ar ?? '')),
    ])->all();
    $paymentTypeOpts = [
        ['value' => 'supplier', 'label' => 'سند صرف مورد'],
        ['value' => 'expense', 'label' => 'سند صرف مصروف'],
    ];
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">سند صرف</h1>
            <p class="mt-1 text-sm text-gray-500">صرف مبالغ لمورد أو لحساب مصروف مع تسجيل القيد المحاسبي.</p>
        </div>
        <a href="{{ route('finance.payments.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
            الرجوع لسندات الصرف
        </a>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
         x-data="{
            type: '{{ old('type', 'supplier') }}',
            supplierId: '{{ old('supplier_id') }}',
            invoiceId: '{{ old('purchase_invoice_id') }}',
            invoices: [],
            loading: false,
            async loadInvoices() {
                this.invoices = [];
                if (!this.supplierId || this.type !== 'supplier') { return; }
                this.loading = true;
                try {
                    const base = '{{ route('finance.payments.supplier-purchase-invoices') }}';
                    const r = await fetch(base + '?supplier_id=' + encodeURIComponent(this.supplierId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const d = await r.json();
                    this.invoices = d.invoices || [];
                } catch (e) {
                    this.invoices = [];
                } finally {
                    this.loading = false;
                }
            }
         }"
         @custom-select-change.window="
            if (!$event.detail) return;
            if ($event.detail.name === 'supplier_id') { supplierId = $event.detail.value != null && $event.detail.value !== '' ? String($event.detail.value) : ''; invoiceId = ''; loadInvoices(); }
            if ($event.detail.name === 'type') { type = $event.detail.value || 'supplier'; if (type !== 'supplier') invoiceId = ''; }
         "
         x-init="if (type === 'supplier' && supplierId) { loadInvoices(); }">
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('finance.payments.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">نوع السند</label>
                    <x-custom-select
                        name="type"
                        class="w-full"
                        :options="$paymentTypeOpts"
                        :selected="old('type', 'supplier')"
                        :empty-option="false"
                        :error="$errors->has('type')"
                        placeholder="نوع السند..."
                    />
                    @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-3" x-show="type === 'supplier'">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="supplier_id-trigger">المورد</label>
                    <input type="hidden" name="supplier_id" id="supplier_id" x-model="supplierId">
                    <x-searchable-select
                        class="w-full"
                        omit-hidden
                        name="supplier_id"
                        id="supplier_id"
                        :options="$paymentSupplierOpts"
                        :value="old('supplier_id')"
                        :error="$errors->has('supplier_id')"
                        empty-label="— اختر المورد —"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 xl:col-span-5" x-show="type === 'supplier' && supplierId" x-cloak>
                    <label class="mb-1 block text-sm font-medium text-gray-700">فاتورة مشتريات (اختياري)</label>
                    <select name="purchase_invoice_id" x-model="invoiceId" :disabled="type !== 'supplier'"
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60 @error('purchase_invoice_id') border-red-500 @enderror">
                        <option value="">— بدون ربط بفاتورة محددة —</option>
                        <template x-for="inv in invoices" :key="inv.id">
                            <option :value="inv.id" x-text="inv.label"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" x-show="loading">جاري تحميل الفواتير ذات الرصيد…</p>
                    <p class="mt-1 text-xs text-gray-500" x-show="!loading && type === 'supplier' && supplierId && invoices.length === 0">لا توجد فواتير بها رصيد متبقٍ لهذا المورد.</p>
                    @error('purchase_invoice_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-3" x-show="type === 'expense'">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="expense_account_id-trigger">حساب المصروف</label>
                    <x-searchable-select
                        name="expense_account_id"
                        id="expense_account_id"
                        :options="$paymentExpenseAccountOpts"
                        :value="old('expense_account_id')"
                        :error="$errors->has('expense_account_id')"
                        empty-label="— اختر حساب المصروف —"
                        placeholder="ابحث بالرمز أو اسم الحساب..."
                    />
                    @error('expense_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">التاريخ</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                           class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('date') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                    @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">المبلغ</label>
                    <input type="number" inputmode="decimal" name="amount" value="{{ old('amount', 0) }}" min="0.01" step="any" required
                           class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('amount') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" maxlength="50"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('reference') border-red-500 @enderror">
                    @error('reference')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('finance.payments.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ السند</button>
            </div>
        </form>
    </div>
</div>
@endsection
