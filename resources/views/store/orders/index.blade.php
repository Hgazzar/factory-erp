@extends('layouts.app')

@section('title', 'إدارة طلبات المتجر')

@section('breadcrumb')
    <a href="{{ route('pos.dashboard') }}" class="text-gray-500 hover:text-indigo-600">نقاط البيع</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">طلبات المتجر</span>
@endsection

@section('content')
<div dir="rtl" class="max-w-7xl mx-auto space-y-6"
     x-data="{ receiptUrl: null, receiptInvoice: '' }"
     @pos-receipt.window="receiptUrl = $event.detail.url; receiptInvoice = $event.detail.invoice">
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
                        <th class="px-4 py-3 text-center font-semibold w-14">إجراءات</th>
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
                        $showTransferReceipt = $order->payment_method === \App\Models\PosSale::PAYMENT_MANUAL_TRANSFER && $order->payment_receipt_path;
                        $isCodPending = $order->payment_method === \App\Models\PosSale::PAYMENT_COD && $order->status === \App\Models\PosSale::STATUS_PENDING;
                        $isCodDelivered = $order->payment_method === \App\Models\PosSale::PAYMENT_COD && $order->status === \App\Models\PosSale::STATUS_DELIVERED;
                        $isTransferPending = $order->payment_method === \App\Models\PosSale::PAYMENT_MANUAL_TRANSFER && $order->status === \App\Models\PosSale::STATUS_PENDING_VERIFICATION;
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
                        <td class="px-4 py-3 text-center relative" x-data="{ open: false }">
                            <button type="button"
                                    @click="open = !open"
                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    title="المزيد من الإجراءات"
                                    aria-label="المزيد من الإجراءات"
                                    :aria-expanded="open.toString()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute left-0 top-full z-30 mt-2 min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                 role="menu"
                                 dir="rtl">
                                <a href="{{ route('pos.orders.invoice.pdf', $order) }}" target="_blank" rel="noopener"
                                   class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50 no-underline"
                                   role="menuitem"
                                   @click="open = false">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/></svg>
                                    </span>
                                    <span class="flex-1 leading-snug"><x-info field="pos.online_order_invoice_pdf" /> PDF</span>
                                </a>
                                @if($showTransferReceipt)
                                    <button type="button"
                                            class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50"
                                            role="menuitem"
                                            @click="open=false; $dispatch('pos-receipt', { url: '{{ route('pos.orders.payment-receipt', $order) }}', invoice: @js($order->invoice_number) })">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">إيصال التحويل</span>
                                    </button>
                                @endif
                                @if($isCodPending)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('تأكيد التسليم؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">تم التسليم</span>
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('تحصيل مباشر؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">تحصيل مباشر</span>
                                        </button>
                                    </form>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('إلغاء؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">إلغاء</span>
                                        </button>
                                    </form>
                                @elseif($isCodDelivered)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('تأكيد التحصيل؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">تم التحصيل</span>
                                        </button>
                                    </form>
                                @elseif($isTransferPending)
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('تأكيد التحويل وترحيل القيد؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="collected">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-orange-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">تأكيد التحويل</span>
                                        </button>
                                    </form>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form method="post" action="{{ route('pos.orders.update-status', $order) }}" class="m-0" onsubmit="return confirm('إلغاء؟');">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50" role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">إلغاء</span>
                                        </button>
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
