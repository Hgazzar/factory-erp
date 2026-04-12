@extends('layouts.app')

@section('title', 'إذن إضافة مخزني ' . $stockIn->document_number . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تفاصيل الإذن</span>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 no-print">
        <h1 class="text-2xl font-bold text-gray-900">إذن إضافة مخزني</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition" title="طباعة إيصال الاستلام">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1h-1A1.5 1.5 0 0 0 .5 8.5v2A1.5 1.5 0 0 0 2 12h1a.5.5 0 0 0 0-1H2a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h1z"/><path d="M5.5 7.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/><path d="M14 8a.5.5 0 0 0 0 1h1a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-1a.5.5 0 0 0 0 1h1a1.5 1.5 0 0 0 1.5-1.5v-2A1.5 1.5 0 0 0 14 8h-1z"/><path d="M4.5 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-3z"/></svg>
                طباعة الإيصال
            </button>
            <a href="{{ route('inventory.stock-in.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إذن جديد</a>
            <a href="{{ route('inventory.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">المخزون</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-900 text-sm no-print">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <div class="flex flex-wrap justify-between gap-4 mb-6 border-b border-gray-100 pb-4">
            <div>
                <p class="text-sm text-gray-500 mb-1"><x-info field="inventory.stock_in_document" /> رقم الإذن</p>
                <p class="text-xl font-bold text-gray-900">{{ $stockIn->document_number }}</p>
            </div>
            <div class="text-left">
                <p class="text-sm text-gray-500 mb-1">التاريخ</p>
                <p class="font-semibold text-gray-900">{{ $stockIn->date?->format('Y-m-d') }}</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">المورد</span>
                <p class="font-medium text-gray-900">{{ $stockIn->supplier?->name ?? '—' }}</p>
            </div>
            <div>
                <span class="text-gray-500"><x-info field="inventory.stock_in_settlement" /> التسوية المحاسبية</span>
                <p class="font-medium text-gray-900">{{ ($stockIn->settlement_type ?? 'on_account') === 'cash' ? 'دفع نقدي (1010)' : 'على ذمة المورد (2010)' }}</p>
            </div>
            @if($stockIn->reference)
            <div>
                <span class="text-gray-500">مرجع خارجي</span>
                <p class="font-medium text-gray-900">{{ $stockIn->reference }}</p>
            </div>
            @endif
            @if($stockIn->notes)
            <div class="md:col-span-2">
                <span class="text-gray-500">ملاحظات</span>
                <p class="text-gray-800">{{ $stockIn->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 font-semibold text-gray-900">البنود</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_product" /> المنتج</th>
                        <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_warehouse" /> المستودع</th>
                        <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_qty" /> الكمية</th>
                        <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_purchase_price" /> سعر الشراء</th>
                        <th class="py-3 px-3 font-medium text-gray-600"><x-info field="inventory.stock_in_line_total" /> الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockIn->lines as $line)
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3">{{ $line->item?->display_name ?? $line->item?->name_ar ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $line->warehouse?->name_ar ?? '—' }}</td>
                        <td class="py-2 px-3">{{ number_format((float) $line->quantity, 4) }}</td>
                        <td class="py-2 px-3">SAR {{ number_format((float) $line->purchase_price, 2) }}</td>
                        <td class="py-2 px-3 font-medium">SAR {{ number_format((float) $line->quantity * (float) $line->purchase_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td colspan="4" class="py-3 px-3 text-left">الإجمالي</td>
                        <td class="py-3 px-3">SAR {{ number_format((float) $stockIn->line_value_total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($autoPrint)
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
@endif
@endsection
