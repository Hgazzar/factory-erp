@extends('layouts.app')

@section('title', 'تعديل مصروف - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.expenses.index') }}" class="text-gray-500 hover:text-blue-600">المصروفات</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تعديل مصروف</span>
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
    $expenseEditCategoryOptions = collect($categories ?? collect())->map(fn ($c) => [
        'value' => data_get($c, 'id'),
        'label' => trim((string) data_get($c, 'code').' — '.(string) data_get($c, 'name_ar', data_get($c, 'name', ''))),
    ])->filter(fn ($o) => (string) ($o['value'] ?? '') !== '')->values()->all();
    $expenseEditPaymentMethodOpts = [
        ['value' => 'cash', 'label' => 'نقدًا'],
        ['value' => 'bank', 'label' => 'تحويل بنكي'],
        ['value' => 'card', 'label' => 'بطاقة'],
        ['value' => 'check', 'label' => 'شيك'],
    ];
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full">
    <header class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
        <a href="{{ route('finance.expenses.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="العودة">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">تعديل مصروف</h1>
    </header>

    <form method="POST" action="{{ route('finance.expenses.update', $expense) }}" enctype="multipart/form-data" class="space-y-6" id="expense-form-edit" novalidate>
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">تفاصيل المصروف</h2>
            @if(!empty($expenseIsPosted) && !empty($isSuperAdmin))
                <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">مسؤول النظام: مصروف معتمد — أي تعديل يحدّث بيانات السند والقيد المحاسبي المرتبط عند وجوده.</p>
            @else
                <p class="mb-4 text-sm text-gray-500">المسودات قابلة للتعديل قبل الاعتماد؛ المصروفات المعتمدة لا يمكن تعديلها إلا لمسؤول النظام.</p>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">رقم المصروف</label>
                    <input type="text" value="{{ $expense->expense_number ?? ('EXP-'.str_pad((string) $expense->id, 5, '0', STR_PAD_LEFT)) }}" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-600">
                </div>
                <div class="space-y-1">
                    <label for="expense_category_id" class="block text-sm font-medium text-gray-700">
                        تصنيف المصروف <x-info field="expense_expense_category" />
                    </label>
                    <x-custom-select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="w-full"
                        :options="$expenseEditCategoryOptions"
                        :selected="old('expense_category_id', $expense->expense_category_id)"
                        :error="$errors->has('expense_category_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث في تصنيفات المصروف..."
                    />
                    @error('expense_category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="date" class="block text-sm font-medium text-gray-700">تاريخ المصروف <span class="text-red-500">*</span></label>
                    <input id="date" name="date" type="date" value="{{ old('date', $expense->date?->toDateString()) }}" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('date') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                    @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="cost_center_id-trigger" class="block text-sm font-medium text-gray-700">مركز التكلفة</label>
                    <x-searchable-select
                        name="cost_center_id"
                        id="cost_center_id"
                        :options="$expenseCostCenterOptions"
                        :value="old('cost_center_id', $expense->cost_center_id)"
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
                        :value="old('account_id', $expense->expense_account_id)"
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
                        :value="old('supplier_id', $expense->supplier_id)"
                        :error="$errors->has('supplier_id')"
                        empty-label="لا شيء"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="reference" class="block text-sm font-medium text-gray-700">المرجع</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference', $expense->reference) }}" placeholder="أدخل رقم المرجع..." class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('reference') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
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
                        :existing="$expense->attachments"
                        :uploadable="true"
                        :allow-delete="true"
                        help-text="معاينة وحذف المرفقات الحالية وإضافة ملفات جديدة (حتى 20 ملفاً، 10 ميجابايت لكل ملف)."
                    />
                    <p id="receipt-local-hint" class="mt-1 hidden text-xs text-blue-800" role="status"></p>
                    @error('attachments')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">الدفع</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="space-y-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700">المبلغ <span class="text-red-500">*</span></label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 {{ $errors->has('amount') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium tracking-wide text-gray-500">SAR</span>
                        <input id="amount" name="amount" type="number" inputmode="decimal" min="0" step="any" value="{{ old('amount', $expense->amount) }}" class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="tax_amount" class="block text-sm font-medium text-gray-700">مبلغ الضريبة</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 {{ $errors->has('tax_amount') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium tracking-wide text-gray-500">SAR</span>
                        <input id="tax_amount" name="tax_amount" type="number" inputmode="decimal" min="0" step="any" value="{{ old('tax_amount', $expense->tax_amount ?? 0) }}" class="h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('tax_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="total_amount" class="block text-sm font-medium text-gray-700">الإجمالي</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-100 text-xs font-medium tracking-wide text-gray-500">SAR</span>
                        <input id="total_amount" type="text" readonly value="{{ number_format((float) old('amount', $expense->amount) + (float) old('tax_amount', $expense->tax_amount ?? 0), 2, '.', '') }}" class="h-full flex-1 cursor-not-allowed border-0 bg-gray-50 px-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-0" tabindex="-1" aria-live="polite">
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3" x-data="{ pm: @js(old('payment_method', $expense->payment_method)) }" @custom-select-change="if ($event.detail && $event.detail.name === 'payment_method') pm = $event.detail.value || 'cash'">
                <div class="space-y-1 md:col-span-1">
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">طريقة الدفع <span class="text-red-500">*</span></label>
                    <x-custom-select
                        id="payment_method"
                        name="payment_method"
                        class="w-full"
                        :options="$expenseEditPaymentMethodOpts"
                        :selected="old('payment_method', $expense->payment_method)"
                        :empty-option="false"
                        :error="$errors->has('payment_method')"
                        placeholder="طريقة الدفع..."
                    />
                    @error('payment_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1 md:col-span-2" x-show="['bank','check','card'].includes(pm)" x-cloak>
                    <label for="bank_account_id-trigger" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700">
                        الحساب البنكي <span class="text-red-500">*</span>
                        <x-info field="expense_bank_account" />
                    </label>
                    <x-searchable-select
                        name="bank_account_id"
                        id="bank_account_id"
                        :options="$expenseBankAccountOptions"
                        :value="old('bank_account_id', $expense->bank_account_id)"
                        :required="false"
                        :error="$errors->has('bank_account_id')"
                        empty-label="اختر الحساب البنكي"
                        placeholder="ابحث باسم البنك أو رقم الحساب..."
                    />
                    @error('bank_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-lg font-bold text-gray-900">ملاحظات</h2>
            <textarea name="notes" rows="4" placeholder="أدخل الملاحظات هنا..." class="min-h-[110px] w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('notes') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">{{ old('notes', $expense->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </section>
    </form>

    <div class="relative z-20 mt-6 flex flex-wrap justify-end gap-3 pb-2">
        <a href="{{ route('finance.expenses.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
        <button type="submit" form="expense-form-edit" id="expense-edit-submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70">
            <span class="expense-submit-label" data-default-label="حفظ التغييرات">حفظ التغييرات</span>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var MAX_RECEIPT_BYTES = 10 * 1024 * 1024;
    function syncExpenseTotal() {
        var a = document.getElementById('amount');
        var t = document.getElementById('tax_amount');
        var out = document.getElementById('total_amount');
        if (!a || !t || !out) return;
        var sum = (parseFloat(a.value) || 0) + (parseFloat(t.value) || 0);
        out.value = sum.toFixed(2);
    }
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
        var a = document.getElementById('amount');
        var tx = document.getElementById('tax_amount');
        if (a) a.addEventListener('input', syncExpenseTotal);
        if (tx) tx.addEventListener('input', syncExpenseTotal);
        syncExpenseTotal();

        var form = document.getElementById('expense-form-edit');
        var btn = document.getElementById('expense-edit-submit');
        var fileInput = form ? form.querySelector('input[name="attachments[]"]') : null;
        var hint = document.getElementById('receipt-local-hint');

        if (fileInput && hint) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length) {
                    hint.textContent = 'تم اختيار ملفات على جهازك فقط. اضغط «حفظ التغييرات» لإرسال النموذج ورفع المرفقات.';
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
