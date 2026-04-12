@extends('layouts.app')

@section('title', 'عرض أمر شراء - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر الشراء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عرض أمر شراء</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div>
                <p class="text-xs text-gray-500 mb-1"><x-info field="procurement.purchase_order_code" /> رقم الأمر</p>
                <h1 class="text-2xl font-bold text-gray-900 whitespace-nowrap">{{ $order->display_order_number }}</h1>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $order->status }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($order->status === 'معلق')
                <a href="{{ route('purchases.orders.edit', $order) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">تعديل</a>
            @endif
            <a href="{{ route('purchases.orders.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">رجوع</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">المورد</div>
            <div class="text-sm font-semibold text-gray-900">{{ $order->supplier?->getLocalizedDisplayName() ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">تاريخ الأمر</div>
            <div class="text-sm font-semibold text-gray-900">{{ $order->order_date?->format('Y-m-d') ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">تاريخ التسليم المتوقع</div>
            <div class="text-sm font-semibold text-gray-900">{{ $order->expected_delivery_date?->format('Y-m-d') ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">الإجمالي</div>
            <div class="text-sm font-semibold text-gray-900">SAR {{ number_format((float) $order->total, 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                    <tr>
                        <th class="py-3 px-4 font-medium">الكود</th>
                        <th class="py-3 px-4 font-medium">الوصف</th>
                        <th class="py-3 px-4 font-medium">الكمية</th>
                        <th class="py-3 px-4 font-medium">سعر الوحدة</th>
                        <th class="py-3 px-4 font-medium">نسبة الضريبة</th>
                        <th class="py-3 px-4 font-medium">قيمة الضريبة</th>
                        <th class="py-3 px-4 font-medium">إجمالي السطر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $line)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">{{ $line->item?->code ?? '—' }}</td>
                            <td class="py-3 px-4">{{ $line->description ?: ($line->item?->name_ar ?? '—') }}</td>
                            <td class="py-3 px-4">{{ number_format((float) $line->quantity, 4) }}</td>
                            <td class="py-3 px-4">SAR {{ number_format((float) $line->unit_price, 4) }}</td>
                            <td class="py-3 px-4">{{ number_format((float) $line->tax_percent, 2) }}%</td>
                            <td class="py-3 px-4">SAR {{ number_format((float) $line->vat_amount, 4) }}</td>
                            <td class="py-3 px-4 font-medium">SAR {{ number_format((float) ($line->total_amount ?? $line->line_total), 4) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500">لا توجد بنود.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
