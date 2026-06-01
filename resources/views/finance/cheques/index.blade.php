@extends('layouts.app')

@section('title', 'إدارة الشيكات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">الشيكات</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-right">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900">إدارة الشيكات</h1>
                <p class="mt-1 text-sm text-gray-500">إدارة الشيكات المستلمة والصادرة</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('finance.cheques.create-outgoing') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    إصدار شيك
                </a>
                <a href="{{ route('finance.cheques.create-incoming') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    استلام شيك
                </a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-4 gap-4">
        <article class="rounded-lg border border-gray-200 bg-white p-4 text-right shadow-sm">
            <p class="text-xs font-medium text-gray-500">المستلمة المعلقة</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">SAR {{ number_format($stats['incoming_total'] ?? 0, 2) }}</p>
        </article>
        <article class="rounded-lg border border-gray-200 bg-white p-4 text-right shadow-sm">
            <p class="text-xs font-medium text-gray-500">المودعة</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">SAR {{ number_format($stats['due_today'] ?? 0, 2) }}</p>
        </article>
        <article class="rounded-lg border border-gray-200 bg-white p-4 text-right shadow-sm">
            <p class="text-xs font-medium text-gray-500">الصادرة المعلقة</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">SAR {{ number_format($stats['outgoing_total'] ?? 0, 2) }}</p>
        </article>
        <article class="rounded-lg border border-red-100 bg-red-50 p-4 text-right shadow-sm">
            <p class="text-xs font-medium text-red-600">الشيكات المرتجعة</p>
            <p class="mt-1 text-3xl font-bold text-red-700">SAR {{ number_format($stats['bounced_total'] ?? 0, 2) }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('finance.cheques.index', array_filter(['search' => $search, 'status' => $status])) }}"
                   class="rounded-lg border px-4 py-2 text-sm font-medium {{ $type === '' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                    الشيكات المستلمة
                </a>
                <a href="{{ route('finance.cheques.index', array_filter(['type' => 'outgoing', 'search' => $search, 'status' => $status])) }}"
                   class="rounded-lg border px-4 py-2 text-sm font-medium {{ $type === 'outgoing' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                    الشيكات الصادرة
                </a>
            </div>

            <form method="GET" action="{{ route('finance.cheques.index') }}" class="flex flex-1 flex-wrap items-center gap-2" x-data="{}" @custom-select-change.window="if ($event.detail?.name === 'status') $el.submit()">
                <input type="hidden" name="type" value="{{ $type }}">
                <label class="relative min-w-[280px] flex-1">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="search" name="search" value="{{ $search }}" placeholder="البحث في الشيكات"
                           class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 pr-9 pl-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                @php
                    $chequeStatusFilterOpts = [
                        ['value' => '', 'label' => 'الكل'],
                        ['value' => 'pending', 'label' => 'قيد المتابعة'],
                        ['value' => 'cleared', 'label' => 'تم التحصيل/الصرف'],
                        ['value' => 'bounced', 'label' => 'مرتجع'],
                        ['value' => 'cancelled', 'label' => 'ملغي'],
                    ];
                @endphp
                <x-custom-select
                    name="status"
                    class="h-11 w-52 shrink-0"
                    :options="$chequeStatusFilterOpts"
                    :selected="$status"
                    :empty-option="false"
                    placeholder="الحالة..."
                />
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-right">رقم الشيك <x-info field="cheque_number" /></th>
                        <th class="px-4 py-3 text-right">تاريخ الاستحقاق <x-info field="due_date" /></th>
                        <th class="px-4 py-3 text-right">اسم الساحب/المستفيد <x-info field="beneficiary_name" /></th>
                        <th class="px-4 py-3 text-right">البنك <x-info field="cheque_bank" /></th>
                        <th class="px-4 py-3 text-right">المبلغ <x-info field="cheque_amount" /></th>
                        <th class="px-4 py-3 text-right">الحالة <x-info field="cheque_status" /></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($cheques as $cheque)
                        @php
                            $isOverdue = $cheque->status === 'pending' && optional($cheque->due_date)->isPast();
                            $drawerName = $cheque->type === 'incoming'
                                ? ($cheque->party_name ?: '—')
                                : ($cheque->beneficiary_name ?: '—');
                        @endphp
                        <tr class="{{ $isOverdue ? 'bg-red-50/60 hover:bg-red-50' : 'hover:bg-gray-50' }}">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $cheque->cheque_number }}</td>
                            <td class="px-4 py-3 {{ $isOverdue ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                {{ optional($cheque->due_date)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $drawerName }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $cheque->bank_name }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format((float) $cheque->amount, 2) }} SAR</td>
                            <td class="px-4 py-3">
                                @if($cheque->status === 'cleared')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">تم التحصيل/الصرف</span>
                                @elseif($cheque->status === 'bounced')
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">مرتجع</span>
                                @elseif($cheque->status === 'cancelled')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">ملغي</span>
                                @else
                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">قيد المتابعة</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $cheques->links() }}
        </div>
    </section>
</div>
@endsection
