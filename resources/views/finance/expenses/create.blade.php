@extends('layouts.app')

@section('title', 'إضافة مصروف - MIRADA ERP')

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
                    <select id="expense_category_id" name="expense_category_id" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('expense_category_id') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <option value="">لا شيء</option>
                        @forelse(($categories ?? collect()) as $category)
                            <option value="{{ data_get($category, 'id') }}" @selected((string) old('expense_category_id') === (string) data_get($category, 'id'))>
                                {{ data_get($category, 'code') }} — {{ data_get($category, 'name_ar', data_get($category, 'name', '')) }}
                            </option>
                        @empty
                        @endforelse
                    </select>
                    @error('expense_category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="date" class="block text-sm font-medium text-gray-700">تاريخ المصروف <span class="text-red-500">*</span></label>
                    <input id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('date') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                    @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="cost_center_id" class="block text-sm font-medium text-gray-700">مركز التكلفة</label>
                    <select id="cost_center_id" name="cost_center_id" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('cost_center_id') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <option value="">لا شيء</option>
                        @forelse(($costCenters ?? collect()) as $costCenter)
                            <option value="{{ data_get($costCenter, 'id') }}" @selected((string) old('cost_center_id') === (string) data_get($costCenter, 'id'))>
                                {{ data_get($costCenter, 'name_ar', data_get($costCenter, 'name', '')) }}
                            </option>
                        @empty
                        @endforelse
                    </select>
                    @error('cost_center_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="account_id" class="block text-sm font-medium text-gray-700">
                        الحساب المحاسبي <span class="text-red-500">*</span> <x-info field="expense_expense_account" />
                    </label>
                    <select id="account_id" name="account_id" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('account_id') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <option value="">اختر الحساب</option>
                        @forelse(($expenseAccounts ?? collect()) as $account)
                            <option value="{{ data_get($account, 'id') }}" @selected((string) old('account_id') === (string) data_get($account, 'id'))>
                                {{ data_get($account, 'code') }} - {{ data_get($account, 'name_ar', data_get($account, 'name', '')) }}
                            </option>
                        @empty
                        @endforelse
                    </select>
                    @error('account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">المورد</label>
                    <select id="supplier_id" name="supplier_id" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('supplier_id') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <option value="">لا شيء</option>
                        @forelse(($suppliers ?? collect()) as $supplier)
                            <option value="{{ data_get($supplier, 'id') }}" @selected((string) old('supplier_id') === (string) data_get($supplier, 'id'))>
                                {{ data_get($supplier, 'code') }} - {{ data_get($supplier, 'name', data_get($supplier, 'name_ar', '')) }}
                            </option>
                        @empty
                        @endforelse
                    </select>
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

                <div class="space-y-1 md:col-span-2 xl:col-span-3">
                    <label for="receipt" class="block text-sm font-medium text-gray-700">
                        إيصال مرفق (صورة) <x-info field="expense_receipt" />
                    </label>
                    <input id="receipt" name="receipt" type="file" accept="image/*" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500">صورة فاتورة أو إيصال (وقود، مرافق، …) — حتى 5 ميغابايت.</p>
                    <p id="receipt-local-hint" class="mt-1 hidden text-xs text-blue-800" role="status"></p>
                    @error('receipt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                        <input id="amount" name="amount" type="number" inputmode="decimal" min="0" step="any" value="{{ old('amount', 0) }}" class="expense-amount-input h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="tax_amount" class="block text-sm font-medium text-gray-700">مبلغ الضريبة</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 {{ $errors->has('tax_amount') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-50 text-xs font-medium tracking-wide text-gray-500">SAR</span>
                        <input id="tax_amount" name="tax_amount" type="number" inputmode="decimal" min="0" step="any" value="{{ old('tax_amount', 0) }}" class="expense-tax-input h-full flex-1 border-0 bg-white px-3 text-sm focus:outline-none focus:ring-0">
                    </div>
                    @error('tax_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="total_amount" class="block text-sm font-medium text-gray-700">الإجمالي</label>
                    <div class="flex h-11 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                        <span class="flex w-14 items-center justify-center border-r border-gray-200 bg-gray-100 text-xs font-medium tracking-wide text-gray-500">SAR</span>
                        <input id="total_amount" type="text" readonly value="{{ number_format((float) old('amount', 0) + (float) old('tax_amount', 0), 2, '.', '') }}" class="h-full flex-1 cursor-not-allowed border-0 bg-gray-50 px-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-0" tabindex="-1" aria-live="polite">
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="space-y-1 md:col-span-1">
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">طريقة الدفع <span class="text-red-500">*</span></label>
                    <select id="payment_method" name="payment_method" class="h-10 w-full rounded-lg border bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('payment_method') ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200' }}">
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>نقدًا</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>تحويل بنكي</option>
                        <option value="card" @selected(old('payment_method') === 'card')>بطاقة</option>
                    </select>
                    @error('payment_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 rounded-lg border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm text-blue-900">
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
    var MAX_RECEIPT_BYTES = 5 * 1024 * 1024;
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

        var form = document.getElementById('expense-form-create');
        var btn = document.getElementById('expense-create-submit');
        var receipt = document.getElementById('receipt');
        var hint = document.getElementById('receipt-local-hint');

        if (receipt && hint) {
            receipt.addEventListener('change', function () {
                if (this.files && this.files.length) {
                    hint.textContent = 'تم اختيار ملف على جهازك فقط. اضغط «إنشاء» لإرسال النموذج ورفع الإيصال.';
                    hint.classList.remove('hidden');
                }
            });
        }

        if (form && btn) {
            form.addEventListener('submit', function (e) {
                var f = receipt && receipt.files && receipt.files[0];
                if (f && f.size > MAX_RECEIPT_BYTES) {
                    e.preventDefault();
                    window.alert('حجم الإيصال يتجاوز 5 ميجابايت. اختر صورة أصغر.');
                    return;
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
