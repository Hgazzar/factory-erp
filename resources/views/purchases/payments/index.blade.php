@extends('layouts.app')

@section('title', 'مدفوعات الموردين - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مدفوعات الموردين</span>
@endsection

@section('content')
@php
    $supplierFilterOptions = $suppliers->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim($s->getLocalizedDisplayName().' ('.($s->code ?? $s->id).')'),
    ])->all();
    $paymentMethodFilterOptions = collect($paymentMethods)->map(fn ($label, $value) => [
        'value' => $value,
        'label' => $label,
    ])->values()->all();
@endphp
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">مدفوعات الموردين</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('purchases.payments.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">تصدير</a>
            <a href="{{ route('purchases.payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                سند صرف جديد
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-0.5">إجمالي السندات</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($totalPayments) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-0.5">إجمالي المبالغ</p>
            <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalAmount, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-0.5">هذا الشهر</p>
            <p class="text-xl font-bold text-gray-900">SAR {{ number_format($thisMonthAmount, 2) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('purchases.payments.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[160px]">
                <x-info field="procurement.supplier_payment_method" class="block text-sm font-medium text-gray-700 mb-1">طريقة الدفع</x-info>
                <x-searchable-select
                    class="w-full"
                    name="payment_method"
                    id="filter_payment_method"
                    :options="$paymentMethodFilterOptions"
                    :value="request('payment_method')"
                    empty-label="جميع الطرق"
                    :searchable="false"
                />
            </div>
            <div class="min-w-[180px]">
                <x-info field="procurement.supplier_payment_supplier" class="block text-sm font-medium text-gray-700 mb-1">المورد</x-info>
                <x-searchable-select
                    class="w-full"
                    name="supplier_id"
                    id="filter_supplier_id"
                    :options="$supplierFilterOptions"
                    :value="request('supplier_id')"
                    empty-label="جميع الموردين"
                    placeholder="ابحث عن مورد..."
                />
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="مرجع، ملاحظات، مورد..." class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">تطبيق</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_reference">المرجع</x-info></th>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_supplier">المورد</x-info></th>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_date">التاريخ</x-info></th>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_method">طريقة الدفع</x-info></th>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_amount">المبلغ</x-info></th>
                        <th class="py-3 px-4 font-medium"><x-info field="procurement.supplier_payment_invoice">الفاتورة</x-info></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php
                            $allocatedInvoice = $payment->purchaseInvoices->first();
                            $allocatedAmount = $allocatedInvoice ? (float) ($allocatedInvoice->pivot->amount ?? 0) : 0;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $payment->reference ?: ('PMT-'.$payment->id) }}</td>
                            <td class="py-3 px-4 text-gray-900">{{ $payment->supplier?->getLocalizedDisplayName() ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $payment->date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $paymentMethods[$payment->payment_method] ?? ($payment->payment_method ?? '—') }}</td>
                            <td class="py-3 px-4 text-gray-900 font-medium">SAR {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($allocatedInvoice)
                                    {{ $allocatedInvoice->reference ?: ('PINV-'.$allocatedInvoice->id) }}
                                    <span class="text-xs text-gray-500">({{ number_format($allocatedAmount, 2) }})</span>
                                @else
                                    <span class="text-gray-400">ذمة عامة</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500">لا توجد مدفوعات للموردين</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
