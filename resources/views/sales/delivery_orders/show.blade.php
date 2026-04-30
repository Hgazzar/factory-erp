@extends('layouts.app')

@section('title', $deliveryOrder->delivery_number . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.orders.show', $deliveryOrder->sales_order_id) }}" class="text-gray-500 hover:text-indigo-600">SO-{{ $deliveryOrder->sales_order_id }}</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $deliveryOrder->delivery_number }}</span>
@endsection

@section('content')
@php
    $typeLabels = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة'];
@endphp
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex flex-wrap items-center gap-2">
                {{ $deliveryOrder->delivery_number }}
                <x-info field="sales.delivery_number" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                أمر البيع SO-{{ $deliveryOrder->sales_order_id }} — {{ $deliveryOrder->salesOrder?->customer?->name ?? '—' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 justify-end">
            <a href="{{ route('sales.orders.show', $deliveryOrder->sales_order_id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">أمر البيع</a>
            @if(auth()->user()->isAdminOrSuperAdmin())
                <a href="{{ route('services.orders.create', ['delivery_order_id' => $deliveryOrder->id]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-sky-200 bg-sky-50 text-sky-900 text-sm font-medium hover:bg-sky-100">إنشاء طلب خدمة</a>
            @endif
            @if($deliveryOrder->status === 'pending')
                <form method="POST" action="{{ route('sales.delivery-orders.deliver', $deliveryOrder->id) }}" class="inline-flex items-center gap-2" onsubmit="return confirm('تأكيد التسليم؟ سيتم خصم المخزون للأصناف القابلة للتخزين.');">
                    @csrf
                    <x-info field="sales.delivery_confirm" />
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #16a34a;">تأكيد التسليم</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-gray-500 flex items-center gap-1">الحالة <x-info field="sales.delivery_status" /></span>
                @if($deliveryOrder->status === 'pending')
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">قيد الانتظار</span>
                @elseif($deliveryOrder->status === 'delivered')
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">تم التسليم</span>
                @else
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">ملغى</span>
                @endif
            </div>
            <div class="flex justify-between"><span class="text-gray-500">تاريخ التوريد</span><span class="font-medium text-gray-900">{{ $deliveryOrder->delivery_date?->format('Y-m-d') ?? '—' }}</span></div>
            @if($deliveryOrder->notes)
                <div class="pt-2 border-t border-gray-100"><span class="text-gray-500">ملاحظات</span><p class="text-gray-800 mt-1">{{ $deliveryOrder->notes }}</p></div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800">بنود التوريد</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="sales.delivery_col_item" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">النوع <x-info field="sales.delivery_col_type" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الكمية <x-info field="sales.delivery_col_qty" /></span></th>
                        @if($deliveryOrder->status === 'pending')
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرصيد الحالي <x-info field="sales.delivery_detail_col_stock" /></span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveryOrder->items as $row)
                        @php $t = $row->item?->type; @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">{{ $row->item?->code }} — {{ $row->item?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $typeLabels[$t] ?? $t }}</td>
                            <td class="py-3 px-4 font-medium">{{ rtrim(rtrim(number_format((float) $row->quantity, 4, '.', ''), '0'), '.') }}</td>
                            @if($deliveryOrder->status === 'pending')
                                <td class="py-3 px-4 text-gray-600">
                                    @if(in_array($t, ['raw_material', 'finished_good'], true))
                                        {{ rtrim(rtrim(number_format((float) ($row->item?->current_stock ?? 0), 4, '.', ''), '0'), '.') }}
                                    @else
                                        <span class="text-gray-400">— (خدمة)</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
