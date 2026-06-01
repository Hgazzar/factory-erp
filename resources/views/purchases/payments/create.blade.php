@extends('layouts.app')

@section('title', 'سند صرف مورد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.payments.index') }}" class="text-gray-500 hover:text-indigo-600">مدفوعات الموردين</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">سند صرف جديد</span>
@endsection

@section('content')
@php
    $supplierOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim($s->getLocalizedDisplayName().' ('.($s->code ?? $s->id).')'),
    ])->all();
    $paymentMethodOptions = collect($paymentMethods)->map(fn ($label, $value) => [
        'value' => $value,
        'label' => $label,
    ])->values()->all();
@endphp
<div class="max-w-full">
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">سند صرف مورد</h1>
        <a href="{{ route('purchases.payments.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">الرجوع للمدفوعات</a>
    </div>

    <form method="POST" action="{{ route('purchases.payments.store') }}" id="supplier-payment-form" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">بيانات السند</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-info field="procurement.supplier_payment_supplier" for="supplier_id-trigger" class="block text-sm font-medium text-gray-700 mb-1">المورد <span class="text-red-500">*</span></x-info>
                    <x-searchable-select
                        class="w-full"
                        name="supplier_id"
                        id="supplier_id"
                        :options="$supplierOptions"
                        :value="old('supplier_id')"
                        :required="true"
                        :error="$errors->has('supplier_id')"
                        empty-label="— اختر المورد —"
                        placeholder="ابحث باسم المورد أو الرمز..."
                    />
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-info field="procurement.supplier_payment_date" for="date" class="block text-sm font-medium text-gray-700 mb-1">تاريخ الدفع <span class="text-red-500">*</span></x-info>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-info field="procurement.supplier_payment_method" for="payment_method-trigger" class="block text-sm font-medium text-gray-700 mb-1">طريقة الدفع <span class="text-red-500">*</span></x-info>
                    <x-searchable-select
                        class="w-full"
                        name="payment_method"
                        id="payment_method"
                        :options="$paymentMethodOptions"
                        :value="old('payment_method', 'cash')"
                        :required="true"
                        :error="$errors->has('payment_method')"
                        :searchable="false"
                    />
                    @error('payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-info field="procurement.supplier_payment_amount" for="amount" class="block text-sm font-medium text-gray-700 mb-1">المبلغ (SAR) <span class="text-red-500">*</span></x-info>
                    <input type="number" inputmode="decimal" name="amount" id="amount" value="{{ old('amount') }}" min="0.01" step="any" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="0.00">
                    @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-info field="procurement.supplier_payment_reference" for="reference" class="block text-sm font-medium text-gray-700 mb-1">المرجع</x-info>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}" maxlength="50" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-info field="procurement.supplier_payment_notes" for="notes" class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</x-info>
                    <input type="text" name="notes" id="notes" value="{{ old('notes') }}" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div id="outstanding-wrap" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">رصيد المورد المفتوح</h2>
            <p id="outstanding-balance" class="text-2xl font-bold text-indigo-600">SAR 0.00</p>
        </div>

        <div id="invoice-wrap" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">تخصيص على فاتورة (اختياري)</h2>
            <p class="text-sm text-gray-500 mb-4">اختر فاتورة لسدادها مباشرة؛ أو اترك الاختيار فارغاً لتسوية الذمة العامة للمورد.</p>
            <input type="hidden" name="purchase_invoice_id" id="purchase_invoice_id" value="{{ old('purchase_invoice_id') }}">
            @error('purchase_invoice_id')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="overflow-x-auto hidden" id="invoice-table-wrap">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-3 font-medium w-10"></th>
                            <th class="py-2 px-3 font-medium"><x-info field="procurement.supplier_payment_invoice">الفاتورة</x-info></th>
                            <th class="py-2 px-3 font-medium">التاريخ</th>
                            <th class="py-2 px-3 font-medium">الإجمالي</th>
                            <th class="py-2 px-3 font-medium">المدفوع</th>
                            <th class="py-2 px-3 font-medium">المتبقي</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-tbody"></tbody>
                </table>
            </div>
            <p id="no-invoices-msg" class="text-sm text-gray-500 hidden">لا توجد فواتير مفتوحة لهذا المورد.</p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('purchases.payments.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-medium text-sm hover:bg-indigo-700 transition">حفظ سند الصرف</button>
        </div>
    </form>
</div>

<script>
(function() {
    const supplierInput = document.querySelector('input[name="supplier_id"]');
    const amountInput = document.getElementById('amount');
    const invoiceIdInput = document.getElementById('purchase_invoice_id');
    const outstandingWrap = document.getElementById('outstanding-wrap');
    const outstandingBalance = document.getElementById('outstanding-balance');
    const invoiceWrap = document.getElementById('invoice-wrap');
    const invoiceTableWrap = document.getElementById('invoice-table-wrap');
    const invoiceTbody = document.getElementById('invoice-tbody');
    const noInvoicesMsg = document.getElementById('no-invoices-msg');

    let unpaidInvoices = [];

    function renderInvoiceTable() {
        invoiceTbody.innerHTML = '';
        const preselected = invoiceIdInput ? invoiceIdInput.value : '';

        unpaidInvoices.forEach(function(inv) {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100 cursor-pointer hover:bg-indigo-50/40';
            const checked = String(inv.id) === String(preselected) ? ' checked' : '';
            row.innerHTML =
                '<td class="py-2 px-3"><input type="radio" name="invoice_pick" value="' + inv.id + '" class="invoice-pick"' + checked + '></td>' +
                '<td class="py-2 px-3 text-gray-900 font-medium">' + inv.reference + '</td>' +
                '<td class="py-2 px-3 text-gray-700">' + (inv.date || '') + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + Number(inv.total).toFixed(2) + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + Number(inv.paid_amount).toFixed(2) + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + Number(inv.balance).toFixed(2) + '</td>';
            row.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('invoice-pick')) return;
                const radio = row.querySelector('.invoice-pick');
                if (radio) {
                    radio.checked = true;
                    selectInvoice(inv);
                }
            });
            const radio = row.querySelector('.invoice-pick');
            radio.addEventListener('change', function() {
                if (radio.checked) selectInvoice(inv);
            });
            invoiceTbody.appendChild(row);
        });

        invoiceTableWrap.classList.toggle('hidden', unpaidInvoices.length === 0);
        noInvoicesMsg.classList.toggle('hidden', unpaidInvoices.length > 0);
    }

    function selectInvoice(inv) {
        if (invoiceIdInput) invoiceIdInput.value = inv.id;
        if (amountInput && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
            amountInput.value = Number(inv.balance).toFixed(2);
        }
    }

    function clearInvoiceSelection() {
        if (invoiceIdInput) invoiceIdInput.value = '';
        document.querySelectorAll('.invoice-pick').forEach(function(r) { r.checked = false; });
    }

    function fetchOutstanding() {
        const supplierId = supplierInput ? supplierInput.value : '';
        if (!supplierId) {
            outstandingWrap.classList.add('hidden');
            invoiceWrap.classList.add('hidden');
            unpaidInvoices = [];
            clearInvoiceSelection();
            return;
        }

        fetch('{{ route("purchases.payments.supplier-outstanding") }}?supplier_id=' + encodeURIComponent(supplierId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                unpaidInvoices = data.invoices || [];
                outstandingBalance.textContent = 'SAR ' + Number(data.outstanding_balance || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2 });
                outstandingWrap.classList.remove('hidden');
                invoiceWrap.classList.remove('hidden');
                renderInvoiceTable();
            })
            .catch(function() {
                outstandingWrap.classList.add('hidden');
                invoiceWrap.classList.add('hidden');
                unpaidInvoices = [];
                clearInvoiceSelection();
            });
    }

    if (supplierInput) {
        supplierInput.addEventListener('change', function() {
            clearInvoiceSelection();
            fetchOutstanding();
        });
    }

    window.addEventListener('searchable-select-change', function(e) {
        if (e.detail && e.detail.name === 'supplier_id') {
            clearInvoiceSelection();
            fetchOutstanding();
        }
    });

    if (supplierInput && supplierInput.value) fetchOutstanding();
})();
</script>
@endsection
