@extends('layouts.app')

@section('title', 'فواتير الموردين - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">فواتير الموردين</span>
@endsection

@push('styles')
<style>
    .inv-widget { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1rem 1.25rem; }
    .inv-table-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .inv-badge { padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
    .inv-badge-paid { background: rgba(34, 197, 94, 0.15); color: #15803d; }
    .inv-badge-overdue { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }
    .inv-badge-unpaid { background: rgba(245, 158, 11, 0.2); color: #b45309; }
    .inv-badge-draft { background: rgba(107, 114, 128, 0.2); color: #4b5563; }
</style>
@endpush

@section('content')
@php
    $piIndexSupplierOptions = collect($suppliers ?? [])->map(fn ($s) => [
        'value' => $s->id,
        'label' => trim((string) ($s->code !== '' && $s->code !== null ? $s->code.' — ' : '').(string) ($s->name_ar ?? $s->name ?? '')),
    ])->values()->all();
    $invoicePaymentMethodOptions = $invoicePaymentMethodOptions ?? [];
@endphp
<div class="max-w-full" dir="rtl">

    @if(session('error'))
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <ul class="m-0 list-disc pr-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">فواتير الموردين</h1>
        </div>
    </div>

    {{-- بطاقات الإحصائيات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="inv-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.2); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">إجمالي المستحق</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalDue, 2) }}</p>
            </div>
        </div>
        <div class="inv-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(239, 68, 68, 0.2); color: #dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">متأخر</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($overdueAmount, 2) }}</p>
                <p class="text-xs text-gray-500">{{ $overdueCount }} فواتير</p>
            </div>
        </div>
        <div class="inv-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v4h4a.5.5 0 0 1 0 1h-4.5A.5.5 0 0 1 8 9V4.5z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">مستحق هذا الأسبوع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($dueThisWeek, 2) }}</p>
            </div>
        </div>
        <div class="inv-widget flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.2); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">إجمالي المدفوع</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalPaid, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- شريط الأدوات --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('purchases.invoices.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="min-w-0 w-52 max-w-[240px] shrink-0">
                    <label class="mb-0.5 block text-xs font-medium text-gray-600">المورد</label>
                    <x-searchable-select
                        name="supplier_id"
                        id="filter_pi_supplier_id"
                        :options="$piIndexSupplierOptions"
                        :value="request('supplier_id')"
                        :required="false"
                        empty-label="كل الموردين"
                        placeholder="ابحث عن مورد..."
                        class="[&_button]:h-9 [&_button]:text-sm"
                    />
                </div>
                <div>
                    <label class="mb-0.5 block text-xs font-medium text-gray-600">بحث</label>
                    <div class="flex items-center gap-1">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث..." class="w-44 min-w-0 px-3 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="shrink-0 rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50" title="تطبيق">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a.5.5 0 0 0 .708-.708l-3.85-3.85a.877.877 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </button>
                        <a href="{{ route('purchases.invoices.index') }}" class="shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">مسح</a>
                    </div>
                </div>
            </form>
            <span class="text-sm text-gray-600">الإجمالي <span class="font-semibold text-gray-900">{{ $invoices->total() }}</span></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#purchaseInvoicesImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('purchases.invoices.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-export-url="{{ route('purchases.invoices.index', array_merge(request()->query(), ['export' => 'csv'])) }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <a href="{{ route('purchases.invoices.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-white font-medium text-sm transition shadow-sm hover:opacity-90" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                فاتورة جديدة
            </a>
        </div>
    </div>

    {{-- جدول الفواتير --}}
    <div class="inv-table-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium text-gray-600">رقم الفاتورة</th>
                        <th class="py-3 px-4 font-medium text-gray-600">المورد</th>
                        <th class="py-3 px-4 font-medium text-gray-600">تاريخ الفاتورة</th>
                        <th class="py-3 px-4 font-medium text-gray-600">تاريخ الاستحقاق</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الإجمالي</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الرصيد المستحق</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الحالة</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap px-4 py-3 text-center text-xs font-semibold text-gray-600">
                            <span class="inline-flex items-center justify-center gap-1">
                                <x-info field="procurement.purchase_invoice_list_actions" />
                                الإجراءات
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $inv->reference ?: '#' . $inv->id }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $inv->supplier?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $inv->date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $inv->due_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-800">SAR {{ number_format((float) $inv->total, 2) }}</td>
                        <td class="py-3 px-4 text-gray-800">SAR {{ number_format($inv->balance, 2) }}</td>
                        <td class="py-3 px-4">
                            @if($inv->balance <= 0)
                                <span class="inv-badge inv-badge-paid">مدفوعة</span>
                            @elseif($inv->due_date && $inv->due_date->isPast())
                                <span class="inv-badge inv-badge-overdue">متأخرة</span>
                            @elseif((float) $inv->paid_amount > 0)
                                <span class="inv-badge inv-badge-unpaid">مدفوعة جزئياً</span>
                            @else
                                <span class="inv-badge inv-badge-draft">{{ $inv->status_label }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            @php
                                $canRecordPi = $inv->status !== \App\Models\PurchaseInvoice::STATUS_DRAFT && $inv->balance > 0.0001;
                            @endphp
                            <x-erp-actions-dropdown :menu-id="'pi-inv-'.$inv->id">
                                @if($canRecordPi)
                                    <button type="button" role="menuitem" class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50" data-bs-toggle="modal" data-bs-target="#purchaseInvoiceRecordPaymentModal" data-invoice-id="{{ $inv->id }}" data-balance="{{ number_format($inv->balance, 2, '.', '') }}">
                                        تسجيل دفعة
                                    </button>
                                @else
                                    <span class="erp-menu-item block px-3 py-2.5 text-right text-xs text-gray-400">لا يتوفر إجراء</span>
                                @endif
                            </x-erp-actions-dropdown>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center">
                            <p class="text-gray-500 font-medium">لا توجد فواتير</p>
                            <p class="text-sm text-gray-400 mt-1">يمكنك إنشاء فاتورة جديدة باستخدام الزر أعلاه.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $invoices->links() }}</div>
        @endif
    </div>

    {{-- مودال استيراد فواتير الموردين --}}
    <div class="modal fade" id="purchaseInvoicesImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد فواتير الموردين من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('purchases.invoices.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-600">
                            كل سطر يمثل بنداً في فاتورة شراء، ويتم التجميع حسب <strong>reference</strong>.
                            يجب أن يحتوي الملف على بيانات المورد (<code>supplier_code</code>) والمستودع (<code>warehouse_code</code>) والصنف (<code>item_code</code>) كما في النموذج الإرشادي.
                        </p>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 space-y-1">
                            <p class="font-semibold mb-1">الأعمدة الإلزامية لكل سطر (بند):</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>reference</code> – رقم/مرجع الفاتورة (يُستخدم لتجميع البنود في نفس الفاتورة).</li>
                                <li><code>supplier_code</code> – كود المورد كما هو في شاشة الموردين.</li>
                                <li><code>warehouse_code</code> – كود المستودع.</li>
                                <li><code>date</code> – تاريخ الفاتورة (YYYY-MM-DD).</li>
                                <li><code>due_date</code> – تاريخ الاستحقاق (إلزامي في أول سطر لكل مرجع فاتورة).</li>
                                <li><code>item_code</code> – كود الصنف كما هو في شاشة الأصناف.</li>
                                <li><code>quantity</code>, <code>unit_price</code> – الكمية وسعر الوحدة أكبر من صفر.</li>
                            </ul>
                            <p class="font-semibold mt-3 mb-1">الأعمدة الاختيارية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>currency</code>, <code>description</code>, <code>discount</code>, <code>vat_percent</code>, <code>notes</code></li>
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
                            <a href="{{ route('purchases.invoices.import-template') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
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

    <div class="modal fade" id="purchaseInvoiceRecordPaymentModal" tabindex="-1" aria-labelledby="purchaseInvoiceRecordPaymentModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl border border-gray-200 shadow-xl">
                <div class="modal-header border-b border-gray-100">
                    <h5 class="modal-title text-base font-semibold text-gray-900" id="purchaseInvoiceRecordPaymentModalLabel">تسجيل دفعة</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form id="purchaseInvoiceRecordPaymentForm" method="POST" action="">
                    @csrf
                    <div class="modal-body space-y-4 text-sm text-gray-800">
                        <p class="m-0 inline-flex flex-wrap items-center gap-1.5 text-xs leading-relaxed text-gray-600">
                            <span>سجّل دفعة المورد على الفاتورة؛ يُنشئ النظام القيد تلقائياً.</span>
                            <x-info field="procurement.purchase_invoice_record_payment_intro" />
                        </p>
                        <div class="space-y-1">
                            <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                                <span>المبلغ <span class="text-red-500" aria-hidden="true">*</span></span>
                                <x-info field="procurement.purchase_invoice_record_payment_amount" />
                            </label>
                            <input type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="space-y-1">
                            <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                                <span>التاريخ <span class="text-red-500" aria-hidden="true">*</span></span>
                                <x-info field="procurement.purchase_invoice_record_payment_date" />
                            </label>
                            <input type="date" name="date" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="space-y-1">
                            <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                                <span>وسيلة الدفع <span class="text-red-500" aria-hidden="true">*</span></span>
                                <x-info field="procurement.purchase_invoice_record_payment_method" />
                            </label>
                            <x-searchable-select
                                name="payment_method"
                                id="purchase_invoice_record_payment_method"
                                :options="$invoicePaymentMethodOptions"
                                value="cash"
                                :required="true"
                                :empty-option="false"
                                in-modal="true"
                                placeholder="اختر وسيلة الدفع…"
                                class="[&_button]:min-h-[2.75rem] [&_button]:text-sm"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-gray-700">المرجع (اختياري)</label>
                            <input type="text" name="reference" maxlength="50" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="modal-footer border-t border-gray-100 flex items-center justify-between gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ الدفعة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('purchaseInvoiceRecordPaymentModal');
    const form = document.getElementById('purchaseInvoiceRecordPaymentForm');
    const base = @json(rtrim(url('/purchases/invoices'), '/'));
    el?.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn || !form) return;
        const id = btn.getAttribute('data-invoice-id');
        if (!id) return;
        form.action = base + '/' + id + '/record-payment';
        const amt = form.querySelector('[name="amount"]');
        if (amt) amt.value = btn.getAttribute('data-balance') || '';
        const dt = form.querySelector('[name="date"]');
        if (dt) dt.value = new Date().toISOString().slice(0, 10);
        const ref = form.querySelector('[name="reference"]');
        if (ref) ref.value = '';
        window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'purchase_invoice_record_payment_method', value: 'cash' } }));
    });
});
</script>
@endpush
