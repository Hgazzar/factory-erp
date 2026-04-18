@extends('layouts.app')

@section('title', 'أمر بيع SO-' . $salesOrder->id . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر البيع</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">SO-{{ $salesOrder->id }}</span>
@endsection

@section('content')
@php
    $typeLabels = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة'];
    $canCreateDelivery = $salesOrder->status !== 'ملغي' && $salesOrder->items->contains(fn ($l) => $l->remainingQuantityForDelivery() > 0);
    $pending = $salesOrder->status === \App\Models\SalesOrder::STATUS_PENDING;
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
            <h1 class="text-2xl font-bold text-gray-900">أمر بيع SO-{{ $salesOrder->id }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $salesOrder->customer?->name ?? '—' }} · {{ $salesOrder->order_date?->format('Y-m-d') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 justify-end">
            <a href="{{ route('sales.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">رجوع للقائمة</a>
            @if($canCreateDelivery)
                <div class="inline-flex items-center gap-2">
                    <x-info field="sales.order_delivery_action" />
                    <a href="{{ route('sales.orders.delivery-orders.create', $salesOrder->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">إنشاء أمر توريد</a>
                </div>
            @endif
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('services.orders.create', ['sales_order_id' => $salesOrder->id]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-sky-200 bg-sky-50 text-sky-900 text-sm font-medium hover:bg-sky-100">إنشاء طلب خدمة</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">الحالة</span><span class="font-medium text-gray-900">{{ $salesOrder->status }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">الإجمالي</span><span class="font-medium text-gray-900">SAR {{ number_format((float) $salesOrder->total, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">التسليم المتوقع</span><span class="font-medium text-gray-900">{{ $salesOrder->expected_delivery?->format('Y-m-d') ?? '—' }}</span></div>
            @if($salesOrder->notes)
                <div class="pt-2 border-t border-gray-100"><span class="text-gray-500">ملاحظات</span><p class="text-gray-800 mt-1">{{ $salesOrder->notes }}</p></div>
            @endif
        </div>
    </div>

    @if($salesOrder->accountingJournalEntry)
        <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm text-indigo-900 flex flex-wrap items-center justify-between gap-3">
            <span class="font-medium">مرتبط بقيد يومية: #{{ $salesOrder->accountingJournalEntry->id }}</span>
            <a href="{{ route('finance.journals.edit', ['journal' => $salesOrder->accountingJournalEntry]) }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-50">فتح القيد</a>
        </div>
    @elseif($pending)
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-base font-semibold text-gray-900">الإكمال المحاسبي</h2>
            <p class="mb-4 text-sm text-gray-600 flex flex-wrap items-center gap-2">
                <x-info field="sales.order_complete_accounting" />
                <span>بعد الترحيل تُحدَّث الحالة إلى «مكتمل» ويُنشأ قيد المبيعات وتكلفة البضاعة عند توفر تكلفة للأصناف.</span>
            </p>
            <form method="POST" action="{{ route('sales.orders.complete-accounting', $salesOrder) }}" class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                @csrf
                <div class="space-y-2">
                    <span class="block text-sm font-medium text-gray-700">طريقة التسوية <span class="text-red-500">*</span></span>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <input type="radio" name="settlement" value="receivable" class="text-indigo-600" checked>
                            <span class="inline-flex items-center gap-1"><x-info field="sales.order_settlement_receivable" /> ذمم مدينة (آجل)</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <input type="radio" name="settlement" value="cash" class="text-indigo-600">
                            <span class="inline-flex items-center gap-1"><x-info field="sales.order_settlement_cash" /> نقدي (خزينة)</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">ترحيل وإكمال</button>
            </form>
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-gray-900">المرفقات</h2>
        @if($pending)
            <form method="POST" action="{{ route('sales.orders.attachments.store', $salesOrder) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-attachment-handler
                    hint-field="sales.order_attachments"
                    title="مرفقات أمر البيع"
                    :existing="$salesOrder->attachments"
                    :allow-delete="true"
                    help-text="إضافة ملفات جديدة دون حذف المرفقات الحالية. التخزين: sales-orders/{{ $salesOrder->id }} (مثل أوامر الشراء)."
                />
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">حفظ المرفقات الجديدة</button>
                </div>
            </form>
        @else
            <x-attachment-handler
                hint-field="sales.order_attachments"
                title="مرفقات أمر البيع"
                :existing="$salesOrder->attachments"
                :uploadable="false"
                :allow-delete="false"
                help-text="لإضافة مرفقات لاحقاً يجب أن يكون الأمر في حالة «معلق»؛ يمكن دائماً إرفاق مستندات على قيد اليومية من شاشة تعديل القيد."
            />
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
            <span class="font-semibold text-gray-800">بنود الأمر</span>
            <x-info field="sales.order_lines_table" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="sales.order_line_col_item" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">النوع <x-info field="sales.order_line_col_type" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الكمية <x-info field="sales.order_line_col_qty" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">متبقي للتوريد <x-info field="sales.order_line_col_remaining" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrder->items as $line)
                        @php $rem = $line->remainingQuantityForDelivery(); @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 text-gray-900">{{ $line->item?->code }} — {{ $line->item?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $typeLabels[$line->item?->type] ?? $line->item?->type ?? '—' }}</td>
                            <td class="py-3 px-4">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') }}</td>
                            <td class="py-3 px-4 font-medium {{ $rem > 0 ? 'text-amber-700' : 'text-gray-500' }}">{{ rtrim(rtrim(number_format($rem, 4, '.', ''), '0'), '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
            <span class="font-semibold text-gray-800">أوامر التوريد</span>
            <x-info field="sales.delivery_orders_list" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرقم <x-info field="sales.delivery_list_number" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الحالة <x-info field="sales.delivery_list_status" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">التاريخ <x-info field="sales.delivery_list_date" /></span></th>
                        <th class="py-3 px-4 font-medium w-40"><span class="inline-flex items-center gap-1">إجراء <x-info field="sales.delivery_list_actions" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesOrder->deliveryOrders as $d)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $d->delivery_number }}</td>
                            <td class="py-3 px-4">
                                @if($d->status === 'pending')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">قيد الانتظار</span>
                                @elseif($d->status === 'delivered')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">تم التسليم</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">ملغى</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $d->delivery_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('sales.delivery-orders.show', $d->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-gray-500">لا توجد أوامر توريد بعد</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
