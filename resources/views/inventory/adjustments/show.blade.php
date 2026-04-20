@extends('layouts.app')

@section('title', 'تفاصيل التسوية ' . $adjustment->adjustment_number . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.adjustments.index') }}" class="text-gray-500 hover:text-indigo-600">تسويات المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تفاصيل التسوية</span>
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
        <h1 class="text-2xl font-bold text-gray-900">تفاصيل التسوية — {{ $adjustment->adjustment_number }}</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" onclick="window.print();">طباعة</button>
            <a href="{{ route('inventory.adjustments.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
        </div>
    </header>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">بيانات التسوية</div>
        <div class="p-4 md:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.adjustment_number" /> رقم التسوية</span>
                    <p class="font-semibold text-gray-900">{{ $adjustment->adjustment_number }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.adjustment_date" /> التاريخ</span>
                    <p class="font-semibold text-gray-900">{{ $adjustment->adjustment_date?->format('Y-m-d') }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.adjustment_warehouse" /> المستودع</span>
                    <p class="font-semibold text-gray-900">{{ $adjustment->warehouse?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.adjustment_type" /> نوع التسوية</span>
                    <p class="font-semibold text-gray-900">{{ $adjustment->type === 'add' ? 'إضافة كمية' : 'خصم كمية' }}</p>
                </div>
                @if($adjustment->costCenter)
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.adjustment_cost_center" /> مركز التكلفة</span>
                    <p class="font-semibold text-gray-900">{{ $adjustment->costCenter->name }} ({{ $adjustment->costCenter->code }})</p>
                </div>
                @endif
            </div>
            @if($adjustment->notes)
            <div class="mt-4 border-t border-gray-100 pt-4">
                <span class="mb-1 block text-xs text-gray-500">ملاحظات</span>
                <p class="text-gray-800">{{ $adjustment->notes }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">عناصر التسوية</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_line_index" /> #</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_item" /> الصنف</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_qty_line" /> الكمية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_unit_cost_line" /> التكلفة</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_line_total" /> الإجمالي</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_reason" /> السبب</th>
                    </tr>
                </thead>
                <tbody>
                    @php $reasons = config('inventory.adjustment_reasons', []); @endphp
                    @foreach($adjustment->items as $i => $line)
                    @php
                        $lineTotal = (float) $line->quantity * (float) $line->unit_cost;
                        $reasonLabel = $line->reason ? ($reasons[$line->reason] ?? $line->reason) : '—';
                    @endphp
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 text-gray-800">{{ $i + 1 }}</td>
                        <td class="px-3 py-3 font-medium text-gray-900">{{ $line->item?->name_ar ?? $line->item?->code ?? '—' }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_qty($line->quantity) }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_money($line->unit_cost ?? 0) }}</td>
                        <td class="px-3 py-3 font-medium tabular-nums text-gray-900">{{ erp_money($lineTotal) }} SAR</td>
                        <td class="px-3 py-3 text-gray-700">{{ $reasonLabel }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-6 border-t border-gray-200 px-4 py-3 text-sm text-gray-700">
            <span class="inline-flex items-center gap-1"><x-info field="inventory.adjustment_total_qty" /><span>إجمالي الكمية: <strong class="tabular-nums text-gray-900">{{ erp_qty($adjustment->total_quantity ?? 0) }}</strong></span></span>
            <span class="inline-flex items-center gap-1"><x-info field="inventory.adjustment_total_value" /><span>إجمالي القيمة: <strong class="tabular-nums text-gray-900">{{ erp_money($adjustment->total_value ?? 0) }} SAR</strong></span></span>
        </div>
    </section>
</div>
@if(request()->get('print'))
<script>window.onload = function() { window.print(); }</script>
@endif
@endsection
