@extends('layouts.app')

@section('title', 'المدفوعات - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المدفوعات</span>
@endsection

@section('content')
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">المدفوعات</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('sales.payments.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">تصدير</a>
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#salesPaymentsImportNotImplementedModal">
                استيراد
            </button>
            <a href="{{ route('sales.payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                دفعة جديدة
            </a>
        </div>
    </div>

    {{-- 4 بطاقات إحصائية --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المدفوعات</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totalPayments) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-gray-100 text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المبلغ</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">تم تخصيص الدفعة بنجاح</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($allocatedAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zM1 7v1h14V7H1zm0 3v1h14v-1H1z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">غير مخصص</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($unallocatedAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(249, 115, 22, 0.2); color: #ea580c;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
        </div>
    </div>

    {{-- فلاتر: طريقة الدفع، العميل، التاريخ، الحالة، بحث --}}
    <form method="GET" action="{{ route('sales.payments.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <select name="payment_method" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px]">
                <option value="">جميع الطرق</option>
                @foreach($paymentMethods as $value => $label)
                    <option value="{{ $value }}" {{ request('payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[160px]">
                <option value="">جميع العملاء</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm" placeholder="من">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm" placeholder="إلى">
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px]">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث في المدفوعات..." class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <span class="text-sm text-gray-500">الإجمالي {{ $payments->total() }}</span>
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">تطبيق</button>
        </div>
    </form>

    {{-- جدول المدفوعات --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم الدفعة</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">تاريخ الدفع</th>
                        <th class="py-3 px-4 font-medium">طريقة الدفع</th>
                        <th class="py-3 px-4 font-medium">المبلغ</th>
                        <th class="py-3 px-4 font-medium">تم تخصيص الدفعة بنجاح</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">PAY-{{ $payment->id }}</td>
                            <td class="py-3 px-4 text-gray-900">{{ $payment->customer?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $payment->date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $paymentMethods[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td class="py-3 px-4 text-gray-900 font-medium">SAR {{ number_format((float)$payment->amount, 2) }}</td>
                            <td class="py-3 px-4 text-gray-700">SAR {{ number_format((float)($payment->allocations_sum_amount_allocated ?? 0), 2) }}</td>
                            <td class="py-3 px-4">
                                @php $alloc = (float)($payment->allocations_sum_amount_allocated ?? 0); @endphp
                                @if($alloc >= (float)$payment->amount)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">مخصص</span>
                                @elseif($alloc > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">جزئي</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">غير مخصص</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500">لا توجد مدفوعات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- مودال استيراد المدفوعات (لم يُفعّل بعد) --}}
    <div class="modal fade" id="salesPaymentsImportNotImplementedModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد المدفوعات من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body space-y-3 text-sm text-gray-700">
                    <p>استيراد المدفوعات من ملف غير مفعّل بعد في هذه الشاشة.</p>
                    <p class="text-xs text-gray-500">
                        يدعم النظام حالياً الاستيراد للموردين، الأصناف، العملاء، فواتير الموردين، وسندات الاستلام.
                    </p>
                </div>
                <div class="modal-footer border-t border-gray-200">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">حسناً</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
