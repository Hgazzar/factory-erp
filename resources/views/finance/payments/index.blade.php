@extends('layouts.app')

@section('title', 'سندات الصرف - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">سندات الصرف</span>
@endsection

@section('content')
@php
    $paymentFilterSupplierOpts = collect($filterSuppliers ?? [])->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->code !== '' && $s->code !== null ? $s->code.' — ' : '').(string) ($s->name_ar ?? $s->name ?? '')),
    ])->values()->all();
    $paymentFilterExpenseAccountOpts = collect($filterExpenseAccounts ?? [])->map(fn ($a) => [
        'value' => $a->id,
        'label' => trim((string) ($a->code ?? '').' — '.(string) ($a->name_ar ?? $a->name_en ?? '')),
    ])->values()->all();
@endphp
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v2" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">سندات الصرف</h1>
                <p class="mt-1 text-sm text-gray-500">صرف مبالغ للموردين أو للمصروفات وربطها بقيود مالية.</p>
            </div>
        </div>
        <a href="{{ route('finance.payments.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            سند صرف جديد
        </a>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.payments.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-36 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">النوع</label>
                @php
                    $paymentIndexTypeOpts = [
                        ['value' => '', 'label' => 'الكل'],
                        ['value' => 'supplier', 'label' => 'مورد'],
                        ['value' => 'expense', 'label' => 'مصروف'],
                    ];
                @endphp
                <x-custom-select
                    name="type"
                    class="w-full"
                    :options="$paymentIndexTypeOpts"
                    :selected="request('type')"
                    :empty-option="false"
                    placeholder="النوع..."
                />
            </div>
            <div class="min-w-0 w-full max-w-[220px] shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">المورد</label>
                <x-searchable-select
                    name="supplier_id"
                    id="filter_payments_supplier_id"
                    :options="$paymentFilterSupplierOpts"
                    :value="request('supplier_id')"
                    :required="false"
                    empty-label="كل الموردين"
                    placeholder="ابحث عن مورد..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="min-w-0 w-full max-w-[240px] shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">حساب المصروف</label>
                <x-searchable-select
                    name="expense_account_id"
                    id="filter_payments_expense_account_id"
                    :options="$paymentFilterExpenseAccountOpts"
                    :value="request('expense_account_id')"
                    :required="false"
                    empty-label="كل الحسابات"
                    placeholder="ابحث بالرمز أو الاسم..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="w-40 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="min-w-0 flex-1 basis-[10rem]">
                <label class="mb-1 block text-xs font-medium text-gray-600">بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="مرجع، ملاحظات…" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex shrink-0 gap-2 pb-0.5">
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">تطبيق</button>
                <a href="{{ route('finance.payments.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">مسح</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] table-fixed border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="w-[7rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">التاريخ</th>
                        <th class="w-[6rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">النوع</th>
                        <th class="min-w-0 border-b border-gray-200 px-3 py-3 text-right font-semibold">المورد / حساب المصروف</th>
                        <th class="w-[8rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">المرجع</th>
                        <th class="min-w-[10rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">فاتورة مشتريات</th>
                        <th class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold tabular-nums">المبلغ</th>
                        <th class="w-[9rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $payment->date?->format('Y-m-d') }}</td>
                            <td class="px-3 py-3 text-gray-700">
                                @if($payment->type === 'supplier')
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">مورد</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">مصروف</span>
                                @endif
                            </td>
                            <td class="min-w-0 px-3 py-3 break-words text-gray-800">
                                @if($payment->type === 'supplier')
                                    {{ $payment->supplier?->name ?? '—' }}
                                @else
                                    {{ $payment->expenseAccount?->code ?? '' }} — {{ $payment->expenseAccount?->name_ar ?? '—' }}
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $payment->reference ?? '—' }}</td>
                            <td class="px-3 py-3 text-xs text-gray-600">
                                @if($payment->type === 'supplier' && $payment->purchaseInvoices->isNotEmpty())
                                    @foreach($payment->purchaseInvoices as $pi)
                                        <span class="mb-0.5 block last:mb-0">{{ $pi->reference ?: '#'.$pi->id }}@if($pi->pivot?->amount) <span class="whitespace-nowrap text-gray-500">({{ rtrim(rtrim(number_format((float) $pi->pivot->amount, 2, '.', ''), '0'), '.') }})</span>@endif</span>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-medium tabular-nums text-gray-900">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="px-3 py-3 text-gray-800">
                                <span class="block font-medium">{{ $payment->creator?->name ?? '—' }}</span>
                                <span class="block text-xs text-gray-500">{{ $payment->creator?->email }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                لا توجد سندات صرف حتى الآن.
                                <a href="{{ route('finance.payments.create') }}" class="font-medium text-blue-600 hover:text-blue-800">أضف أول سند صرف</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $payments->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
