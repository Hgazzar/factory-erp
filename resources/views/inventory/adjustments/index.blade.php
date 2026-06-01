@extends('layouts.app')

@section('title', 'تسويات المخزون - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تسويات المخزون</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6.5L14 3.5v1z"/><path d="M4.5 5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.5-.5h-7z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تسويات المخزون</h1>
                <p class="mt-1 text-sm text-gray-500">تعديل الكميات أو القيم مع تتبع الأسباب ومراكز التكلفة.</p>
            </div>
        </div>
        <a href="{{ route('inventory.adjustments.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + تسوية جديدة
        </a>
    </header>

    <form method="GET" action="{{ route('inventory.adjustments.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" class="h-10 max-w-xs flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 sm:flex-none" placeholder="بحث...">
        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">بحث</button>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_number" /> رقم التسوية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_date" /> التاريخ</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_warehouse" /> المستودع</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_type" /> نوع التسوية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_total_qty" /> إجمالي الكمية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_total_value" /> إجمالي القيمة</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_reason" /> السبب</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.adjustment_cost_center" /> مركز التكلفة</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap border-b border-gray-200 px-3 py-3 text-center font-semibold"><span class="inline-flex items-center justify-center gap-1"><x-info field="inventory.adjustment_list_actions" /> إجراءات</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $a)
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 font-semibold text-gray-900">{{ $a->adjustment_number }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $a->adjustment_date?->format('Y-m-d') }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $a->warehouse?->name_ar ?? '—' }}</td>
                        <td class="px-3 py-3">
                            @if($a->type === 'add')
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800">إضافة كمية</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900">خصم كمية</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_qty($a->total_quantity ?? 0) }}</td>
                        <td class="px-3 py-3 tabular-nums font-medium text-gray-900">{{ number_format($a->total_value ?? 0, 2) }} SAR</td>
                        <td class="max-w-[12rem] px-3 py-3 text-gray-700">{{ $a->reason_label ? (config('inventory.adjustment_reasons')[$a->reason_label] ?? $a->reason_label) : '—' }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $a->costCenter ? $a->costCenter->name : '—' }}</td>
                        <td class="px-3 py-3 text-center align-middle">
                            @php $adjMenuId = 'adjustment-actions-'.$a->id; @endphp
                            <x-erp-actions-dropdown :menu-id="$adjMenuId">
                                <a href="{{ route('inventory.adjustments.show', $a) }}"
                                   class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                   role="menuitem">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.252-.264.365A13.133 13.133 0 0 1 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                    </span>
                                    <span class="flex-1 text-right font-medium leading-snug">عرض التسوية</span>
                                </a>
                                <div class="mx-2 my-2 border-t border-gray-100"></div>
                                <a href="{{ route('inventory.adjustments.show', $a) }}?print=1" target="_blank" rel="noopener noreferrer"
                                   class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                   role="menuitem">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm-1 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3z"/></svg>
                                    </span>
                                    <span class="flex-1 text-right font-medium leading-snug">طباعة</span>
                                </a>
                            </x-erp-actions-dropdown>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-3 py-10 text-center text-gray-500">لا توجد تسويات مخزون</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($adjustments->hasPages())
    <div class="flex justify-center pt-2">
        {{ $adjustments->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
