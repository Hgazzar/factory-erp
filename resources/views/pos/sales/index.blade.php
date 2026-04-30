@extends('layouts.pos')

@section('title', 'إيصالات نقاط البيع - '.config('app.name'))

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 space-y-6" dir="rtl">
    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                إيصالات نقاط البيع
                <x-info field="pos.cashier_receipts_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">قائمة عمليات البيع المكتملة.</p>
        </div>
        <a href="{{ route('pos.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 transition shrink-0">
            لوحة نقاط البيع
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right min-w-[640px]">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الإيصال <x-info field="pos.col_receipt" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">المبلغ <x-info field="pos.col_amount_sar" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">طريقة الدفع <x-info field="pos.col_payment_method" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الجهاز <x-info field="pos.col_device" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الوقت <x-info field="pos.col_datetime" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-semibold">
                                <a href="{{ route('pos.sales.show', $sale) }}" class="text-blue-600 hover:text-blue-800">{{ $sale->receipt_number }}</a>
                            </td>
                            <td class="py-3 px-4 tabular-nums text-gray-900">{{ $erpCurrencyCode }} {{ number_format((float) $sale->total_price, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ $sale->payment_method }}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-700">{{ $sale->posDevice?->name ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-500 text-xs whitespace-nowrap">{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-14 px-4 text-center text-gray-500">لا توجد إيصالات مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/80">{{ $sales->links() }}</div>
        @endif
    </div>
</div>
@endsection
