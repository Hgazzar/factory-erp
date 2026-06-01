@extends('layouts.app')

@section('title', 'عملاء المبيعات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">عملاء المبيعات</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">عملاء المبيعات</h1>
                <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">بطاقات العملاء للمستندات المالية والائتمان <x-info field="sales.customers_sales_intro" /></p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end flex-wrap">
            <a href="{{ route('sales.customers.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#customersImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('sales.customers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                عميل جديد
            </a>
        </div>
    </div>

    <form id="customers-sales-filters" method="GET" action="{{ route('sales.customers.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 items-end gap-4">
            <div class="min-w-0">
                <label for="q" class="block text-xs font-medium text-gray-600 mb-1">بحث</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="اسم، رمز، بريد…" class="w-full py-2.5 px-3 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="min-w-0">
                <label for="op_status" class="block text-xs font-medium text-gray-600 mb-1"><span class="inline-flex items-center gap-1">الحالة التشغيلية <x-info field="sales.customers_op_status_filter" /></span></label>
                <select name="op_status" id="op_status" class="w-full py-2.5 px-3 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('op_status') === 'active')>نشط</option>
                    <option value="inactive" @selected(request('op_status') === 'inactive')>موقف</option>
                </select>
            </div>
            <div class="min-w-0">
                <label for="region" class="block text-xs font-medium text-gray-600 mb-1"><span class="inline-flex items-center gap-1">المنطقة <x-info field="sales.customers_region_filter" /></span></label>
                <select name="region" id="region" class="w-full py-2.5 px-3 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">كل المناطق</option>
                    @foreach($regionOptions ?? [] as $r)
                        <option value="{{ $r }}" @selected(request('region') === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="balance_min" class="block text-xs font-medium text-gray-600 mb-1"><span class="inline-flex items-center gap-1">الرصيد <x-info field="sales.customers_balance_filter" /></span></label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.01" min="0" name="balance_min" id="balance_min" value="{{ request('balance_min') }}" placeholder="من" class="w-full py-2.5 px-3 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <input type="number" step="0.01" min="0" name="balance_max" id="balance_max" value="{{ request('balance_max') }}" placeholder="إلى" class="w-full py-2.5 px-3 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <div class="flex gap-2 md:col-span-2 xl:col-span-4">
                <button type="submit" class="py-2.5 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">تطبيق</button>
                <a href="{{ route('sales.customers.index') }}" class="py-2.5 px-4 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50 transition">مسح</a>
            </div>
        </div>
        <p class="text-sm text-gray-500">{{ $customers->total() }} عميل</p>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto overscroll-x-contain px-2 sm:px-3">
            <table class="min-w-[56rem] w-full border-collapse text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="py-3 px-3 font-medium min-w-[12rem]"><span class="inline-flex items-center gap-1">العميل <x-info field="sales.customers_table_name" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1">الحالة التشغيلية <x-info field="sales.customers_table_status" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap tabular-nums"><span class="inline-flex items-center gap-1">الرصيد الحالي <x-info field="sales.customers_open_balance" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap tabular-nums hidden md:table-cell"><span class="inline-flex items-center gap-1">حد الائتمان <x-info field="sales.credit_limit" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap min-w-[9rem]"><span class="inline-flex items-center gap-1">الهاتف <x-info field="sales.customers_table_contact" /></span></th>
                        <th scope="col" class="sticky left-0 z-20 min-w-[7.5rem] whitespace-nowrap border-gray-200 bg-gray-50 px-3 py-3 text-center text-xs font-semibold text-gray-600 shadow-[inset_1px_0_0_0_rgb(229_231_235)]"><span class="inline-flex items-center justify-center gap-1"><x-info field="sales.customers_financial_actions" /> إجراءات</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $opBal = (float) ($customer->open_balance ?? 0);
                            $op = (($customer->status ?? ($customer->is_active ? 'active' : 'inactive')) === 'active') ? 'active' : 'inactive';
                            $phoneLine = trim(implode(' / ', array_filter([(string) $customer->phone, (string) $customer->mobile], fn ($p) => $p !== '')));
                        @endphp
                        <tr class="group border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-3 align-top">
                                <span class="block font-semibold text-gray-900 leading-snug">{{ $customer->display_name }}</span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $customer->code }}</span>
                            </td>
                            <td class="py-3 px-3 align-top">
                                @if($op === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">موقف</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 align-top tabular-nums text-gray-900">
                                <span class="inline-flex items-baseline gap-1"><span class="text-xs text-gray-500">SAR</span>{{ number_format($opBal, 2) }}</span>
                            </td>
                            <td class="py-3 px-3 align-top tabular-nums text-gray-700 hidden md:table-cell">
                                @if($customer->credit_limit !== null)
                                    <span class="inline-flex items-baseline gap-1"><span class="text-xs text-gray-500">SAR</span>{{ number_format((float) $customer->credit_limit, 2) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-700 align-top text-xs whitespace-nowrap">{{ $phoneLine !== '' ? $phoneLine : '—' }}</td>
                            <td class="sticky left-0 z-10 py-3 px-3 text-center align-middle bg-white shadow-[inset_1px_0_0_0_rgb(229_231_235)] group-hover:bg-gray-50/50">
                                @php $salesCustMenuId = 'sales-cust-actions-'.$customer->id; @endphp
                                <div class="flex items-center justify-center">
                                    <x-erp-actions-dropdown :menu-id="$salesCustMenuId">
                                        <a href="{{ route('sales.invoices.create', ['customer_id' => $customer->id]) }}"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6.5L11 4.5z"/><path d="M4.5 8.5h7v1h-7v-1zm0 2h5v1h-5v-1z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">فاتورة</span>
                                        </a>
                                        <a href="{{ route('reports.statement.index', ['customer_id' => $customer->id]) }}"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M1.5 1a.5.5 0 0 1 .5-.5h12a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-12a.5.5 0 0 1-.5-.5v-12ZM2 2v11h11V2H2Z"/><path d="M4 4h8v1H4V4Zm0 3h8v1H4V7Zm0 3h5v1H4v-1Z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">كشف حساب</span>
                                        </a>
                                        <a href="{{ route('sales.customers.show', $customer) }}#customer-attachments"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6.5L11 4.5z"/><path d="M4.603 12.089A1.003 1.003 0 0 1 4 11V9c0-.888.39-1.54 1-1.833V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v.167c.61.292 1 .944 1 1.833v2c0 .707-.293 1.256-.883 1.39z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">المستندات</span>
                                        </a>
                                        <div class="mx-2 my-1 border-t border-gray-100"></div>
                                        <a href="{{ route('sales.customers.show', $customer) }}"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 0 1.66 2.043C4.12 11.332 5.88 12.5 8 12.5c2.12 0 3.879-1.168 5.168-2.457A13.133 13.133 0 0 0 14.828 8a13.133 13.133 0 0 0-1.66-2.043C11.88 4.668 10.12 3.5 8 3.5c-2.12 0-3.879 1.168-5.168 2.457A13.133 13.133 0 0 0 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">عرض الملف</span>
                                        </a>
                                        <a href="{{ route('sales.customers.edit', $customer) }}"
                                           class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50 text-decoration-none"
                                           role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                            </span>
                                            <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                        </a>
                                        <div class="mx-2 my-1 border-t border-gray-100"></div>
                                        <button type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCustomerModal"
                                                data-delete-action="{{ route('sales.customers.destroy', $customer) }}"
                                                data-delete-name="{{ $customer->display_name }}"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف</span>
                                        </button>
                                    </x-erp-actions-dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-gray-500">لا يوجد عملاء مطابقون للفلتر.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('modals')
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true" dir="rtl"
         data-bs-backdrop="static" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900" id="deleteCustomerModalLabel">تأكيد حذف العميل</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p class="text-sm text-gray-700 leading-6">
                        هل أنت متأكد من حذف العميل <span id="deleteCustomerModalName" class="font-semibold"></span>؟
                    </p>
                </div>
                <div class="modal-footer border-t border-gray-200 flex items-center justify-between gap-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form id="deleteCustomerForm" method="POST" action="#">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customersImportModal" tabindex="-1" aria-hidden="true" dir="rtl"
         data-bs-backdrop="static" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد العملاء من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('sales.customers.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-600">
                            قم برفع ملف <strong>CSV أو Excel (XLSX / XLS)</strong> يحتوي على الأعمدة وفق النموذج الإرشادي.
                            سيتم الاعتماد على <strong>code أو email</strong> لتحديث العميل إن وجد أو إضافته إن لم يكن موجوداً.
                        </p>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 space-y-1">
                            <p class="font-semibold mb-1">الأعمدة الإلزامية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>name</code> – اسم العميل.</li>
                                <li><code>code</code> أو <code>email</code> – يجب تعبئة واحد منهما على الأقل في كل سطر.</li>
                            </ul>
                            <p class="font-semibold mt-3 mb-1">الأعمدة الاختيارية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>phone</code>, <code>tax_number</code>, <code>credit_limit</code></li>
                                <li><code>address</code>, <code>country</code>, <code>city</code>, <code>region</code>, <code>postal_code</code>, <code>is_active</code>, <code>status</code> (active / inactive)</li>
                            </ul>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ملف البيانات <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="file" accept=".csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                                   class="block w-full text-sm text-gray-700 border border-gray-300 rounded-xl px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('sales.customers.import-template') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                                تحميل النموذج الإرشادي
                            </a>
                            <span class="text-xs text-gray-500">الصيغ المدعومة: CSV, XLSX, XLS</span>
                        </div>
                    </div>
                    <div class="modal-footer border-t border-gray-200 flex items-center justify-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">بدء الاستيراد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('deleteCustomerModal');
    var deleteForm = document.getElementById('deleteCustomerForm');
    var deleteNameEl = document.getElementById('deleteCustomerModalName');
    if (!deleteModal || !deleteForm || !deleteNameEl || !window.bootstrap || !bootstrap.Modal) {
        return;
    }
    deleteModal.addEventListener('show.bs.modal', function (e) {
        if (window.closeErpActionMenus) window.closeErpActionMenus();
        var btn = e.relatedTarget;
        if (!btn) return;
        var action = btn.getAttribute('data-delete-action');
        var name = btn.getAttribute('data-delete-name') || '';
        if (action) deleteForm.setAttribute('action', action);
        deleteNameEl.textContent = name;
    });
});
</script>
@endpush
