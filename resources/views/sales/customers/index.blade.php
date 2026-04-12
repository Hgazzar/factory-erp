@extends('layouts.app')

@section('title', 'العملاء - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">العملاء</span>
@endsection

@section('content')
<div class="max-w-full">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif
    {{-- رأس الصفحة والأزرار --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">العملاء</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
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

    {{-- شريط البحث --}}
    <form id="customers-search-form" method="GET" action="{{ route('sales.customers.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative flex items-center">
                    <span class="absolute left-3 flex items-center pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input id="customers-search-input" type="search" name="q" value="{{ request('q') }}" placeholder="بحث في العملاء..." class="w-full py-2.5 pl-10 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <span class="text-sm text-gray-500">{{ $customers->total() }} الإجمالي</span>
            <button type="submit" class="py-2.5 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">بحث</button>
        </div>
    </form>

    {{-- جدول العملاء --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto overscroll-x-contain px-2 sm:px-3">
            <table class="min-w-[64rem] w-full border-collapse text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap min-w-[5.5rem]"><span class="inline-flex items-center gap-1">الرمز <x-info field="sales.customer_code" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium min-w-[11rem] max-w-[16rem]"><span class="inline-flex items-center gap-1">العميل <x-info field="sales.customers_table_name" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap min-w-[9rem]"><span class="inline-flex items-center gap-1">التواصل <x-info field="sales.customers_table_contact" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium min-w-[8rem] max-w-[12rem] hidden md:table-cell"><span class="inline-flex items-center gap-1">العنوان <x-info field="sales.customers_table_address" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap hidden xl:table-cell"><span class="inline-flex items-center gap-1">رقم ضريبي (VAT) <x-info field="sales.customer_vat_number" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap hidden lg:table-cell"><span class="inline-flex items-center gap-1">الحد الائتماني <x-info field="sales.credit_limit" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap hidden lg:table-cell"><span class="inline-flex items-center gap-1">الرصيد <x-info field="sales.customers_table_balance" /></span></th>
                        <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1">الحالة <x-info field="sales.customers_table_status" /></span></th>
                        <th scope="col" class="sticky left-0 z-20 py-3 px-3 font-medium text-center whitespace-nowrap min-w-[4.5rem] bg-gray-50 shadow-[inset_1px_0_0_0_rgb(229_231_235)] border-gray-200"><span class="inline-flex items-center justify-center gap-1">الإجراءات <x-info field="sales.customers_table_actions" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $row)
                        <tr class="group border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-3 text-gray-900 font-medium whitespace-nowrap align-top">{{ $row->code }}</td>
                            <td class="py-3 px-3 text-gray-900 align-top" title="{{ $row->name }}">
                                <span class="block font-semibold leading-snug break-words">
                                    {{ $row->name }}
                                </span>
                                <span class="block text-xs text-gray-500 truncate mt-1 max-w-[14rem]" title="{{ $row->email }}">
                                    {{ $row->email ?: '—' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 align-top">
                                @php
                                    $phoneValue = trim((string) ($row->phone ?? ''));
                                    $mobileValue = trim((string) ($row->mobile ?? ''));
                                    $contactValue = $phoneValue !== '' && $mobileValue !== '' ? $phoneValue.' / '.$mobileValue : ($phoneValue !== '' ? $phoneValue : $mobileValue);
                                @endphp
                                <span class="block truncate max-w-[10rem]" title="{{ $contactValue }}">{{ $contactValue !== '' ? $contactValue : '—' }}</span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 align-top max-w-[12rem] hidden md:table-cell">
                                @php
                                    $addressValue = trim((string) data_get($row, 'address', ''));
                                @endphp
                                <span class="block truncate" title="{{ $addressValue }}">{{ $addressValue !== '' ? $addressValue : '—' }}</span>
                            </td>
                            <td class="py-3 px-3 text-gray-700 whitespace-nowrap hidden xl:table-cell align-top">{{ $row->vat_number ?? '—' }}</td>
                            <td class="py-3 px-3 text-gray-700 hidden lg:table-cell align-top whitespace-nowrap tabular-nums">
                                @if($row->credit_limit !== null && $row->credit_limit !== '')
                                    <span class="inline-flex items-baseline gap-1.5"><span class="text-xs text-gray-500 shrink-0">SAR</span><span>{{ number_format((float) $row->credit_limit, 2) }}</span></span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-900 hidden lg:table-cell align-top whitespace-nowrap tabular-nums">
                                <span class="inline-flex items-baseline gap-1.5"><span class="text-xs text-gray-500 shrink-0">SAR</span><span>{{ is_numeric($row->balance) ? number_format((float) $row->balance, 2) : $row->balance }}</span></span>
                            </td>
                            <td class="py-3 px-3 align-top">
                                @if(($row->status ?? '') === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">غير نشط</span>
                                @endif
                            </td>
                            <td class="sticky left-0 z-10 py-3 px-3 text-center align-middle bg-white shadow-[inset_1px_0_0_0_rgb(229_231_235)] group-hover:bg-gray-50/50">
                                @if($row->id)
                                    <div class="relative inline-flex items-center justify-center">
                                        <button type="button"
                                                class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                                data-actions-menu="customer-actions-{{ $row->id }}"
                                                aria-haspopup="menu"
                                                aria-expanded="false"
                                                title="المزيد من الإجراءات"
                                                aria-label="المزيد من الإجراءات">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                            </svg>
                                        </button>
                                        <div id="customer-actions-{{ $row->id }}"
                                             class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                             style="list-style: none;"
                                             role="menu"
                                             dir="rtl">
                                            <a href="{{ route('sales.customers.show', $row->id) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض</span>
                                            </a>
                                            <a href="{{ route('sales.customers.edit', $row->id) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <button type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteCustomerModal"
                                                    data-delete-action="{{ route('sales.customers.destroy', $row->id) }}"
                                                    data-delete-name="{{ $row->name }}"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                    role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">حذف</span>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 inline-block">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-16 text-center text-gray-500">
                                لا يوجد عملاء
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
<script>
    (function () {
        var form = document.getElementById('customers-search-form');
        var input = document.getElementById('customers-search-input');
        if (form && input) {
            input.addEventListener('input', function () {
                if (input.value.trim() === '') {
                    form.submit();
                }
            });
        }

        function positionMenu(trigger, menu) {
            var rect = trigger.getBoundingClientRect();
            var gap = 8;
            var pad = 12;
            menu.style.position = 'fixed';
            menu.style.zIndex = '9999';
            var w = menu.offsetWidth || 0;
            var left = rect.right + gap;
            if (left + w > window.innerWidth - pad) {
                left = window.innerWidth - pad - w;
            }
            if (left < pad) left = pad;
            menu.style.left = left + 'px';
            menu.style.right = 'auto';

            var h = menu.offsetHeight || 0;
            var spaceBelow = window.innerHeight - rect.bottom - gap - pad;
            var spaceAbove = rect.top - gap - pad;
            var openUp = h > 0 && h > spaceBelow && spaceAbove >= spaceBelow;
            if (openUp) {
                menu.style.top = 'auto';
                menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
            } else {
                menu.style.top = (rect.bottom + gap) + 'px';
                menu.style.bottom = 'auto';
            }
        }

        function restoreMenuToCell(menu) {
            if (!menu._erpPlaceholder || !menu._erpPlaceholder.parentNode) return;
            menu._erpPlaceholder.parentNode.replaceChild(menu, menu._erpPlaceholder);
            delete menu._erpPlaceholder;
        }

        function closeAllMenus() {
            document.querySelectorAll('.erp-actions-menu').forEach(function (m) {
                m.classList.add('hidden');
                restoreMenuToCell(m);
            });
            document.querySelectorAll('.erp-actions-trigger[aria-expanded="true"]').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
            });
        }

        function openMenu(trigger, menu) {
            if (!menu._erpPlaceholder) {
                menu._erpPlaceholder = document.createComment('erp-menu-placeholder');
                menu.parentNode.insertBefore(menu._erpPlaceholder, menu);
            }
            document.body.appendChild(menu);
            menu.classList.remove('hidden');
            positionMenu(trigger, menu);
            trigger.setAttribute('aria-expanded', 'true');
        }

        function repositionOpenMenus() {
            document.querySelectorAll('.erp-actions-menu:not(.hidden)').forEach(function (menu) {
                var id = menu.id;
                var trigger = document.querySelector('[data-actions-menu="' + id + '"]');
                if (trigger) positionMenu(trigger, menu);
            });
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.erp-actions-trigger');
            if (!trigger) {
                if (!e.target.closest('.erp-actions-menu')) closeAllMenus();
                return;
            }
            var menuId = trigger.getAttribute('data-actions-menu');
            var menu = menuId ? document.getElementById(menuId) : null;
            if (!menu) return;
            var isOpen = !menu.classList.contains('hidden');
            closeAllMenus();
            if (!isOpen) openMenu(trigger, menu);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAllMenus();
        });

        window.addEventListener('resize', repositionOpenMenus);
        window.addEventListener('scroll', repositionOpenMenus, true);

        document.addEventListener('DOMContentLoaded', function () {
            var deleteModal = document.getElementById('deleteCustomerModal');
            var deleteForm = document.getElementById('deleteCustomerForm');
            var deleteNameEl = document.getElementById('deleteCustomerModalName');
            if (!deleteModal || !deleteForm || !deleteNameEl || !window.bootstrap || !bootstrap.Modal) {
                return;
            }
            deleteModal.addEventListener('show.bs.modal', function (e) {
                closeAllMenus();
                var btn = e.relatedTarget;
                if (!btn) return;
                var action = btn.getAttribute('data-delete-action');
                var name = btn.getAttribute('data-delete-name') || '';
                if (action) deleteForm.setAttribute('action', action);
                deleteNameEl.textContent = name;
            });
        });
    })();
</script>
@endsection

@push('modals')
    {{-- مودالات خارج الجدول/overflow لتفادي تداخل طبقات الـ backdrop مع sticky --}}
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
