@extends('layouts.crm')

@section('title', 'حسابات الولاء — CRM')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">حسابات الولاء</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                حسابات الولاء
                <x-info field="crm.loyalty_accounts_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1 tabular-nums">إجمالي السجلات المطابقة للتصفية: {{ number_format((int) ($totalFiltered ?? 0)) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('crm.loyalty.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition no-underline">
                برامج الولاء
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('crm.loyalty.accounts.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="min-w-0 md:col-span-2">
                <label for="acc-program" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">البرنامج <x-info field="crm.loyalty_accounts_program_filter" /></span></label>
                <x-searchable-select
                    name="loyalty_program_id"
                    id="acc-program"
                    :options="$programOptions ?? []"
                    :value="request('loyalty_program_id', '')"
                    empty-label="الكل"
                    placeholder="الكل"
                    :searchable="true"
                />
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تطبيق</button>
                <a href="{{ route('crm.loyalty.accounts.index') }}" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">مسح</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-fixed w-full min-w-[56rem] border-collapse text-sm text-right">
                <colgroup>
                    <col class="w-[22%]">
                    <col class="w-[24%]">
                    <col class="w-[18%]">
                    <col class="w-[18%]">
                    <col class="w-[18%]">
                </colgroup>
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_accounts_customer_column" /> العميل</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_accounts_program_column" /> البرنامج</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_points_name" /> وحدة النقاط</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_accounts_balance_column" /> الرصيد</span></th>
                        <th class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.loyalty_accounts_monetary_column" /> القيمة التقديرية</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($accounts as $account)
                        @php
                            $cust = $account->customer;
                            $prog = $account->loyaltyProgram;
                            $money = ($prog !== null) ? ((float) $account->current_balance * (float) $prog->redemption_rate) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="py-3 px-3">
                                @if($cust)
                                    <a href="{{ route('sales.customers.show', $cust) }}" class="font-medium text-blue-700 hover:text-blue-900 no-underline">{{ $cust->display_name }}</a>
                                    <div class="text-xs text-gray-500 font-mono tabular-nums mt-0.5">{{ $cust->code }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-800">
                                {{ $prog?->name ?? '—' }}
                                @if($prog)
                                    <div class="text-xs text-gray-500 font-mono">{{ $prog->code }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-700">{{ $prog?->points_name ?? '—' }}</td>
                            <td class="py-3 px-3 tabular-nums text-gray-900 font-medium">{{ number_format((float) $account->current_balance, 2) }}</td>
                            <td class="py-3 px-3 tabular-nums text-gray-700">{{ number_format((float) $money, 4) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-flex flex-col items-center gap-2 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112-2h-2Zm0 0V5.5A2.5 2.5 0 109.5 8H12Zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                                    <span class="text-sm">لا توجد حسابات ولاء مسجلة بعد؛ سجّل العملاء من بطاقة العميل في المبيعات.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
