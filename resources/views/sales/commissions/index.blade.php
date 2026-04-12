@extends('layouts.app')

@section('title', 'العمولات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">العمولات</span>
@endsection

@section('content')
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">العمولات</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="btn-open-commission-modal" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M6 12.5a.5.5 0 0 1-.5-.5V9H2.5a.5.5 0 0 1 0-1H5.5V4.5a.5.5 0 0 1 1 0V8h3a.5.5 0 0 1 0 1H6.5v3a.5.5 0 0 1-.5.5z"/></svg>
                حساب العمولات
            </button>
            <a href="{{ route('sales.commissions.rules.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/><path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                إدارة القواعد
            </a>
        </div>
    </div>

    {{-- البطاقات الإحصائية --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المحسوب</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalCalculated, 2) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M11 1a1 1 0 0 0-1 1v1H6V2a1 1 0 0 0-2 0v1H3.5A1.5 1.5 0 0 0 2 4.5v8A1.5 1.5 0 0 0 3.5 14h9A1.5 1.5 0 0 0 14 12.5v-8A1.5 1.5 0 0 0 12.5 3H12V2a1 1 0 0 0-1-1z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">في انتظار الاعتماد</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPendingApproval, 2) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(234, 179, 8, 0.2); color: #ca8a04;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">في انتظار الدفع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPendingPayment, 2) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M11 1a1 1 0 0 0-1 1v1H6V2a1 1 0 0 0-2 0v1H3.5A1.5 1.5 0 0 0 2 4.5v8A1.5 1.5 0 0 0 3.5 14h9A1.5 1.5 0 0 0 14 12.5v-8A1.5 1.5 0 0 0 12.5 3H12V2a1 1 0 0 0-1-1z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المدفوع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPaid, 2) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.904 11.803 4.151 9.05a.5.5 0 1 1 .707-.707l1.89 1.89 4.39-4.39a.5.5 0 0 1 .707.708l-4.743 4.742a.5.5 0 0 1-.707 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" action="{{ route('sales.commissions.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                </span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث في العمولات..." class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[160px]">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- جدول العمولات --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium w-20">#</th>
                        <th class="py-3 px-4 font-medium">موظف المبيعات</th>
                        <th class="py-3 px-4 font-medium">المصدر</th>
                        <th class="py-3 px-4 font-medium">المبلغ الأساسي</th>
                        <th class="py-3 px-4 font-medium">المعدل / النسبة</th>
                        <th class="py-3 px-4 font-medium">مبلغ العمولة</th>
                        <th class="py-3 px-4 font-medium">تاريخ الحساب</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $c)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $c->id }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->user?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($c->salesInvoice)
                                    فاتورة SINV-{{ $c->salesInvoice->id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($c->base_amount, 2) }}</td>
                            <td class="py-3 px-4 text-gray-900">{{ number_format($c->rate_percent, 2) }}%</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($c->commission_amount, 2) }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $c->calculated_at?->format('Y-m-d') ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @switch($c->status)
                                    @case('pending_approval')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">في انتظار الاعتماد</span>
                                        @break
                                    @case('approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">معتمد</span>
                                        @break
                                    @case('pending_payment')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">في انتظار الدفع</span>
                                        @break
                                    @case('paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مدفوع</span>
                                        @break
                                    @case('rejected')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">مرفوض</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $c->status }}</span>
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-500">لا توجد عمولات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($commissions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
    {{-- نافذة حساب العمولات --}}
    <div id="commission-modal-backdrop" class="fixed inset-0 bg-black/40 z-40 hidden"></div>
    <div id="commission-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-md mx-auto" dir="rtl">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">حساب العمولات</h2>
                <button type="button" id="btn-close-commission-modal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/><path d="M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8z"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('sales.commissions.calculate') }}" class="px-5 pt-4 pb-5 space-y-4" dir="rtl">
                @csrf
                <p class="text-sm text-gray-600">
                    حساب العمولات للفواتير في الفترة المحددة. سيتم معالجة الفواتير التي ليس لها عمولات حالة فقط.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">بداية الفترة <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" id="commission-start-date" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نهاية الفترة <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" id="commission-end-date" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="btn-cancel-commission-modal" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حساب العمولات</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var openBtn = document.getElementById('btn-open-commission-modal');
    var closeBtn = document.getElementById('btn-close-commission-modal');
    var cancelBtn = document.getElementById('btn-cancel-commission-modal');
    var modal = document.getElementById('commission-modal');
    var backdrop = document.getElementById('commission-modal-backdrop');

    function setDefaultDates() {
        var startInput = document.getElementById('commission-start-date');
        var endInput = document.getElementById('commission-end-date');
        var today = new Date();
        var first = new Date(today.getFullYear(), today.getMonth(), 1);
        var pad = n => (n < 10 ? '0' + n : n);
        var toStr = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        if (startInput && !startInput.value) startInput.value = toStr(first);
        if (endInput && !endInput.value) endInput.value = toStr(today);
    }

    function openModal() {
        setDefaultDates();
        if (modal) modal.classList.remove('hidden');
        if (backdrop) backdrop.classList.remove('hidden');
    }
    function closeModal() {
        if (modal) modal.classList.add('hidden');
        if (backdrop) backdrop.classList.add('hidden');
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
})();
</script>
@endpush

@endsection
