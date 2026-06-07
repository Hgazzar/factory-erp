@extends('layouts.app')

@section('title', 'إدارة طلبات المتجر')

@section('breadcrumb')
    <a href="{{ route('pos.dashboard') }}" class="text-gray-500 hover:text-indigo-600">نقاط البيع</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">طلبات المتجر</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-7xl mx-auto space-y-6" x-data="{ receiptUrl: null, receiptInvoice: '' }">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">إدارة طلبات المتجر الإلكتروني</h1>
            <p class="text-sm text-gray-500 mt-1">COD · تحويل بنكي · بطاقة (Paymob) — مع إشعارات واتساب عبر Queue</p>
        </div>
        <a href="{{ route('settings.store.edit') }}" class="text-sm text-indigo-600 hover:underline">إعدادات المتجر</a>
    </header>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 text-green-800 px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl bg-red-50 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="get" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-end gap-4">
        <div class="min-w-[12rem] flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <x-info field="pos.online_orders_filter_status" /> تصفية بالحالة
            </label>
            <x-searchable-select
                name="status"
                :options="collect($statusOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all()"
                :value="$status ?: 'all'"
                value-key="value"
                label-key="label"
                :searchable="false"
                empty-option="false"
            />
        </div>
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700">تطبيق</button>
        @if($status !== '' && $status !== 'all')
            <a href="{{ route('pos.orders.index') }}" class="text-sm text-gray-500 hover:text-indigo-600">إظهار الكل</a>
        @endif
    </form>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-semibold text-gray-800">
            <x-info field="pos.online_orders_list" /> الطلبات ({{ $orders->total() }})
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_invoice" /> الفاتورة</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_customer" /> العميل</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_payment" /> الدفع</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_status" /> الحالة</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_total" /> الإجمالي</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="pos.online_order_date" /> التاريخ</th>
                        <th class="px-4 py-3 text-right font-semibold">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                    @php
                        $statusLabel = \App\Models\PosSale::onlineOrderStatusLabels()[$order->status] ?? $order->status;
                        $statusClass = match($order->status) {
                            \App\Models\PosSale::STATUS_PENDING => 'bg-amber-100 text-amber-800',
                            \App\Models\PosSale::STATUS_PENDING_VERIFICATION => 'bg-orange-100 text-orange-800',
                            \App\Models\PosSale::STATUS_DELIVERED => 'bg-blue-100 text-blue-800',
                            \App\Models\PosSale::STATUS_COLLECTED, \App\Models\PosSale::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800',
                            \App\Models\PosSale::STATUS_VOIDED => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $paymentLabel = match($order->payment_method) {
                            \App\Models\PosSale::PAYMENT_CARD => 'بطاقة',
                            \App\Models\PosSale::PAYMENT_MANUAL_TRANSFER => 'تحويل',
                            default => 'COD',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $order->invoice_number }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-400" dir="ltr">{{ $order->customer_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-bold text-gray-700">{{ $paymentLabel }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 font-bold tabular-nums">{{ number_format((float) $order->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2 items-center">
                                <a href="{{ route('pos.orders.invoice.pdf', $order) }}" target="_blank" rel="noopener"
                                   class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 text-xs font-bold hover:bg-gray-50">
                                    <x-info field="pos.online_order_invoice_pdf" /> PDF
                                </a>

                                @if($order->payment_method === \App\Models\PosSale::PAYMENT_MANUAL_TRANSFER && $order->payment_receipt_path)
                                    <button type="button"
                                            @click="receiptUrl='{{ route('pos.orders.payment-receipt', $order) }}'; receiptInvoice='{{ $order->invoice_number }}'"
                                            class="px-3 py-1.5 rounded-lg border border-amber-200 text-amber-800 text-xs font-bold hover:bg-amber-50">
                                        إيصال التحويل
                                    </button>
                                @endif

                                @if($order->payment_method === \App\Models\PosSale::PAYMENT_COD && $order->status === \App\Models\PosSale::STATUS_PENDING)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('تأكيد التسليم؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold">تم التسليم</button>
                                    </form>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('تحصيل مباشر؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">تحصيل مباشر</button>
                                    </form>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('إلغاء؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs font-bold">إلغاء</button>
                                    </form>
                                @elseif($order->payment_method === \App\Models\PosSale::PAYMENT_COD && $order->status === \App\Models\PosSale::STATUS_DELIVERED)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('تأكيد التحصيل؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">تم التحصيل</button>
                                    </form>
                                @elseif($order->payment_method === \App\Models\PosSale::PAYMENT_MANUAL_TRANSFER && $order->status === \App\Models\PosSale::STATUS_PENDING_VERIFICATION)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('تأكيد التحويل وترحيل القيد؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">تأكيد التحويل</button>
                                    </form>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" onsubmit="return confirm('إلغاء؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs font-bold">إلغاء</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">لا توجد طلبات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>

    <div x-show="receiptUrl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="receiptUrl=null">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-4" @click.outside="receiptUrl=null">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900">إيصال التحويل — <span x-text="receiptInvoice"></span></h3>
                <button type="button" @click="receiptUrl=null" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <img :src="receiptUrl" alt="إيصال التحويل" class="max-h-[70vh] w-full object-contain rounded-lg border border-gray-100">
        </div>
    </div>
</div>
@endsection
