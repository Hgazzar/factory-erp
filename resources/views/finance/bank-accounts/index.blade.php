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
                                        <span>الرصيد الحالي</span>
                                        <x-info field="opening_balance" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>الحالة</span>
                                        <x-info field="bank_status" />
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1">
                                        <span>الإجراءات</span>
                                        <x-info field="bank_account_actions" />
                                        <x-info field="bank_account_delete" />
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
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format((float) $account->current_balance, 2) }} {{ $account->currency }}</td>
                                    <td class="px-4 py-3">
                                        @if($account->status === 'active')
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">نشط</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">غير نشط</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('finance.bank-accounts.edit', $account) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="تعديل بيانات الحساب">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.586a2 2 0 112.828 2.828L11.828 14.828a4 4 0 01-1.414.943l-3.029 1.01 1.01-3.029a4 4 0 01.943-1.414l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.bank-accounts.destroy', $account) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟ لا يمكن التراجع عن هذه الخطوة');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف الحساب">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
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
