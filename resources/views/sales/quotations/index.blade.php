@extends('layouts.app')

@php
    use App\Models\Quotation;
@endphp

@section('title', 'عروض الأسعار - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عروض الأسعار</span>
@endsection

@section('content')
@php
    $indexFilterCustomerOptions = collect($customers ?? [])->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->display_name ?? $c->name ?? ''),
    ])->values()->all();
@endphp
<div class="max-w-full">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">عروض الأسعار</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <button type="button"
                    data-import-modal="1"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition"
                    data-bs-toggle="modal"
                    data-bs-target="#quotationsImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/>
                </svg>
                استيراد
            </button>
            <a href="{{ route('sales.quotations.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <a href="{{ route('sales.quotations.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                عرض سعر جديد
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">إجمالي عروض الأسعار</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totalCount) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">مسودات</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($pendingCount) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(234, 179, 8, 0.2); color: #ca8a04;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">عروض معتمدة (قيمة)</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($acceptedAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">معدل التحويل</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($conversionRate, 1) }}%</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(147, 51, 234, 0.15); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.quotations.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث برقم العرض أو العميل..." class="w-full py-2 pl-10 pr-4 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px] text-right">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="min-w-0 w-full max-w-[220px] shrink-0">
                <x-searchable-select
                    name="customer_id"
                    id="filter_quotations_customer_id"
                    :options="$indexFilterCustomerOptions"
                    :value="request('customer_id')"
                    :required="false"
                    empty-label="جميع العملاء"
                    placeholder="ابحث عن عميل..."
                    class="[&_button]:min-h-[2.5rem] [&_button]:text-sm"
                />
            </div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm text-right">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm text-right">
            <span class="text-sm text-gray-500">الإجمالي {{ $quotations->total() }}</span>
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">تطبيق</button>
            <a href="{{ route('sales.quotations.index') }}" class="py-2 px-3 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">مسح</a>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_number" /> رقم العرض</th>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_customer" /> العميل</th>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_date" /> تاريخ العرض</th>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_valid_until" /> صالح حتى</th>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_total" /> الإجمالي</th>
                        <th class="py-3 px-4 font-medium"><x-info field="sales.quotation_status" /> الحالة</th>
                        <th class="py-3 px-4 font-medium text-center w-[1%] whitespace-nowrap"><x-info field="sales.quotation_actions" /> الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $q->quotation_number ?? ('QT-'.str_pad((string) $q->id, 3, '0', STR_PAD_LEFT)) }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $q->customer?->display_name ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $q->date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $q->valid_until?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format((float) $q->total_amount, 2) }}</td>
                            <td class="py-3 px-4">
                                @if($q->status === Quotation::STATUS_DRAFT)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-900">مسودة</span>
                                @elseif($q->status === Quotation::STATUS_APPROVED)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">معتمد</span>
                                @elseif($q->status === Quotation::STATUS_REJECTED)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">مرفوض</span>
                                @elseif($q->status === Quotation::STATUS_CONVERTED_TO_ORDER)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">محوّل</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $q->status }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center align-middle">
                                <div class="relative inline-flex items-center justify-center">
                                    <button type="button"
                                            class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                            data-actions-menu="quotation-actions-{{ $q->id }}"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            title="المزيد من الإجراءات"
                                            aria-label="المزيد من الإجراءات">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </button>

                                    <div id="quotation-actions-{{ $q->id }}"
                                         class="erp-actions-menu hidden min-w-[13.5rem] max-w-[min(18rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                         style="list-style: none;"
                                         role="menu"
                                         dir="rtl">
                                        <a href="{{ route('sales.quotations.print', $q) }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">طباعة</span>
                                        </a>

                                        <div class="flex w-full min-w-0 items-stretch" role="menuitem">
                                            <a href="{{ route('sales.quotations.pdf', $q) }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="erp-menu-item erp-quotation-pdf-link flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50"
                                               onclick="if (window.closeErpActionMenus) window.closeErpActionMenus();">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6.5L11 4.5z"/><path d="M4.603 12.089a1.5 1.5 0 0 1-.997-1.684l.29-1.704a.5.5 0 0 1 .5-.425h1.318a.5.5 0 0 1 .5.425l.29 1.704a1.5 1.5 0 0 1-.997 1.684l-.71.12a.5.5 0 0 1-.504-.12l-.71-.12zM7.5 6.5h3v1h-3v-1zm0 2h3v1h-3v-1zm0 2h2v1h-2v-1z"/></svg>
                                                </span>
                                                <span class="min-w-0 flex-1 font-medium leading-snug">معاينة PDF</span>
                                            </a>
                                            <div class="flex shrink-0 items-center ps-1 pe-2">
                                                <x-info field="quotation_action_download_pdf" />
                                            </div>
                                        </div>

                                        @can('update', $q)
                                            <a href="{{ route('sales.quotations.edit', $q) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                            </a>
                                        @endcan

                                        @can('approve', $q)
                                            <form method="POST" action="{{ route('sales.quotations.approve', $q) }}" class="m-0">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">اعتماد العرض</span>
                                                </button>
                                            </form>
                                        @endcan

                                        @if($q->status === Quotation::STATUS_APPROVED)
                                            <a href="{{ route('sales.quotations.convert-to-order', $q) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-indigo-900 transition hover:bg-indigo-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تحويل لأمر بيع</span>
                                            </a>
                                            <a href="{{ route('sales.invoices.create', ['quotation_id' => $q->id]) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-emerald-900 transition hover:bg-emerald-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تحويل لفاتورة</span>
                                            </a>
                                        @endif

                                        @can('delete', $q)
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST"
                                                  action="{{ route('sales.quotations.destroy', $q) }}"
                                                  class="m-0"
                                                  onsubmit="return confirm('حذف عرض السعر؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">حذف</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-gray-500">
                                لا توجد عروض أسعار
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $quotations->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="quotationsImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl">
            <div class="modal-header border-b border-gray-200">
                <h5 class="modal-title text-base font-semibold text-gray-900">استيراد عروض الأسعار</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('sales.quotations.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body space-y-3 text-sm text-gray-700">
                    <p>ارفع ملف CSV / Excel بنفس ترويسة القالب.</p>
                    <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm" required>
                    <a href="{{ route('sales.quotations.import-template') }}" class="inline-flex items-center text-xs font-medium text-indigo-700 hover:text-indigo-900">تحميل قالب الاستيراد</a>
                </div>
                <div class="modal-footer border-t border-gray-200">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">استيراد</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'تمت العملية',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false
        });
    @endif
});
</script>
@endpush
