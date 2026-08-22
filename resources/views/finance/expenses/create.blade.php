@extends(niche_shell_layout())

@section('title', 'إضافة مصروف - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.expenses.index') }}" class="text-gray-500 hover:text-blue-600">المصروفات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إضافة مصروف</span>
@endsection

@section('content')
@php
    $expenseAccountOptions = collect($expenseAccounts ?? [])->map(fn ($a) => [
        'value' => data_get($a, 'id'),
        'label' => trim((string) data_get($a, 'code').' - '.(string) data_get($a, 'name_ar', data_get($a, 'name', ''))),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expenseCostCenterOptions = collect($costCenters ?? [])->map(fn ($c) => [
        'value' => data_get($c, 'id'),
        'label' => trim((string) data_get($c, 'name_ar', data_get($c, 'name', ''))),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expenseSupplierOptions = collect($suppliers ?? [])->map(fn ($s) => [
        'value' => data_get($s, 'id'),
        'label' => trim(((string) data_get($s, 'code') !== '' ? (string) data_get($s, 'code').' - ' : '').(string) data_get($s, 'name', data_get($s, 'name_ar', ''))),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expenseBankAccountOptions = collect($bankAccounts ?? [])->map(fn ($b) => [
        'value' => data_get($b, 'id'),
        'label' => trim((string) data_get($b, 'bank_name').' — '.(string) data_get($b, 'account_number')),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expenseCategoryOptions = collect($categories ?? collect())->map(fn ($c) => [
        'value' => data_get($c, 'id'),
        'label' => trim((string) data_get($c, 'code').' — '.(string) data_get($c, 'name_ar', data_get($c, 'name', ''))),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expensePaymentMethodOpts = [
        ['value' => 'cash', 'label' => 'نقدًا'],
        ['value' => 'bank', 'label' => 'تحويل بنكي'],
        ['value' => 'card', 'label' => 'بطاقة'],
        ['value' => 'check', 'label' => 'شيك'],
    ];
    $expenseTaxableInitial = old('is_taxable') === '1' || old('is_taxable') === 1 || (float) old('tax_amount', 0) > 0.000001;
@endphp
<script>
window.expenseCreateTotals = window.expenseCreateTotals || function (defaultVat, amountOld, taxOld, isTaxableInitial) {
    const def = defaultVat != null ? Number(defaultVat) : 15;
    const a0 = amountOld != null ? Number(amountOld) : 0;
    const t0 = taxOld != null ? Number(taxOld) : 0;
    const initTaxable = !!(
        isTaxableInitial === true || isTaxableInitial === 1 || isTaxableInitial === '1' ||
        (typeof isTaxableInitial === 'string' && String(isTaxableInitial).toLowerCase() === 'true')
    );
    let initialPercent = def;
    if (initTaxable && a0 > 0.00001 && t0 >= 0) {
        initialPercent = Math.round((t0 / a0) * 10000) / 100;
    }
    return {
        defaultVat: def,
        isTaxable: initTaxable,
        taxPercent: initTaxable ? initialPercent : def,
        amount: a0,
        taxAmount: initTaxable ? t0 : 0,
        applyVatToTax() {
            if (!this.isTaxable) {
                this.taxAmount = 0;
                return;
            }
            const a = parseFloat(this.amount) || 0;
            const p = parseFloat(this.taxPercent);
            const pct = Number.isFinite(p) ? p : 0;
            this.taxAmount = a > 0 ? Math.round(a * (pct / 100) * 100) / 100 : 0;
        },
        onTaxableToggle() {
            if (this.isTaxable) {
                this.taxPercent = this.defaultVat;
            }
            this.applyVatToTax();
        },
        paymentMethod: @json(old('payment_method', 'cash')),
        get grandFormatted() {
            const s = (parseFloat(this.amount) || 0) + (parseFloat(this.taxAmount) || 0);
            return s.toFixed(2);
        },
    };
};
</script>
<div dir="rtl" class="mx-auto w-full max-w-full">
    <header class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
        <a href="{{ route('finance.expenses.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="العودة">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">إضافة مصروف</h1>
    </header>

    <form method="POST" action="{{ route('finance.expenses.store') }}" enctype="multipart/form-data" class="space-y-6" id="expense-form-create" novalidate>
        @csrf

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">تفاصيل المصروف</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">رقم المصروف</label>
                    <input type="text" value="{{ $nextExpenseNumber ?? '' }}" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-600" title="يُسجَّل تلقائياً عند الحفظ">
                </div>
                <div class="space-y-1">
                    <label for="expense_category_id" class="block text-sm font-medium text-gray-700">
                        تصنيف المصروف <x-info field="expense_expense_category" />
                    </label>
                    <x-custom-select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="w-full"
                        :options="$expenseCategoryOptions"
                        :selected="old('expense_category_id')"
                        :error="$errors->has('expense_category_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث في تصنيفات المصروف..."
                    />
                    @error('expense_category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="date" class="block text-sm font-medium text-gray-700">تاريخ المصروف <span class="text-red-500">*</span></label>
                    <input id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('date') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                    @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="cost_center_id-trigger" class="block text-sm font-medium text-gray-700">مركز التكلفة</label>
                    <x-searchable-select
                        name="cost_center_id"
                        id="cost_center_id"
                        :options="$expenseCostCenterOptions"
                        :value="old('cost_center_id')"
                        :error="$errors->has('cost_center_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث باسم مركز التكلفة..."
                    />
                    @error('cost_center_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="account_id-trigger" class="block text-sm font-medium text-gray-700">
                        الحساب المحاسبي (مصروف / أصل رأسمالي) <span class="text-red-500">*</span> <x-info field="expense_expense_account" />
                    </label>
                    <x-searchable-select
                        name="account_id"
                        id="account_id"
                        :options="$expenseAccountOptions"
                        :value="old('account_id')"
                        :required="true"
                        :error="$errors->has('account_id')"
                        empty-label="اختر الحساب"
                        placeholder="ابحث بالرمز أو اسم الحساب..."
                    />
                    @error('account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="supplier_id-trigger" class="block text-sm font-medium text-gray-700">المورد</label>
                    <x-searchable-select
                        name="supplier_id"
                        id="supplier_id"
                        :options="$expenseSupplierOptions"
                        :value="old('supplier_id')"
                        :error="$errors->has('supplier_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="reference" class="block text-sm font-medium text-gray-700">المرجع</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}" placeholder="أدخل رقم المرجع..." class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('reference') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                    @error('reference')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1 md:col-span-2 xl:col-span-3">
                    <label for="description" class="block text-sm font-medium text-gray-700">الوصف</label>
                    <textarea id="description" name="description" rows="3" placeholder="أدخل الوصف..." class="min-h-[90px] w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('description') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <x-attachment-handler
                        hint-field="expense_receipt"
                        title="مرفقات الإيصال"
                        :existing="[]"
                        :show-existing="false"
                        :uploadable="true"
                        :allow-delete="true"
                        help-text="صور أو PDF أو مستندات (حتى 20 ملفاً، 10 ميجابايت لكل ملف). تُحفظ مع المصروف في مجلد expenses/{id}."
                    />
                    <p id="receipt-local-hint" class="mt-1 hidden text-xs text-blue-800" role="status"></p>
                    @error('attachments')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section
            class="relative rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            x-data="window.expenseCreateTotals(@js((float) $defaultVatPercent), @js((float) old('amount', 0)), @js((float) old('tax_amount', 0)), @json($expenseTaxableInitial))"
            x-init="applyVatToTax()"
            @custom-select-change="if ($event.detail && $event.detail.name === 'payment_method') paymentMethod = $event.detail.value || 'cash'"
        >
            <h2 class="mb-4 text-lg font-bold text-gray-900">الدفع</h2>

            <input type="hidden" name="is_taxable" x-bind:value="isTaxable ? '1' : '0'">

            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span class="text-sm font-medium text-gray-900">خاضع للضريبة؟</span>
                    <span class="text-xs text-gray-500" x-show="!isTaxable" x-transition>مبلغ الضريبة يُصفر والإجمالي يساوي المبلغ الأساسي.</span>
                    <span class="text-xs text-gray-500" x-show="isTaxable" x-transition>تظهر نسبة الضريبة ومبلغها؛ الافتراضي {{ erp_qty((float) $defaultVatPercent) }}%.</span>
                </div>
                {{-- مسار LTR؛ حركة الدائرة = :class من Alpine (translate-x) وليس peer --}}
                <label dir="ltr" class="relative isolate inline-block h-8 w-14 shrink-0 cursor-pointer select-none overflow-hidden rounded-full border border-gray-300/90 shadow-inner">
                    <input type="checkbox" class="absolute inset-0 z-20 m-0 h-full w-full cursor-pointer opacity-0" role="switch" :aria-checked="isTaxable" x-model="isTaxable" @change="onTaxableToggle()">
                    <span class="pointer-events-none absolute inset-0 transition-colors duration-300 ease-out" :class="isTaxable ? 'bg-indigo-600' : 'bg-gray-200'" aria-hidden="true"></span>
                    <span
                        class="pointer-events-none absolute left-0.5 top-0.5 z-10 h-7 w-7 rounded-full bg-white shadow-md ring-1 ring-gray-200/90 transition-transform duration-300 ease-out will-change-transform"
                        :style="{ transform: isTaxable ? 'translate3d(1.5rem, 0, 0)' : 'translate3d(0, 0, 0)' }"
                        aria-hidden="true"
                    ></span>
                    <span class="sr-only" x-text="isTaxable ? 'نعم، خاضع للضريبة' : 'لا، غير خاضع للضريبة'"></span>
                </label>
            </div>

            @php
                $expenseAmtBorder = $errors->has('amount') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200';
                $expenseTaxBorder = $errors->has('tax_amount') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200';
            @endphp
            {{-- عناصر وهمية: تضمين أصناف md:grid-cols-* في الـ build --}}
            <span class="pointer-events-none grid hidden md:grid-cols-2" aria-hidden="true"></span>
            <span class="pointer-events-none grid hidden md:grid-cols-4" aria-hidden="true"></span>
            <div class="grid grid-cols-1 items-start gap-4" :class="isTaxable ? 'md:grid-cols-4 md:gap-5' : 'md:grid-cols-2'">
                {{-- المبلغ (إدخال يدوي) --}}
                <div class="min-w-0 space-y-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700">المبلغ <span class="text-red-500">*</span></label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 {{ $expenseAmtBorder }}">
                        <span class="flex w-[3.25rem] shrink-0 items-center justify-center border-e border-gray-200 bg-gray-50 text-xs font-semibold tabular-nums tracking-wide text-gray-600">SAR</span>
                        <input id="amount" name="amount" type="number" inputmode="decimal" min="0" step="any" x-model.number="amount" @input.debounce.150ms="applyVatToTax()" class="expense-amount-input min-w-0 flex-1 border-0 bg-white px-3 py-2 text-end text-sm tabular-nums text-gray-900 focus:outline-none focus:ring-0">
                    </div>
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- نسبة الضريبة (نفس بنية حقل المبلغ: شريحة وحدة + إدخال) --}}
                <div
                    class="min-w-0 w-full space-y-1 md:max-w-[9.5rem] md:justify-self-stretch"
                    x-show="isTaxable"
                    x-transition:enter="transition ease-out duration-250"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <label for="expense_tax_percent" class="block text-sm font-medium text-gray-700">نسبة الضريبة <x-info field="tax_percent" /></label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border border-gray-200 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                        <span class="flex w-[3.25rem] shrink-0 items-center justify-center border-e border-gray-200 bg-gray-50 text-xs font-semibold tabular-nums tracking-wide text-gray-600">%</span>
                        <input
                            id="expense_tax_percent"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            max="100"
                            step="any"
                            x-model.number="taxPercent"
                            @input.debounce.150ms="applyVatToTax()"
                            class="min-w-0 flex-1 border-0 bg-white px-3 py-2 text-end text-sm tabular-nums font-medium text-gray-900 focus:outline-none focus:ring-0"
                            title="يُحدَّث مع المبلغ؛ يمكنك التعديل أو 0%"
                        >
                    </div>
                </div>

                {{-- مبلغ الضريبة (محسوب — خلفية مميزة) --}}
                <div
                    class="min-w-0 space-y-1"
                    x-show="isTaxable"
                    x-transition:enter="transition ease-out duration-250"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <label for="tax_amount" class="block text-sm font-medium text-gray-700">مبلغ الضريبة</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border bg-gray-50 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 {{ $expenseTaxBorder }}">
                        <span class="flex w-[3.25rem] shrink-0 items-center justify-center border-e border-gray-200 bg-gray-100/80 text-xs font-semibold tabular-nums tracking-wide text-gray-600">SAR</span>
                        <input id="tax_amount" name="tax_amount" type="number" inputmode="decimal" min="0" step="any" x-model.number="taxAmount" class="expense-tax-input min-w-0 flex-1 border-0 bg-gray-50 px-3 py-2 text-end text-sm tabular-nums text-gray-800 focus:outline-none focus:ring-0">
                    </div>
                    @error('tax_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- الإجمالي (محسوب) --}}
                <div class="min-w-0 space-y-1">
                    <label for="total_amount" class="block text-sm font-medium text-gray-700">الإجمالي</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                        <span class="flex w-[3.25rem] shrink-0 items-center justify-center border-e border-gray-200 bg-gray-100/80 text-xs font-semibold tabular-nums tracking-wide text-gray-600">SAR</span>
                        <input id="total_amount" type="text" readonly :value="grandFormatted" class="min-w-0 flex-1 cursor-not-allowed border-0 bg-gray-50 px-3 py-2 text-end text-sm font-semibold tabular-nums text-gray-900 focus:outline-none focus:ring-0" tabindex="-1" aria-live="polite">
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="space-y-1 md:col-span-1">
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">طريقة الدفع <span class="text-red-500">*</span></label>
                    <x-custom-select
                        id="payment_method"
                        name="payment_method"
                        class="w-full"
                        :options="$expensePaymentMethodOpts"
                        :selected="old('payment_method', 'cash')"
                        :empty-option="false"
                        :error="$errors->has('payment_method')"
                        placeholder="طريقة الدفع..."
                    />
                    @error('payment_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1 md:col-span-2" x-show="['bank','check','card'].includes(paymentMethod)" x-cloak>
                    <label for="bank_account_id-trigger" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        الحساب البنكي <span class="text-red-500">*</span>
                        <x-info field="expense_bank_account" />
                    </label>
                    <x-searchable-select
                        name="bank_account_id"
                        id="bank_account_id"
                        :options="$expenseBankAccountOptions"
                        :value="old('bank_account_id')"
                        :required="false"
                        :error="$errors->has('bank_account_id')"
                        empty-label="اختر الحساب البنكي"
                        placeholder="ابحث باسم البنك أو رقم الحساب..."
                    />
                    @error('bank_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-3 rounded-lg border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm text-blue-900">
                    <p class="font-medium">يُحفظ المصروف كمسودة ولا يُرحَّل إلى دفتر الأستاذ إلا بعد اعتماده من المدير أو الإدارة.</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-lg font-bold text-gray-900">ملاحظات</h2>
            <textarea name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="min-h-[110px] w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('notes') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </section>
    </form>

    <div class="relative z-20 mt-6 flex flex-wrap justify-end gap-3 pb-2">
        <a href="{{ route('finance.expenses.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
        <button type="submit" form="expense-form-create" id="expense-create-submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70">
            <span class="expense-submit-label" data-default-label="إنشاء">إنشاء</span>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var MAX_RECEIPT_BYTES = 10 * 1024 * 1024;
    function resetSubmitBtn(btn) {
        if (!btn) return;
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        var lab = btn.querySelector('.expense-submit-label');
        if (lab && lab.getAttribute('data-default-label')) {
            lab.textContent = lab.getAttribute('data-default-label');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('expense-form-create');
        var btn = document.getElementById('expense-create-submit');
        var fileInput = form ? form.querySelector('input[name="attachments[]"]') : null;
        var hint = document.getElementById('receipt-local-hint');

        if (fileInput && hint) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length) {
                    hint.textContent = 'تم اختيار ملفات على جهازك فقط. اضغط «إنشاء» لإرسال النموذج ورفع المرفقات.';
                    hint.classList.remove('hidden');
                }
            });
        }

        if (form && btn) {
            form.addEventListener('submit', function (e) {
                var inp = form.querySelector('input[name="attachments[]"]');
                if (inp && inp.files && inp.files.length) {
                    for (var i = 0; i < inp.files.length; i++) {
                        if (inp.files[i].size > MAX_RECEIPT_BYTES) {
                            e.preventDefault();
                            window.alert('حجم أحد المرفقات يتجاوز 10 ميجابايت. اختر ملفات أصغر.');
                            return;
                        }
                    }
                }
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
                var lab = btn.querySelector('.expense-submit-label');
                if (lab) lab.textContent = 'جاري الحفظ...';
                window.setTimeout(function () { resetSubmitBtn(btn); }, 60000);
            });
            window.addEventListener('pageshow', function () { resetSubmitBtn(btn); });
        }
    });
})();
</script>
@endpush
