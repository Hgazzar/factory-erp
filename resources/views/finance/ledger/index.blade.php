@extends('layouts.app')

@section('title', 'كشف حساب - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">كشف حساب</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    @if(session('success'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('success') }}</div>
    @endif
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">كشف حساب</h1>
                <p class="mt-1 text-sm text-gray-500">اختر حساباً وفترة زمنية لعرض الحركات مع الرصيد التراكمي.</p>
            </div>
        </div>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.ledger.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="min-w-[220px] flex-1 sm:max-w-md">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="ledger_filter_account" /> الحساب</label>
                <select name="account_id" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">— اختر الحساب —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}"
                                @selected(optional($selectedAccount)->id === $account->id)>
                            {{ $account->code }} — {{ $account->filterHierarchyLabel($accountsById) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="ledger_filter_from_date" /> من تاريخ</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="ledger_filter_to_date" /> إلى تاريخ</label>
                <input type="date" name="to_date" value="{{ $toDate }}"
                       class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    عرض
                </button>
            </div>
        </form>
    </section>

    @if($selectedAccount)
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-l from-gray-50/80 to-white px-4 py-4 sm:px-6">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">الحساب المحدد</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $selectedAccount->code }} — {{ $selectedAccount->name_ar }}</p>
                @if($selectedAccount->name_en)
                    <p class="mt-0.5 text-sm text-gray-500">{{ $selectedAccount->name_en }}</p>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700">
                            <th scope="col" class="w-[7rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="ledger_col_date" /> التاريخ</th>
                            <th scope="col" class="w-[7.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="ledger_col_reference" /> المرجع</th>
                            <th scope="col" class="min-w-0 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="ledger_col_description" /> البيان</th>
                            <th scope="col" class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold tabular-nums"><x-info field="ledger_col_debit" /> مدين</th>
                            <th scope="col" class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold tabular-nums"><x-info field="ledger_col_credit" /> دائن</th>
                            <th scope="col" class="w-[7.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold tabular-nums"><x-info field="ledger_col_running_balance" /> الرصيد التراكمي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $balance = (float) $selectedAccount->opening_balance;
                        @endphp
                        <tr class="border-b border-amber-100 bg-amber-50/40">
                            <td class="px-3 py-3 text-gray-500">—</td>
                            <td class="px-3 py-3 text-gray-500">—</td>
                            <td class="px-3 py-3 font-medium text-amber-950">رصيد افتتاحي</td>
                            <td class="px-3 py-3 text-right text-gray-400">—</td>
                            <td class="px-3 py-3 text-right text-gray-400">—</td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($balance, 2) }}</td>
                        </tr>
                        @forelse($items as $item)
                            @php
                                $debit = (float) $item->debit;
                                $credit = (float) $item->credit;
                                $balance += ($debit - $credit);
                            @endphp
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                                <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $item->journalEntry?->date?->format('Y-m-d') }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ $item->journalEntry?->reference ?? '—' }}</td>
                                <td class="min-w-0 px-3 py-3 text-gray-800 break-words leading-snug">{{ $item->description ?? $item->journalEntry?->description ?? '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-900">{{ $debit > 0 ? number_format($debit, 2) : '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-900">{{ $credit > 0 ? number_format($credit, 2) : '—' }}</td>
                                <td class="px-3 py-3 text-right font-medium tabular-nums text-gray-900">{{ number_format($balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                                    لا توجد حركات على هذا الحساب في الفترة المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
