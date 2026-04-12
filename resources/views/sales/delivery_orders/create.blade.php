@extends('layouts.app')

@section('title', 'أمر توريد جديد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.orders.show', $salesOrder->id) }}" class="text-gray-500 hover:text-indigo-600">SO-{{ $salesOrder->id }}</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أمر توريد جديد</span>
@endsection

@section('content')
@php
    $typeLabels = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة'];
@endphp
<div class="max-w-full" dir="rtl">
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">إنشاء أمر توريد</h1>
            <p class="text-sm text-gray-500 mt-1">أمر البيع SO-{{ $salesOrder->id }} — {{ $salesOrder->customer?->name ?? '—' }}</p>
        </div>
        <a href="{{ route('sales.orders.show', $salesOrder->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">إلغاء</a>
    </div>

    <form method="POST" action="{{ route('sales.orders.delivery-orders.store', $salesOrder->id) }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ التوريد <x-info field="sales.delivery_date" /></label>
                <input type="date" name="delivery_date" value="{{ old('delivery_date', now()->format('Y-m-d')) }}" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500">
                @error('delivery_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات <x-info field="sales.delivery_notes" /></label>
                <textarea name="notes" rows="2" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" placeholder="اختياري">{{ old('notes') }}</textarea>
                @error('notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <span class="font-semibold text-gray-800">الكميات</span>
                <x-info field="sales.delivery_line_qty" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="sales.delivery_col_item" /></span></th>
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">النوع <x-info field="sales.delivery_col_type" /></span></th>
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">المطلوب بالأمر <x-info field="sales.delivery_col_ordered" /></span></th>
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">المتبقي <x-info field="sales.delivery_col_remaining" /></span></th>
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">كمية التوريد <x-info field="sales.delivery_col_qty" /></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $idx => $line)
                            <tr class="border-b border-gray-100">
                                <td class="py-3 px-4 text-gray-900">{{ $line['code'] }} — {{ $line['name_ar'] }}</td>
                                <td class="py-3 px-4 text-gray-600">{{ $typeLabels[$line['type']] ?? $line['type'] }}</td>
                                <td class="py-3 px-4">{{ rtrim(rtrim(number_format($line['ordered'], 4, '.', ''), '0'), '.') }}</td>
                                <td class="py-3 px-4 font-medium {{ $line['remaining'] > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ rtrim(rtrim(number_format($line['remaining'], 4, '.', ''), '0'), '.') }}</td>
                                <td class="py-3 px-4">
                                    <input type="hidden" name="lines[{{ $idx }}][sales_order_item_id]" value="{{ $line['sales_order_item_id'] }}">
                                    @if($line['remaining'] > 0)
                                        <input type="number" inputmode="decimal" name="lines[{{ $idx }}][quantity]" value="{{ old('lines.'.$idx.'.quantity', '0') }}" min="0" max="{{ $line['remaining'] }}" step="any" class="w-36 py-2 px-3 border border-gray-300 rounded-lg text-sm text-right">
                                    @else
                                        <input type="hidden" name="lines[{{ $idx }}][quantity]" value="0">
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">حفظ أمر التوريد</button>
        </div>
    </form>
</div>
@endsection
