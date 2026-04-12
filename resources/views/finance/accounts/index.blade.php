@extends('layouts.app')

@section('title', 'دليل الحسابات - UFUQ ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">لوحة المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">دليل الحسابات</span>
@endsection

@section('content')
<div id="coa-page-root"
     dir="rtl"
     class="mx-auto w-full max-w-full">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif

    @if (session('success'))
        <div class="erp-alert-success-inline mb-4" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="erp-alert-error mb-4" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="erp-alert-error mb-4" role="alert">
            <ul class="mb-0 list-none space-y-1 pr-4">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 1. الهيدر: العنوان يمين، الأزرار شمال — justify-between --}}
    <header class="flex w-full flex-nowrap items-center justify-between border-b border-gray-100 pb-2 mb-4">
        <div class="flex flex-nowrap items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">دليل الحسابات</h1>
                <p class="mt-0.5 text-sm text-gray-500">إدارة هيكل الحسابات والتسلسل الهرمي</p>
            </div>
        </div>
        <div class="flex flex-nowrap items-center gap-2">
            <a href="{{ route('finance.accounts.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">تصدير</a>
            <button type="button" data-import-modal="1" class="rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 erp-btn-import" data-bs-toggle="modal" data-bs-target="#financeAccountsImportModal">استيراد</button>
            <a href="{{ route('finance.accounts.create') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ حساب جديد</a>
        </div>
    </header>

    {{-- 2. الكروت الإحصائية — كما في الصورة: خلفية بيضاء، حدود رمادية خفيفة، أرقام Bold واضحة، ألوان مدين/دائن/فرق --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6" dir="rtl">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">إجمالي الحسابات</p>
                    <p class="text-base font-bold text-gray-900">{{ $totalAccountsCount }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8v-8m-8 8v8h8" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">إجمالي المدين</p>
                    <p class="text-base font-bold" style="color: #059669; font-weight: 700;">SAR {{ number_format((float) $totalDebit, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8v8m-8 0h8" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">إجمالي الدائن</p>
                    <p class="text-base font-bold" style="color: #dc2626; font-weight: 700;">SAR {{ number_format((float) $totalCredit, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">الفرق</p>
                    <p class="text-base font-bold" style="color: #059669; font-weight: 700;">SAR {{ number_format((float) abs($difference), 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. التصفية — جميع الحقول والقوائم والأزرار: bg-[#f9fafb] و border-gray-200 موحّد --}}
    <div class="w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">التصفية</h3>
        <form method="GET" action="{{ route('finance.accounts.index') }}" class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1" style="min-width: 200px;">
                    <span class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="البحث في الحسابات..." class="h-9 w-full rounded-md border border-gray-200 text-sm pr-10 pl-3 focus:ring-blue-500 focus:border-blue-500" style="background-color: #f9fafb;">
                </div>
                <div class="shrink-0 w-40">
                    <select name="type" class="h-9 w-full rounded-md border border-gray-200 text-sm focus:ring-blue-500 focus:border-blue-500" style="background-color: #f9fafb;">
                        <option value="">الكل</option>
                        <option value="asset" {{ request('type') === 'asset' ? 'selected' : '' }}>أصل</option>
                        <option value="liability" {{ request('type') === 'liability' ? 'selected' : '' }}>خصم</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>مصروف</option>
                        <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>إيراد</option>
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="h-9 rounded-md border border-gray-200 px-3 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-blue-500" style="background-color: #f9fafb;" data-tree="collapse">طي الكل</button>
                    <button type="button" class="h-9 rounded-md border border-gray-200 px-3 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-blue-500" style="background-color: #f9fafb;" data-tree="expand">توسيع الكل</button>
                    <button type="submit" class="h-9 rounded-md bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">تطبيق</button>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. الجدول الشجري RTL: الرمز (يمين) ← اسم الحساب، النوع، مدين، دائن، الرصيد، الحالة، الإجراءات (يسار) --}}
    <div class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm" dir="rtl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full table-auto border-collapse text-sm" role="grid">
                <thead>
                    <tr class="bg-gray-100">
                        <th scope="col" class="w-24 border-b border-gray-100 px-3 py-3 text-right font-bold text-gray-900">الرمز</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-bold text-gray-900">اسم الحساب</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-bold text-gray-900">النوع</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-left font-bold text-gray-900">مدين</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-left font-bold text-gray-900">دائن</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-left font-bold text-gray-900">الرصيد</th>
                        <th scope="col" class="w-20 border-b border-gray-100 px-3 py-3 text-right font-bold text-gray-900">الحالة</th>
                        <th scope="col" class="w-14 border-b border-gray-100 px-3 py-3 text-center font-bold text-gray-900">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rootAccounts as $account)
                        @include('finance.accounts._row', ['account' => $account, 'level' => 0])
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">لا توجد حسابات. تأكد من وجود حسابات جذر (parent_id فارغ) أو شغّل Seeder الخاص بشجرة الحسابات.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال استيراد دليل الحسابات --}}
    <div class="modal fade" id="financeAccountsImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد دليل الحسابات</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('finance.accounts.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-3 text-sm text-gray-700">
                        <p>ارفع ملف CSV / Excel بنفس ترويسة القالب.</p>
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm" required>
                        <a href="{{ route('finance.accounts.import-template') }}" class="inline-flex items-center text-xs font-medium text-indigo-700 hover:text-indigo-900">تحميل قالب الاستيراد</a>
                    </div>
                    <div class="modal-footer border-t border-gray-200">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">استيراد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال تعديل الحساب (Bootstrap — بدون Alpine) --}}
    <div class="modal fade" id="coaEditModal" tabindex="-1" aria-labelledby="coaEditModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl border border-gray-200">
                <div class="modal-header border-bottom border-gray-100">
                    <h5 class="modal-title text-base font-bold text-gray-900" id="coaEditModalLabel">تعديل الحساب</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form id="coa-edit-form" method="POST" action="#" data-base-url="{{ rtrim(url('/finance/accounts'), '/') }}">
                    @csrf
                    @method('PUT')
                    @if (request()->filled('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if (request()->filled('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif
                    <div class="modal-body space-y-4">
                        <div>
                            <label for="coa-edit-code" class="mb-1 block text-sm font-medium text-gray-700">رمز الحساب</label>
                            <input id="coa-edit-code" name="code" type="text" required maxlength="50"
                                   class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="coa-edit-name" class="mb-1 block text-sm font-medium text-gray-700">اسم الحساب</label>
                            <input id="coa-edit-name" name="name_ar" type="text" required maxlength="255"
                                   class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="modal-footer border-top border-gray-100 flex-wrap gap-2">
                        <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary rounded-lg bg-blue-600 border-0">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- تأكيد حذف الحساب (Bootstrap) --}}
    <div class="modal fade" id="coaDeleteModal" tabindex="-1" aria-labelledby="coaDeleteModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl border border-gray-200">
                <div class="modal-header border-bottom border-gray-100">
                    <h5 class="modal-title text-base font-bold text-gray-900" id="coaDeleteModalLabel">تأكيد حذف الحساب</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body text-sm text-gray-700">
                    <p>هل أنت متأكد من حذف الحساب <strong id="coa-delete-name-display" class="text-gray-900"></strong>؟</p>
                    <p class="text-xs text-amber-800 mb-0">لا يمكن التراجع عن الحذف إن نجحت العملية.</p>
                </div>
                <div class="modal-footer border-top border-gray-100 flex-wrap gap-2">
                    <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <form id="coa-delete-form" method="POST" action="#" class="d-inline" data-base-url="{{ rtrim(url('/finance/accounts'), '/') }}">
                        @csrf
                        @method('DELETE')
                        @if (request()->filled('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if (request()->filled('type'))
                            <input type="hidden" name="type" value="{{ request('type') }}">
                        @endif
                        <button type="submit" class="btn btn-danger rounded-lg">حذف نهائياً</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    (function () {
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

        window.coaCloseActionMenus = closeAllMenus;

        function bsModalShow(modalEl) {
            if (!modalEl) return;
            var Modal = window.bootstrap && window.bootstrap.Modal;
            if (Modal) {
                Modal.getOrCreateInstance(modalEl).show();
            }
        }

        function coaOpenEditModal(detail) {
            var form = document.getElementById('coa-edit-form');
            if (!form) return;
            var base = form.getAttribute('data-base-url') || '';
            form.setAttribute('action', base + '/' + detail.id);
            var codeEl = document.getElementById('coa-edit-code');
            var nameEl = document.getElementById('coa-edit-name');
            if (codeEl) codeEl.value = detail.code || '';
            if (nameEl) nameEl.value = detail.name_ar || '';
            bsModalShow(document.getElementById('coaEditModal'));
        }

        function coaOpenDeleteModal(detail) {
            var form = document.getElementById('coa-delete-form');
            if (!form) return;
            var base = form.getAttribute('data-base-url') || '';
            form.setAttribute('action', base + '/' + detail.id);
            var nameDisp = document.getElementById('coa-delete-name-display');
            if (nameDisp) {
                nameDisp.textContent = (detail.name_ar || detail.code || '').trim() || ('#' + detail.id);
            }
            bsModalShow(document.getElementById('coaDeleteModal'));
        }

        window.__coaQuickEdit = function (btn) {
            var id = parseInt(btn.getAttribute('data-coa-id'), 10);
            if (!id) return;
            coaOpenEditModal({
                id: id,
                code: btn.getAttribute('data-coa-code') || '',
                name_ar: btn.getAttribute('data-coa-name') || '',
            });
            if (window.coaCloseActionMenus) window.coaCloseActionMenus();
        };

        window.__coaQuickDelete = function (btn) {
            var id = parseInt(btn.getAttribute('data-coa-id'), 10);
            if (!id) return;
            coaOpenDeleteModal({
                id: id,
                code: btn.getAttribute('data-coa-code') || '',
                name_ar: btn.getAttribute('data-coa-name') || '',
            });
            if (window.coaCloseActionMenus) window.coaCloseActionMenus();
        };

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

        document.addEventListener('show.bs.modal', closeAllMenus);

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.account-copy-btn');
            if (!btn) return;
            var text = btn.getAttribute('data-copy-text') || '';
            if (!text) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(function () {});
            } else {
                var input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                try { document.execCommand('copy'); } catch (err) {}
                document.body.removeChild(input);
            }
            closeAllMenus();
        });
    })();
</script>
@endpush
