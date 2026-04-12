@extends('layouts.app')

@section('title', 'أوامر التوريد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أوامر التوريد</span>
@endsection

@section('content')
@php
    $statusLabels = [
        'pending' => 'قيد الانتظار',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغى',
    ];
@endphp
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                أوامر التوريد
                <x-info field="sales.delivery_orders_index_title" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">قائمة أوامر التوريد المرتبطة بأوامر البيع</p>
        </div>
        <a href="{{ route('sales.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">أوامر البيع</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرقم <x-info field="sales.delivery_list_number" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">أمر البيع <x-info field="sales.delivery_index_so" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">العميل <x-info field="sales.delivery_index_customer" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الحالة <x-info field="sales.delivery_list_status" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">التاريخ <x-info field="sales.delivery_list_date" /></span></th>
                        <th class="py-3 px-4 font-medium w-28"><span class="inline-flex items-center gap-1">إجراء <x-info field="sales.delivery_list_actions" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveryOrders as $d)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $d->delivery_number }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('sales.orders.show', $d->sales_order_id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">SO-{{ $d->sales_order_id }}</a>
                            </td>
                            <td class="py-3 px-4 text-gray-700">{{ $d->salesOrder?->customer?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <span class="text-gray-800">{{ $statusLabels[$d->status] ?? $d->status }}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $d->delivery_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('sales.delivery-orders.show', $d->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-gray-500">لا توجد أوامر توريد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deliveryOrders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $deliveryOrders->links() }}</div>
        @endif
    </div>
</div>
@endsection
