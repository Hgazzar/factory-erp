@extends('layouts.app')

@section('title', 'الحسابات البنكية - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">الحسابات البنكية</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">الحسابات البنكية</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة الحسابات البنكية والأرصدة</p>
        </div>
        <a href="{{ route('finance.bank-accounts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <span class="text-base leading-none">+</span>
            إضافة حساب بنكي
        </a>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-4">
            <h2 class="inline-flex items-center gap-2 text-xl font-bold text-gray-900">
                <span>قائمة الحسابات البنكية</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M0 3a2 2 0 0 1 2-2h13.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H15v2a1 1 0 0 1 1 1v3.5a1.5 1.5 0 0 1-1.5 1.5h-12A2.5 2.5 0 0 1 0 12.5V3zm1 1.732V12.5A1.5 1.5 0 0 0 2.5 14h12a.5.5 0 0 0 .5-.5V5H2a1.99 1.99 0 0 1-1-.268zM1 3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2H2a1 1 0 0 0-1 1z"/>
                </svg>
            </h2>
        </div>

        <div class="space-y-4 p-4">
            <div class="flex justify-end">
                <a href="{{ route('finance.bank-accounts.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    تحديث
                </a>
            </div>

            <form method="GET" action="{{ route('finance.bank-accounts.index') }}" class="flex flex-row flex-nowrap items-end gap-3">
                <div class="min-w-0 flex-1 space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>البحث في الحسابات البنكية</span>
                        <x-info field="bank_account_search" />
                    </label>
                    <div class="relative">
                        <input type="search" name="search" value="{{ $search }}" placeholder="ابحث في الحسابات البنكية" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-10 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="w-40 shrink-0 space-y-1 sm:w-56">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالة</span>
                        <x-info field="bank_status" />
                    </label>
                    <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">الكل</option>
                        <option value="active" @selected($status === 'active')>نشط</option>
                        <option value="inactive" @selected($status === 'inactive')>غير نشط</option>
                    </select>
                </div>
            </form>

            @if($accounts->count() > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>اسم البنك</span>
                                        <x-info field="bank_name" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>رقم الحساب</span>
                                        <x-info field="account_number" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>العملة</span>
                                        <x-info field="currency" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>حساب الدليل</span>
                                        <x-info field="bank_ledger_account" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>الرصيد المحاسبي</span>
                                        <x-info field="bank_gl_balance" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>الحالة</span>
                                        <x-info field="bank_status" />
                                    </span>
                                </th>
                                <th scope="col" class="w-[1%] whitespace-nowrap px-4 py-3 text-center text-xs font-semibold text-gray-500">
                                    <span class="inline-flex items-center justify-center gap-1">
                                        <x-info field="bank_account_actions" />
                                        <x-info field="bank_account_delete" />
                                        الإجراءات
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($accounts as $account)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-800">{{ $account->bank_name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $account->account_number }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $account->currency }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if($account->ledgerAccount)
                                            <span class="font-mono text-xs text-gray-600">{{ $account->ledgerAccount->code }}</span>
                                            <span class="mr-1 text-gray-800">{{ $account->ledgerAccount->name_ar }}</span>
                                        @else
                                            <span class="text-xs text-amber-700">غير مربوط</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ erp_money((float) $account->current_balance) }} {{ $account->currency }}</td>
                                    <td class="px-4 py-3">
                                        @if($account->status === 'active')
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">نشط</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">غير نشط</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center align-middle">
                                        @php $bankAccMenuId = 'bank-account-actions-'.$account->id; @endphp
                                        <x-erp-actions-dropdown :menu-id="$bankAccMenuId">
                                            <a href="{{ route('finance.bank-accounts.edit', $account) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل الحساب البنكي</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.bank-accounts.destroy', $account) }}" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟ لا يمكن التراجع عن هذه الخطوة');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">حذف الحساب</span>
                                                </button>
                                            </form>
                                        </x-erp-actions-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-gray-100 bg-white py-24 text-center text-sm text-gray-500">
                    لا توجد حسابات بنكية
                </div>
            @endif
        </div>

        @if($accounts->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $accounts->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
