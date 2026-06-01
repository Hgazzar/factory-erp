@extends('layouts.app')

@section('title', 'أوامر الإنتاج - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أوامر الإنتاج</span>
@endsection

@section('content')
@php
    $statusLabels = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ];
@endphp
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                أوامر الإنتاج
                <x-info field="production.list_actions" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">ربط المنتج التام بالمواد الخام وتحديث المخزون عند الإتمام</p>
        </div>
        <a href="{{ route('production-orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">أمر إنتاج جديد</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرقم <x-info field="production.order_number" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الحالة <x-info field="production.order_status" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">بداية <x-info field="production.start_date" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">نهاية <x-info field="production.end_date" /></span></th>
                        <th class="py-3 px-4 font-medium">البنود</th>
                        <th class="py-3 px-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $order->production_number }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($order->status === 'pending') bg-amber-100 text-amber-800
                                    @elseif($order->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'completed') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-700">{{ $order->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $order->end_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $order->production_items_count }} منتج / {{ $order->ingredients_count }} خامة</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('production-orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-gray-500">لا توجد أوامر إنتاج بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
