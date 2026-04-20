@extends('layouts.app')

@section('title', 'كشف حساب العميل - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">كشف حساب العميل</span>
@endsection

@section('content')
@php
    $statementCustomerOptions = $customers->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->name ?? ''),
    ])->all();
@endphp
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">كشف حساب العميل</h1>
            <p class="text-sm text-gray-500 mt-1">عرض سجل معاملات العميل والفواتير والمدفوعات والاشتراكات.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition no-print">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5 1a2 2 0 0 0-2 2v2h10V3a2 2 0 0 0-2-2H5z"/><path d="M3 7h10v5H3V7z"/><path d="M0 8a2 2 0 0 1 2-2h1v1H2a1 1 0 0 0-1 1v5h2v1H1a1 1 0 0 1-1-1V8zm15-2a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2v-1h2V8a1 1 0 0 0-1-1h-1V6h1z"/></svg>
                طباعة
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition no-print">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5 2a2 2 0 0 0-2 2v8.5A1.5 1.5 0 0 0 4.5 14h7A1.5 1.5 0 0 0 13 12.5V6.414a2 2 0 0 0-.586-1.414l-3.414-3.414A2 2 0 0 0 7.586 1H5z"/><path d="M5 7h6v1H5V7zm0 2h6v1H5V9z"/></svg>
                تصدير PDF
            </button>
        </div>
    </div>

    {{-- الفلترة --}}
    <form method="GET" action="{{ route('reports.statement.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6 space-y-4" id="statement-filter-form">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="customer_id-trigger">العميل <span class="text-red-500">*</span></label>
                <x-searchable-select
                    class="w-full"
                    name="customer_id"
                    id="customer_id"
                    :options="$statementCustomerOptions"
                    :value="old('customer_id', $customerId)"
                    :required="true"
                    empty-label="اختر العميل"
                    placeholder="ابحث باسم العميل..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
        <input type="hidden" name="range" id="range" value="{{ $range }}">
        <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
            <div class="flex flex-wrap gap-2 text-xs md:text-sm">
                <button type="button" data-range="this_month" class="px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-700 hover:bg-indigo-50 hover:border-indigo-300">هذا الشهر</button>
                <button type="button" data-range="this_quarter" class="px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-700 hover:bg-indigo-50 hover:border-indigo-300">هذا الربع</button>
                <button type="button" data-range="this_year" class="px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-700 hover:bg-indigo-50 hover:border-indigo-300">هذه السنة</button>
                <button type="button" data-range="last_12_months" class="px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-700 hover:bg-indigo-50 hover:border-indigo-300">آخر 12 شهراً</button>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">عرض</button>
            </div>
        </div>
    </form>

    {{-- البطاقات الإحصائية --}}
    @if($customer && count($transactions) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-0.5">إجمالي المدين</p>
                    <p class="text-xl font-bold text-gray-900">SAR {{ erp_money($totalDebit) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-red-50 text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h3.5a.5.5 0 0 1 0 1H2a1 1 0 0 0-1 1v7h3v1H1a1 1 0 0 1-1-1V4z"/><path d="M9.5 2a.5.5 0 0 0 0 1H14a2 2 0 0 1 2 2v7a1 1 0 0 1-1 1H9.5a.5.5 0 0 0 0 1H15a2 2 0 0 0 2-2V5a3 3 0 0 0-3-3H9.5z"/><path d="M5 9.5a.5.5 0 0 1 .5-.5h5.793l-2.147-2.146a.5.5 0 1 1 .708-.708l3 3a.498.498 0 0 1 .146.35.498.498 0 0 1-.146.35l-3 3a.5.5 0 0 1-.708-.708L11.293 10H5.5a.5.5 0 0 1-.5-.5z"/></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-0.5">إجمالي الدائن</p>
                    <p class="text-xl font-bold text-gray-900">SAR {{ erp_money($totalCredit) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 8 8A8.009 8.009 0 0 0 8 0zm3.707 6.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L7 8.586l3.293-3.293a1 1 0 0 1 1.414 1.414z"/></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-0.5">الرصيد الحالي</p>
                    <p class="text-xl font-bold text-gray-900">SAR {{ erp_money($currentBalance) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-sky-50 text-sky-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h3.5a.5.5 0 0 1 0 1H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3.5a.5.5 0 0 1 0 1H2a2 2 0 0 1-2-2V4z"/><path d="M7 10.5a.5.5 0 0 1 .5-.5h3.793l-2.147-2.146a.5.5 0 1 1 .708-.708l3 3a.503.503 0 0 1 .146.354v.004a.5.5 0 0 1-.146.35l-3 3a.5.5 0 0 1-.708-.708L11.293 11H7.5a.5.5 0 0 1-.5-.5z"/><path d="M4.5 3a.5.5 0 0 1 .5-.5h7A2.5 2.5 0 0 1 14.5 5v1.5a.5.5 0 0 1-1 0V5A1.5 1.5 0 0 0 12 3.5h-7a.5.5 0 0 1-.5-.5z"/></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-0.5">الأقساط المتبقية</p>
                    <p class="text-xl font-bold text-gray-900">SAR {{ erp_money($remainingInstallments) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M3 0a1 1 0 0 0-1 1v12.5a.5.5 0 0 0 .777.416L6 12.101l3.223 1.815A.5.5 0 0 0 10 13.5V1a1 1 0 0 0-1-1H3z"/></svg>
                </div>
            </div>
        </div>
    @endif

    {{-- جدول الحركات --}}
    @if($customer && count($transactions) > 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        كشف حساب: {{ $customer->name }}
                    </p>
                    @if($fromDate || $toDate)
                        <p class="text-xs text-gray-500 mt-0.5">
                            الفترة: من {{ $fromDate ?: '—' }} إلى {{ $toDate ?: '—' }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-medium">التاريخ</th>
                            <th class="py-3 px-4 font-medium">النوع</th>
                            <th class="py-3 px-4 font-medium">المرجع</th>
                            <th class="py-3 px-4 font-medium">البيان</th>
                            <th class="py-3 px-4 font-medium text-left">مدين</th>
                            <th class="py-3 px-4 font-medium text-left">دائن</th>
                            <th class="py-3 px-4 font-medium text-left">الرصيد التراكمي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/60">
                                <td class="py-2.5 px-4 text-gray-800 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="py-2.5 px-4 text-gray-700 whitespace-nowrap">
                                    @if($row['type'] === 'invoice')
                                        فاتورة
                                    @elseif($row['type'] === 'payment')
                                        سداد
                                    @elseif($row['type'] === 'contract')
                                        عقد
                                    @else
                                        حركة
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-gray-700 whitespace-nowrap">{{ $row['ref'] }}</td>
                                <td class="py-2.5 px-4 text-gray-700">{{ $row['desc'] }}</td>
                                <td class="py-2.5 px-4 text-left text-gray-900 tabular-nums">{{ $row['debit'] > 0 ? erp_money($row['debit']) : '—' }}</td>
                                <td class="py-2.5 px-4 text-left text-gray-900 tabular-nums">{{ $row['credit'] > 0 ? erp_money($row['credit']) : '—' }}</td>
                                <td class="py-2.5 px-4 text-left font-semibold text-gray-900 tabular-nums">{{ erp_money($row['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($customerId)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-800">
            لا توجد حركات للفترة المحددة لهذا العميل.
        </div>
    @else
        <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl px-4 py-10 text-center text-sm text-gray-500">
            يرجى اختيار عميل وعرض الفترة لعرض كشف حسابه.
        </div>
    @endif
</div>

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        td.text-left.tabular-nums,
        th.text-left {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .overflow-x-auto { overflow: visible !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const form = document.getElementById('statement-filter-form');
    const rangeInput = document.getElementById('range');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    document.querySelectorAll('#statement-filter-form button[data-range]').forEach(function(btn) {
        btn.addEventListener('click', function () {
            rangeInput.value = this.getAttribute('data-range');
            if (fromInput) fromInput.value = '';
            if (toInput) toInput.value = '';
            if (form) form.submit();
        });
    });
})();
</script>
@endpush
@endsection

