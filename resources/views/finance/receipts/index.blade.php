@extends('layouts.app')

@section('title', 'سندات القبض - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">سندات القبض</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">سندات القبض</h1>
                <p class="mt-1 text-sm text-gray-500">تحصيل المبالغ من العملاء وربطها بقيود مالية تلقائياً.</p>
            </div>
        </div>
        <a href="{{ route('finance.receipts.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            سند قبض جديد
        </a>
    </header>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] table-fixed border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="w-[7rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">التاريخ</th>
                        <th class="min-w-0 border-b border-gray-200 px-3 py-3 text-right font-semibold">العميل</th>
                        <th class="w-[8rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">المرجع</th>
                        <th class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold tabular-nums">المبلغ</th>
                        <th class="w-[9rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $receipt->date?->format('Y-m-d') }}</td>
                            <td class="min-w-0 px-3 py-3 font-medium text-gray-900 break-words">{{ $receipt->customer?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-gray-700">{{ $receipt->reference ?? '—' }}</td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format((float) $receipt->amount, 2) }}</td>
                            <td class="px-3 py-3 text-gray-800">
                                <span class="block font-medium">{{ $receipt->creator?->name ?? '—' }}</span>
                                <span class="block text-xs text-gray-500">{{ $receipt->creator?->email }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">
                                لا توجد سندات قبض حتى الآن.
                                <a href="{{ route('finance.receipts.create') }}" class="font-medium text-blue-600 hover:text-blue-800">أضف أول سند قبض</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $receipts->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
