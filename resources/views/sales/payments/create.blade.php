@extends('layouts.app')

@section('title', 'دفعة جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.payments.index') }}" class="text-gray-500 hover:text-indigo-600">المدفوعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">دفعة جديدة</span>
@endsection

@section('content')
<div class="max-w-full">
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">دفعة جديدة</h1>
        <a href="{{ route('sales.payments.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">الرجوع للمدفوعات</a>
    </div>

    <form method="POST" action="{{ route('sales.payments.store') }}" id="payment-form" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">بيانات الدفعة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
                    <select name="customer_id" id="customer_id" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- اختر العميل --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code ?? $c->id }})</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">تاريخ الدفع <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">طريقة الدفع <span class="text-red-500">*</span></label>
                    <select name="payment_method" id="payment_method" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($paymentMethods as $value => $label)
                            <option value="{{ $value }}" {{ old('payment_method', 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">المبلغ (SAR) <span class="text-red-500">*</span></label>
                    <input type="number" inputmode="decimal" name="amount" id="amount" value="{{ old('amount') }}" min="0.01" step="any" required class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="0.00">
                    @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">المرجع</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}" maxlength="50" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <input type="text" name="notes" id="notes" value="{{ old('notes') }}" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- رصيد العميل المستحق (يُجلب عند اختيار العميل) --}}
        <div id="outstanding-wrap" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">رصيد العميل المستحق</h2>
            <p id="outstanding-balance" class="text-2xl font-bold text-indigo-600">SAR 0.00</p>
        </div>

        {{-- تخصيص الدفعة على الفواتير --}}
        <div id="allocation-wrap" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hidden">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">تخصيص الدفعة على الفواتير</h2>
                <button type="button" id="btn-auto-allocate" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-100 text-indigo-700 text-sm font-medium hover:bg-indigo-200 transition">
                    توزيع تلقائي
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-4">أدخل المبلغ المراد تخصيصه لكل فاتورة. المجموع يجب ألا يتجاوز مبلغ الدفعة.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-3 font-medium">رقم الفاتورة</th>
                            <th class="py-2 px-3 font-medium">التاريخ</th>
                            <th class="py-2 px-3 font-medium">إجمالي الفاتورة</th>
                            <th class="py-2 px-3 font-medium">المدفوع</th>
                            <th class="py-2 px-3 font-medium">المتبقي</th>
                            <th class="py-2 px-3 font-medium">مبلغ التخصيص</th>
                        </tr>
                    </thead>
                    <tbody id="allocation-tbody">
                    </tbody>
                </table>
            </div>
            <p id="allocation-summary" class="mt-3 text-sm text-gray-600"></p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.payments.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-medium text-sm hover:bg-indigo-700 transition">حفظ الدفعة</button>
        </div>
    </form>
</div>

<script>
(function() {
    const customerSelect = document.getElementById('customer_id');
    const amountInput = document.getElementById('amount');
    const outstandingWrap = document.getElementById('outstanding-wrap');
    const outstandingBalance = document.getElementById('outstanding-balance');
    const allocationWrap = document.getElementById('allocation-wrap');
    const allocationTbody = document.getElementById('allocation-tbody');
    const allocationSummary = document.getElementById('allocation-summary');
    const btnAutoAllocate = document.getElementById('btn-auto-allocate');

    let unpaidInvoices = [];

    function fetchOutstanding() {
        const customerId = customerSelect.value;
        if (!customerId) {
            outstandingWrap.classList.add('hidden');
            allocationWrap.classList.add('hidden');
            unpaidInvoices = [];
            return;
        }
        fetch('{{ route("sales.payments.customer-outstanding") }}?customer_id=' + encodeURIComponent(customerId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                unpaidInvoices = data.invoices || [];
                outstandingBalance.textContent = 'SAR ' + (data.outstanding_balance || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2 });
                outstandingWrap.classList.remove('hidden');
                renderAllocationTable();
                if (unpaidInvoices.length > 0) {
                    allocationWrap.classList.remove('hidden');
                } else {
                    allocationWrap.classList.add('hidden');
                }
            })
            .catch(function() {
                outstandingWrap.classList.add('hidden');
                allocationWrap.classList.add('hidden');
                unpaidInvoices = [];
            });
    }

    function renderAllocationTable(allocations) {
        allocations = allocations || {};
        allocationTbody.innerHTML = '';
        unpaidInvoices.forEach(function(inv, index) {
            const alloc = parseFloat(allocations[inv.id]) || 0;
            const balance = parseFloat(inv.balance);
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100';
            row.innerHTML =
                '<td class="py-2 px-3 text-gray-900 font-medium">' + (inv.invoice_number || 'SINV-' + inv.id) + '</td>' +
                '<td class="py-2 px-3 text-gray-700">' + (inv.date || '') + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + parseFloat(inv.total).toFixed(2) + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + parseFloat(inv.paid_amount).toFixed(2) + '</td>' +
                '<td class="py-2 px-3 text-gray-700">SAR ' + balance.toFixed(2) + '</td>' +
                '<td class="py-2 px-3">' +
                '<input type="hidden" name="allocations[' + index + '][invoice_id]" value="' + inv.id + '">' +
                '<input type="number" inputmode="decimal" step="any" min="0" max="' + balance + '" name="allocations[' + index + '][amount]" data-invoice-id="' + inv.id + '" data-balance="' + balance + '" class="alloc-input w-24 py-1.5 px-2 border border-gray-300 rounded text-sm" value="' + (alloc > 0 ? alloc.toFixed(2) : '') + '" placeholder="0">' +
                '</td>';
            allocationTbody.appendChild(row);
        });
        updateAllocationSummary();
    }

    function getPaymentAmount() {
        return parseFloat(amountInput.value) || 0;
    }

    function updateAllocationSummary() {
        let sum = 0;
        document.querySelectorAll('.alloc-input').forEach(function(input) {
            sum += parseFloat(input.value) || 0;
        });
        const total = getPaymentAmount();
        allocationSummary.textContent = 'المخصص: SAR ' + sum.toFixed(2) + ' من أصل SAR ' + total.toFixed(2) + (sum > total ? ' — تجاوز المبلغ!' : '');
        allocationSummary.className = 'mt-3 text-sm ' + (sum > total ? 'text-red-600 font-medium' : 'text-gray-600');
    }

    function autoAllocate() {
        const amount = getPaymentAmount();
        if (amount <= 0 || unpaidInvoices.length === 0) return;
        const allocations = {};
        let remaining = amount;
        unpaidInvoices.forEach(function(inv) {
            const balance = parseFloat(inv.balance);
            const alloc = remaining <= 0 ? 0 : Math.min(balance, remaining);
            if (alloc > 0) {
                allocations[inv.id] = alloc;
                remaining -= alloc;
            }
        });
        renderAllocationTable(allocations);
    }

    function buildAllocationInputs() {
        const container = document.createElement('div');
        container.id = 'allocation-inputs-container';
        document.getElementById('payment-form').appendChild(container);
        const fragment = document.createDocumentFragment();
        document.querySelectorAll('.alloc-input').forEach(function(input) {
            const invId = input.dataset.invoiceId;
            const val = parseFloat(input.value) || 0;
            if (val <= 0) return;
            const wrap = document.createElement('div');
            wrap.innerHTML =
                '<input type="hidden" name="allocations[][invoice_id]" value="' + invId + '">' +
                '<input type="hidden" name="allocations[][amount]" value="' + val + '">';
            fragment.appendChild(wrap);
        });
        container.innerHTML = '';
        container.appendChild(fragment);
    }

    customerSelect.addEventListener('change', fetchOutstanding);
    amountInput.addEventListener('input', function() {
        if (allocationTbody.children.length) updateAllocationSummary();
    });
    btnAutoAllocate.addEventListener('click', autoAllocate);

    document.getElementById('payment-form').addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('alloc-input')) updateAllocationSummary();
    });

    if (customerSelect.value) fetchOutstanding();
})();
</script>
@endsection
