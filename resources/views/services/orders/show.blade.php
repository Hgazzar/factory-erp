@extends('layouts.app')

@section('title', $order->reference_number . ' - MIRADA ERP')

@section('content')
@php
    $typeLabels = ['install' => 'تركيب', 'maintenance' => 'صيانة', 'repair' => 'إصلاح'];
    $priorityLabels = ['normal' => 'عادي', 'urgent' => 'عاجل'];
    $statusLabels = [
        'open' => 'مفتوح',
        'assigned' => 'مسند لفني',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ];
    $closed = in_array($order->status, [\App\Models\ServiceOrder::STATUS_COMPLETED, \App\Models\ServiceOrder::STATUS_CANCELLED], true);
@endphp
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('services.orders.index') }}" class="text-sm text-indigo-600 hover:underline">← طلبات الخدمة</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2 flex flex-wrap items-center gap-2">
                <span class="font-mono">{{ $order->reference_number }}</span>
                <x-info field="services.order_detail_title" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">{{ $order->customer?->name ?? '—' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(! $closed)
                <form method="post" action="{{ route('services.orders.cancel', $order) }}" class="inline" onsubmit="return confirm('تأكيد إلغاء الطلب؟');">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-lg border border-red-200 text-red-700 text-sm font-medium hover:bg-red-50">إلغاء الطلب</button>
                </form>
            @endif
        </div>
    </div>

    @if($order->outside_warranty)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <strong>خارج الضمان.</strong> تُحتسب الخدمة كـ <strong>مدفوعة</strong> ويُمكن توليد مسودة فاتورة عند الإغلاق.
        </div>
    @elseif($order->is_paid_service && in_array($order->service_type, ['maintenance', 'repair'], true))
        <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800">
            الخدمة <strong>مدفوعة</strong> (بدون تغطية ضمان أو بلا أصل مثبت محدد).
        </div>
    @elseif(! $order->is_paid_service && in_array($order->service_type, ['maintenance', 'repair'], true))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
            الخدمة ضمن فترة الضمان — <strong>غير مدفوعة</strong> (لا تُولَّد فاتورة تلقائياً عند الإغلاق).
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500"><x-info field="services.type_field" /> النوع</span><span class="font-medium">{{ $typeLabels[$order->service_type] ?? $order->service_type }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500"><x-info field="services.priority_field" /> الأولوية</span><span class="font-medium {{ $order->priority === 'urgent' ? 'text-red-600' : '' }}">{{ $priorityLabels[$order->priority] ?? $order->priority }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">الحالة</span><span class="font-medium">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">الفني</span><span class="font-medium">{{ $order->assignedTechnician?->name ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500"><x-info field="services.warehouse_field" /> المستودع</span><span class="font-medium">{{ $order->warehouse?->name_ar ?? $order->warehouse?->code ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">تاريخ التنفيذ</span><span class="font-medium">{{ $order->executed_at?->format('Y-m-d') ?? '—' }}</span></div>
            @if($order->salesOrder)
                <div class="flex justify-between"><span class="text-gray-500">أمر البيع</span><a href="{{ route('sales.orders.show', $order->sales_order_id) }}" class="text-indigo-600 hover:underline font-mono">SO-{{ $order->sales_order_id }}</a></div>
            @endif
            @if($order->deliveryOrder)
                <div class="flex justify-between"><span class="text-gray-500">أمر التوريد</span><a href="{{ route('sales.delivery-orders.show', $order->delivery_order_id) }}" class="text-indigo-600 hover:underline">{{ $order->deliveryOrder->delivery_number }}</a></div>
            @endif
            @if($order->installedAsset)
                <div class="pt-2 border-t border-gray-100">
                    <span class="text-gray-500 block mb-1">الأصل المثبت</span>
                    <span class="font-medium">{{ $order->installedAsset->item?->name_ar ?? $order->installedAsset->item?->code ?? '—' }}</span>
                    @if($order->installedAsset->warranty_end)
                        <span class="text-gray-600 mr-2">· ضمان حتى {{ $order->installedAsset->warranty_end->format('Y-m-d') }}</span>
                    @endif
                </div>
            @endif
            @if($order->description)
                <div class="pt-2 border-t border-gray-100"><span class="text-gray-500">الوصف</span><p class="text-gray-800 mt-1">{{ $order->description }}</p></div>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <p class="font-semibold text-gray-800 mb-2">المالية</p>
            <div class="flex justify-between"><span class="text-gray-500">خدمة مدفوعة</span><span>{{ $order->is_paid_service ? 'نعم' : 'لا' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">أجرة يدوية</span><span>{{ $order->labor_amount !== null ? number_format((float) $order->labor_amount, 2) : '—' }}</span></div>
            @if($order->sales_invoice_id && $order->salesInvoice)
                <div class="pt-2 border-t border-gray-100">
                    <a href="{{ route('sales.invoices.index', ['q' => $order->salesInvoice->reference]) }}" class="text-indigo-600 hover:underline text-sm">مسودة/فاتورة: {{ $order->salesInvoice->reference ?? '#' . $order->sales_invoice_id }}</a>
                </div>
            @endif
        </div>
    </div>

    @if(! $closed)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><x-info field="services.assign_technician" /> تعيين فني</h2>
                <form method="post" action="{{ route('services.orders.assign', $order) }}" class="flex flex-col gap-3">
                    @csrf
                    <select name="assigned_technician_id" class="rounded-lg border-gray-300 text-sm" required>
                        <option value="">— اختر فنياً —</option>
                        @foreach($technicians as $t)
                            <option value="{{ $t->id }}" @selected($order->assigned_technician_id == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium w-fit">حفظ التعيين</button>
                </form>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><x-info field="services.add_part" /> صرف قطعة غيار</h2>
                <form method="post" action="{{ route('services.orders.parts.store', $order) }}" class="flex flex-col gap-3">
                    @csrf
                    <select name="item_id" class="rounded-lg border-gray-300 text-sm" required>
                        <option value="">— الصنف —</option>
                        @foreach($stockableItems as $it)
                            <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name_ar }}</option>
                        @endforeach
                    </select>
                    <input type="number" inputmode="decimal" name="quantity" step="any" min="0.0001" placeholder="الكمية" class="rounded-lg border-gray-300 text-sm" required>
                    <button type="submit" class="px-4 py-2.5 rounded-lg text-white text-sm font-medium w-fit" style="background: #2563eb;">صرف من المخزون</button>
                </form>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800 flex items-center gap-2">
            <x-info field="services.parts_table" /> قطع الغيار المستخدمة
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">الصنف</th>
                        <th class="py-3 px-4 font-medium">الكمية</th>
                        <th class="py-3 px-4 font-medium">تكلفة الوحدة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->parts as $p)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">{{ $p->item?->code }} — {{ $p->item?->name_ar }}</td>
                            <td class="py-3 px-4">{{ rtrim(rtrim(number_format((float) $p->quantity, 4, '.', ''), '0'), '.') }}</td>
                            <td class="py-3 px-4">{{ number_format((float) $p->unit_cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-gray-500">لا توجد قطع مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(! $closed)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><x-info field="services.complete_order" /> إغلاق الطلب</h2>
            <p class="text-sm text-gray-600 mb-4">عند الإغلاق يُسجَّل تاريخ التنفيذ. إذا كانت الخدمة مدفوعة ووجدت بنود (قطع أو أجرة)، تُنشأ <strong>مسودة فاتورة</strong> مرتبطة.</p>
            <form method="post" action="{{ route('services.orders.complete', $order) }}" class="space-y-3" onsubmit="return confirm('تأكيد إغلاق طلب الخدمة؟');">
                @csrf
                <div class="max-w-xs">
                    <label class="block text-xs text-gray-500 mb-1"><x-info field="services.labor_amount_field" /> أجرة يدوية (اختياري)</label>
                    <input type="number" inputmode="decimal" name="labor_amount" step="any" min="0" value="{{ old('labor_amount', $order->labor_amount) }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="w-full max-w-xs px-5 py-3 rounded-lg text-white text-sm font-bold" style="background:#15803d; display:block;">
                    <span>إغلاق الطلب</span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
