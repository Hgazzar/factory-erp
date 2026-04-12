@extends('layouts.app')

@section('title', 'تحويلات المخزون - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تحويلات المخزون</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تحويلات المخزون</h1>
                <p class="mt-1 text-sm text-gray-500">نقل الأصناف بين المستودعات.</p>
            </div>
        </div>
        <a href="{{ route('inventory.transfers.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + تحويل جديد
        </a>
    </header>

    <form method="GET" action="{{ route('inventory.transfers.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" class="h-10 max-w-xs flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 sm:flex-none" placeholder="بحث...">
        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">بحث</button>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_number" /> رقم التحويل</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_date" /> التاريخ</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_from_wh" /> من مستودع</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_to_wh" /> إلى مستودع</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_items_count" /> عدد الأصناف</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_status" /> الحالة</th>
                        <th class="w-[7rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.transfer_actions" /> الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 font-semibold text-gray-900">{{ $t->transfer_number }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $t->transfer_date?->format('Y-m-d') }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $t->sourceWarehouse?->name_ar ?? '—' }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $t->destWarehouse?->name_ar ?? '—' }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ $t->items_count }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800">مكتمل</span>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('inventory.transfers.show', $t) }}" class="inline-flex rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">عرض</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-3 py-10 text-center text-gray-500">لا توجد تحويلات مخزون</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($transfers->hasPages())
    <div class="flex justify-center pt-2">
        {{ $transfers->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
