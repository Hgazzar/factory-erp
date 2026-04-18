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
                        <th scope="col" class="w-[1%] whitespace-nowrap border-b border-gray-200 px-3 py-3 text-center font-semibold"><span class="inline-flex items-center justify-center gap-1"><x-info field="inventory.transfer_actions" /> إجراءات</span></th>
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
                        <td class="px-3 py-3 text-center align-middle">
                            @php $transferMenuId = 'transfer-actions-'.$t->id; @endphp
                            <x-erp-actions-dropdown :menu-id="$transferMenuId">
                                <a href="{{ route('inventory.transfers.show', $t) }}"
                                   class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                   role="menuitem">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.252-.264.365A13.133 13.133 0 0 1 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                    </span>
                                    <span class="flex-1 text-right font-medium leading-snug">عرض التحويل</span>
                                </a>
                            </x-erp-actions-dropdown>
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
