@extends('layouts.app')

@section('title', 'أوامر الشراء - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أوامر الشراء</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @php
        $poIndexSupplierOptions = collect($suppliers ?? [])->map(fn ($s) => [
            'value' => $s->id,
            'label' => trim((string) ($s->code !== '' && $s->code !== null ? $s->code.' — ' : '').(string) ($s->name_ar ?? $s->name ?? '')),
        ])->values()->all();
    @endphp
    <form method="GET" action="{{ route('purchases.orders.index') }}" class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 w-full max-w-[240px] shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">المورد</label>
                <x-searchable-select
                    name="supplier_id"
                    id="filter_po_supplier_id"
                    :options="$poIndexSupplierOptions"
                    :value="request('supplier_id')"
                    :required="false"
                    empty-label="كل الموردين"
                    placeholder="ابحث عن مورد..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="w-36 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">الحالة</label>
                <select name="status" class="h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">الكل</option>
                    <option value="معلق" {{ request('status') === 'معلق' ? 'selected' : '' }}>معلق</option>
                    <option value="مستلم" {{ request('status') === 'مستلم' ? 'selected' : '' }}>مستلم</option>
                </select>
            </div>
            <div class="w-40 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="w-40 shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="min-w-0 flex-1 basis-[12rem]">
                <label class="mb-1 block text-xs font-medium text-gray-600">بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="رقم الأمر، مرجع، مورد…" class="h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex shrink-0 items-center gap-2 pb-0.5">
                <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">تطبيق</button>
                <a href="{{ route('purchases.orders.index') }}" class="inline-flex h-10 items-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">مسح</a>
            </div>
        </div>
    </form>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">أوامر الشراء</h1>
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#purchaseOrdersImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('purchases.orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                أمر شراء جديد
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium text-gray-600"><x-info field="procurement.purchase_order_code" /> رقم الأمر</th>
                        <th class="py-3 px-4 font-medium text-gray-600">المورد</th>
                        <th class="py-3 px-4 font-medium text-gray-600">التاريخ</th>
                        <th class="py-3 px-4 font-medium text-gray-600">تاريخ التسليم المتوقع</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الحالة</th>
                        <th class="py-3 px-4 font-medium text-gray-600 text-left">الضريبة</th>
                        <th class="py-3 px-4 font-medium text-gray-600 text-left">الإجمالي</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap px-4 py-3 text-center text-xs font-semibold text-gray-600">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="py-3 px-4 font-medium text-gray-900 whitespace-nowrap">{{ $order->display_order_number }}</td>
                        <td class="py-3 px-4">{{ $order->supplier?->getLocalizedDisplayName() ?? '—' }}</td>
                        <td class="py-3 px-4 whitespace-nowrap">{{ $order->order_date?->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">{{ $order->expected_delivery_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-3 px-4">{{ $order->status }}</td>
                        <td class="py-3 px-4 text-left">SAR {{ number_format((float) $order->total_tax, 2) }}</td>
                        <td class="py-3 px-4 text-left font-medium">SAR {{ number_format((float) $order->total, 2) }}</td>
                        <td class="py-3 px-4 text-center align-middle">
                            <div class="relative inline-flex items-center justify-center">
                                <button type="button"
                                        class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                        data-actions-menu="po-actions-{{ $order->id }}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        title="المزيد من الإجراءات"
                                        aria-label="المزيد من الإجراءات">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                    </svg>
                                </button>
                                <div id="po-actions-{{ $order->id }}"
                                     class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                     style="list-style: none;"
                                     role="menu"
                                     dir="rtl">
                                    <a href="{{ route('purchases.orders.show', $order) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">عرض</span>
                                    </a>
                                    @if($order->status === 'معلق')
                                        <a href="{{ route('purchases.orders.edit', $order) }}"
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
                                                data-bs-target="#deletePurchaseOrderModal-{{ $order->id }}"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @if($order->status === 'معلق')
                                <div class="modal fade" id="deletePurchaseOrderModal-{{ $order->id }}" tabindex="-1" aria-hidden="true" dir="rtl">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-lg">
                                            <div class="modal-header border-b border-gray-200">
                                                <h5 class="modal-title text-base font-semibold text-gray-900">تأكيد حذف أمر الشراء</h5>
                                                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-sm text-gray-700 leading-6">
                                                    هل أنت متأكد من حذف أمر الشراء <span class="font-semibold">{{ $order->display_order_number }}</span>؟
                                                </p>
                                            </div>
                                            <div class="modal-footer border-t border-gray-200 flex items-center justify-between gap-3">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <form action="{{ route('purchases.orders.destroy', $order) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">لا توجد أوامر شراء</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $orders->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="purchaseOrdersImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد أوامر الشراء</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('purchases.orders.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-3 text-sm text-gray-700">
                        <p>ارفع ملف CSV / Excel بنفس ترويسة القالب.</p>
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm" required>
                        <a href="{{ route('purchases.orders.import-template') }}" class="inline-flex items-center text-xs font-medium text-indigo-700 hover:text-indigo-900">تحميل قالب الاستيراد</a>
                    </div>
                    <div class="modal-footer border-t border-gray-200">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">استيراد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
