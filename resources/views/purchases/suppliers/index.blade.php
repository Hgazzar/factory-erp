@extends('layouts.app')

@section('title', 'الموردين - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">الموردين</span>
@endsection

@push('styles')
<style>
    .sup-table-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .sup-badge-active { display: inline-flex; align-items: center; white-space: nowrap; background: rgba(34, 197, 94, 0.15); color: #15803d; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
    .sup-badge-inactive { display: inline-flex; align-items: center; white-space: nowrap; background: rgba(107, 114, 128, 0.2); color: #4b5563; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
    .sup-rating { color: #f59e0b; font-size: 0.9rem; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl" data-view="purchases.suppliers.index">

    {{-- عنوان الصفحة --}}
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">الموردين</h1>
        </div>
    </div>

    {{-- شريط الأدوات: بحث وإجمالي يمين، أزرار يسار --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('purchases.suppliers.index') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-600">بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث..." class="w-48 px-3 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="p-2 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a.5.5 0 0 0 .708-.708l-3.85-3.85a.877.877 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                </button>
            </form>
            <span class="text-sm text-gray-600">الإجمالي <span class="font-semibold text-gray-900">{{ $suppliers->total() }}</span></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#suppliersImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('purchases.suppliers.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <a href="{{ route('purchases.suppliers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-white font-medium text-sm transition shadow-sm hover:opacity-90" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                مورد جديد
            </a>
        </div>
    </div>

    {{-- جدول الموردين --}}
    <div class="sup-table-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium text-gray-600">الرمز <x-info field="procurement.supplier_code" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">الاسم <x-info field="procurement.supplier_name" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">البريد الإلكتروني <x-info field="procurement.supplier_email" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">الهاتف <x-info field="procurement.supplier_phone" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">نوع المورد <x-info field="procurement.supplier_type" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">تقييم المورد <x-info field="procurement.supplier_rating" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600">الحالة <x-info field="procurement.supplier_status" /></th>
                        <th class="py-3 px-4 font-medium text-gray-600 text-center w-[1%] whitespace-nowrap">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $supplier->code }}</td>
                        <td class="py-3 px-4"><a href="{{ route('purchases.suppliers.show', $supplier) }}" class="text-indigo-600 hover:underline">{{ $supplier->localized_display_name }}</a></td>
                        <td class="py-3 px-4 text-gray-600">{{ $supplier->email ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $supplier->phone ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $supplier->getLocalizedSupplierTypeLabel() }}</td>
                        <td class="py-3 px-4">
                            @if($supplier->rating !== null)
                                <span class="sup-rating" title="تقييم {{ $supplier->rating }}/5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= $supplier->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($supplier->is_active)
                                <span class="sup-badge-active">نشط</span>
                            @else
                                <span class="sup-badge-inactive">غير نشط</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center align-middle">
                            <div class="relative inline-flex items-center justify-center">
                                <button type="button"
                                        class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                        data-actions-menu="supplier-actions-{{ $supplier->id }}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        title="المزيد من الإجراءات"
                                        aria-label="المزيد من الإجراءات">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                    </svg>
                                </button>
                                <div id="supplier-actions-{{ $supplier->id }}"
                                     class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                     style="list-style: none;"
                                     role="menu"
                                     dir="rtl">
                                    <a href="{{ route('purchases.suppliers.edit', $supplier) }}"
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
                                            data-bs-target="#deleteSupplierModal-{{ $supplier->id }}"
                                            class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                            role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">حذف</span>
                                    </button>
                                </div>
                            </div>
                            <div class="modal fade" id="deleteSupplierModal-{{ $supplier->id }}" tabindex="-1" aria-hidden="true" dir="rtl">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-lg">
                                        <div class="modal-header border-b border-gray-200">
                                            <h5 class="modal-title text-base font-semibold text-gray-900">تأكيد حذف المورد</h5>
                                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-sm text-gray-700 leading-6">
                                                هل أنت متأكد من حذف المورد <span class="font-semibold">{{ $supplier->localized_display_name }}</span>؟ لا يمكن التراجع.
                                            </p>
                                        </div>
                                        <div class="modal-footer border-t border-gray-200 flex items-center justify-between gap-3">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <form action="{{ route('purchases.suppliers.destroy', $supplier) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center">
                            <p class="text-gray-500 font-medium">لا يوجد موردين</p>
                            <p class="text-sm text-gray-400 mt-1">يمكنك إضافة مورد جديد باستخدام الزر أعلاه.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $suppliers->links() }}</div>
        @endif
    </div>

    {{-- مودال استيراد الموردين --}}
    <div class="modal fade" id="suppliersImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد الموردين من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('purchases.suppliers.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-600">
                            قم برفع ملف <strong>CSV أو Excel (XLSX / XLS)</strong> مطابق للترويسة في النموذج الإرشادي.
                            سيتم استخدام <strong>code أو email</strong> لتحديث المورد إن وجد أو إنشائه إن لم يكن موجوداً.
                        </p>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 space-y-1">
                            <p class="font-semibold mb-1">الأعمدة الإلزامية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>name</code> – اسم المورد.</li>
                                <li><code>code</code> أو <code>email</code> – يجب تعبئة واحد منهما على الأقل في كل سطر.</li>
                            </ul>
                            <p class="font-semibold mt-3 mb-1">الأعمدة الاختيارية (يمكن تركها فارغة):</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>phone</code>, <code>supplier_type</code>, <code>rating</code></li>
                                <li><code>tax_number</code>, <code>commercial_register</code>, <code>currency</code>, <code>is_active</code></li>
                            </ul>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    ملف البيانات <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="file" accept=".csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                                       class="block w-full text-sm text-gray-700 border border-gray-300 rounded-xl px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-1">
                            <a href="{{ route('purchases.suppliers.import-template') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
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
</div>
@endsection
