@extends('layouts.app')

@section('title', 'تفاصيل التحويل ' . $transfer->transfer_number . ' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.transfers.index') }}" class="text-gray-500 hover:text-indigo-600">تحويلات المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تفاصيل التحويل</span>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-root { padding: 0; }
    }
</style>
@endpush

@section('content')
<div dir="rtl" class="print-root mx-auto w-full max-w-full space-y-6">
    <header class="no-print flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <h1 class="text-2xl font-bold text-gray-900">تفاصيل التحويل — {{ $transfer->transfer_number }}</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" onclick="window.print();">طباعة</button>
            <a href="{{ route('inventory.transfers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
        </div>
    </header>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">بيانات التحويل</div>
        <div class="p-4 md:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_number" /> رقم التحويل</span>
                    <p class="font-semibold text-gray-900">{{ $transfer->transfer_number }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_date" /> التاريخ</span>
                    <p class="font-semibold text-gray-900">{{ $transfer->transfer_date?->format('Y-m-d') }}</p>
                </div>
                @if($transfer->expected_arrival_date)
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_expected_arrival" /> تاريخ الوصول المتوقع</span>
                    <p class="font-semibold text-gray-900">{{ $transfer->expected_arrival_date->format('Y-m-d') }}</p>
                </div>
                @endif
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_from_wh" /> من مستودع</span>
                    <p class="font-semibold text-gray-900">{{ $transfer->sourceWarehouse?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_to_wh" /> إلى مستودع</span>
                    <p class="font-semibold text-gray-900">{{ $transfer->destWarehouse?->name_ar ?? '—' }}</p>
                </div>
            </div>
            @if($transfer->notes)
            <div class="mt-4 border-t border-gray-100 pt-4">
                <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.transfer_notes" /> ملاحظات</span>
                <p class="text-gray-800">{{ $transfer->notes }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800"><x-info field="inventory.transfer_lines_section" /> أصناف التحويل</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_index" /> #</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_name" /> اسم الصنف</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_qty" /> الكمية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_unit_cost" /> تكلفة الوحدة</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_total" /> الإجمالي</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_notes" /> ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $i => $line)
                    @php $lineTotal = (float) $line->quantity * (float) ($line->unit_cost ?? 0); @endphp
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 text-gray-800">{{ $i + 1 }}</td>
                        <td class="px-3 py-3 font-medium text-gray-900">{{ $line->item?->name_ar ?? $line->item?->code ?? '—' }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_qty($line->quantity) }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_money($line->unit_cost ?? 0) }}</td>
                        <td class="px-3 py-3 font-medium tabular-nums text-gray-900">{{ number_format($lineTotal, 2) }} SAR</td>
                        <td class="px-3 py-3 text-gray-500">{{ $line->notes ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
