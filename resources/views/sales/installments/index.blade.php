@extends('layouts.app')

@section('title', 'الأقساط - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">الأقساط</span>
@endsection

@section('content')
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">الأقساط</h1>
        <a href="{{ route('sales.installments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            إنشاء
        </a>
    </div>

    {{-- بطاقات المؤشرات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المستحق</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalDue, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">متأخر</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalOverdue, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $overdueCount }} أقساط</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">مستحق هذا الأسبوع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($dueThisWeek, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(234, 179, 8, 0.2); color: #ca8a04;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المدفوع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPaid, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            </div>
        </div>
    </div>

    {{-- الفلاتر والبحث --}}
    <form method="GET" action="{{ route('sales.installments.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[180px]">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                </span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="overdue_only" value="1" {{ request('overdue_only') ? 'checked' : '' }} class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="text-sm text-gray-700">المتأخرة فقط</span>
                <span class="w-4 h-4 rounded-full flex items-center justify-center" style="background: rgba(239, 68, 68, 0.2); color: #dc2626;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/></svg>
                </span>
            </label>
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px]">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">بحث</button>
        </div>
    </form>

    {{-- جدول الأقساط --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        @if($totalOverdue <= 0)
            <div class="px-4 py-3 border-b border-gray-100 text-sm text-gray-600">
                لا توجد أقساط <strong class="text-red-700">متأخرة</strong> حالياً، لذلك لن يظهر زر «إرسال تذكير».
                للاختبار: أنشئ قسط بتاريخ استحقاق قديم (مثل تاريخ أمس)، أو افتح الصفحة مع `?demo_installments=1` لإظهار زر التذكير والتنبيه كتجربة.
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم الفاتورة</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">#</th>
                        <th class="py-3 px-4 font-medium">تاريخ الاستحقاق</th>
                        <th class="py-3 px-4 font-medium">المبلغ</th>
                        <th class="py-3 px-4 font-medium">الرصيد المستحق</th>
                        <th class="py-3 px-4 font-medium">أيام التأخير</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium w-24">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($installments as $inst)
                        @php
                            $balance = (float)$inst->amount - (float)$inst->paid_amount;
                            $status = $inst->status;
                            $daysOverdue = $inst->days_overdue;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">SINV-{{ $inst->sales_invoice_id }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $inst->salesInvoice?->customer?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $inst->installment_number }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $inst->due_date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($inst->amount, 2) }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($balance, 2) }}</td>
                            <td class="py-3 px-4 text-gray-900">{{ $daysOverdue > 0 ? $daysOverdue : '—' }}</td>
                            <td class="py-3 px-4">
                                @if($status === 'مدفوع')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مدفوع</span>
                                @elseif($status === 'متأخر')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">متأخر</span>
                                @elseif($status === 'مستحق هذا الأسبوع')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">مستحق هذا الأسبوع</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">قادم</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if(($status === 'متأخر' && $balance > 0) || request()->boolean('demo_installments'))
                                    <button type="button" class="installment-reminder inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 transition" data-url="{{ route('sales.installments.send-reminder', $inst) }}" title="إرسال تذكير">
                                        إرسال تذكير
                                    </button>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-16 text-center text-gray-500">لا توجد أقساط</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($installments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $installments->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.installment-reminder').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var url = this.getAttribute('data-url');
        var btnEl = this;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                alert(d.message || 'تم إرسال التذكير بنجاح.');
            } else {
                alert(d.message || 'لم يتم الإرسال.');
            }
        })
        .catch(function() { alert('حدث خطأ.'); });
    });
});
</script>
@endpush
@endsection
