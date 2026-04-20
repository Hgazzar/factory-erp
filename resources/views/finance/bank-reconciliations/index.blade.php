@extends('layouts.app')

@section('title', 'تسوية البنك - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تسوية البنك</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">تسوية البنك</h1>
            <p class="mt-1 text-sm text-gray-500">مطابقة كشوفات البنك مع سجلات الدفاتر</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('finance.bank-reconciliations.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <span class="text-base leading-none">+</span>
                تسوية بنك جديدة
            </a>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M8 12l4-4m0 0l4 4m-4-4v12" />
                </svg>
                استيراد CSV
            </button>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="space-y-4 p-4">
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500">إجمالي التسويات <x-info field="bank_reconciliation_total" /></p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['total_reconciliations'] ?? 0) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z" />
                            </svg>
                        </span>
                    </div>
                </article>
                <article class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-amber-700">معلق <x-info field="bank_reconciliation_pending" /></p>
                            <p class="mt-2 text-2xl font-bold text-amber-800">{{ (int) ($stats['pending_reconciliations'] ?? 0) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500">إجمالي العناصر <x-info field="bank_reconciliation_items" /></p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['total_items'] ?? 0) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                            </svg>
                        </span>
                    </div>
                </article>
                <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-emerald-700">تمت التسوية <x-info field="bank_reconciliation_completed" /></p>
                            <p class="mt-2 text-2xl font-bold text-emerald-800">{{ (int) ($stats['completed_reconciliations'] ?? 0) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                </article>
            </section>

            <form method="GET" action="{{ route('finance.bank-reconciliations.index') }}" class="flex flex-wrap items-end justify-between gap-3">
                <div class="w-full max-w-xs space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالة</span>
                        <x-info field="bank_reconciliation_filter" />
                    </label>
                    <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">جميع الحالات</option>
                        <option value="draft" @selected($status === 'draft')>معلق</option>
                        <option value="completed" @selected($status === 'completed')>تمت التسوية</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700">استعراض</button>
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[1040px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">رقم التسوية <x-info field="bank_reconciliation_number" /></th>
                            <th class="px-4 py-3 text-right">الحساب البنكي <x-info field="bank_reconciliation_account" /></th>
                            <th class="px-4 py-3 text-right">التاريخ <x-info field="bank_reconciliation_statement_date" /></th>
                            <th class="px-4 py-3 text-right">الرصيد <x-info field="bank_reconciliation_statement_balance" /></th>
                            <th class="px-4 py-3 text-right">الفرق <x-info field="bank_reconciliation_difference" /></th>
                            <th class="px-4 py-3 text-right">الحالة <x-info field="bank_reconciliation_status" /></th>
                            <th scope="col" class="w-[1%] whitespace-nowrap px-4 py-3 text-center text-xs font-semibold text-gray-500">
                                <span class="inline-flex items-center justify-center gap-1">
                                    <x-info field="bank_reconciliation_actions" />
                                    إجراءات
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reconciliations as $reconciliation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $reconciliation->reconciliation_number }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $reconciliation->account?->code }} - {{ $reconciliation->account?->name_ar ?: $reconciliation->account?->name_en }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ optional($reconciliation->statement_date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">SAR {{ number_format((float) $reconciliation->statement_balance, 2) }}</td>
                                <td class="px-4 py-3 font-semibold {{ (float) $reconciliation->difference === 0.0 ? 'text-emerald-700' : 'text-red-600' }}">
                                    SAR {{ number_format((float) $reconciliation->difference, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($reconciliation->status === 'completed')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">تمت التسوية</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">معلق</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    @php $recMenuId = 'bank-reconciliation-actions-'.$reconciliation->id; @endphp
                                    <x-erp-actions-dropdown :menu-id="$recMenuId">
                                        <p class="m-0 px-3 py-2.5 text-right text-xs leading-relaxed text-gray-500" role="presentation">لا توجد إجراءات على السجل حالياً.</p>
                                    </x-erp-actions-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($reconciliations->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $reconciliations->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
