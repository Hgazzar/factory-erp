@extends('layouts.app')

@section('title', 'الفواتير - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">الفواتير</span>
@endsection

@section('content')
<div class="max-w-full">
    {{-- عنوان الصفحة والأزرار --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">الفواتير</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(46, 125, 50, 0.2); color: #2e7d32;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('sales.invoices.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#salesInvoicesImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('sales.invoices.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                فاتورة جديدة
            </a>
        </div>
    </div>

    {{-- كروت الإحصائيات --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي الفواتير</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totalInvoices) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">إجمالي المبلغ</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">المستحق</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($dueAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(245, 158, 11, 0.2); color: #d97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-0.5">المتأخر</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($overdueAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
            </div>
        </div>
    </div>

    {{-- شريط الفلاتر --}}
    <form method="GET" action="{{ route('sales.invoices.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث في الفواتير..." class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px]">
                <option value="">جميع الحالات</option>
                @foreach(array_slice($statuses, 1) as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[160px]">
                <option value="">جميع العملاء</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="py-2 px-3 border border-gray-300 rounded-lg text-sm">
            <span class="text-sm text-gray-500">الإجمالي {{ $invoices->total() }}</span>
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">تطبيق</button>
        </div>
    </form>

    {{-- الجدول --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم الفاتورة</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">تاريخ الإصدار</th>
                        <th class="py-3 px-4 font-medium">تاريخ الاستحقاق</th>
                        <th class="py-3 px-4 font-medium">الإجمالي</th>
                        <th class="py-3 px-4 font-medium">الرصيد المستحق</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium w-24">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $row)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $row->invoice_number }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->customer_name }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->issue_date }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->due_date }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($row->total, 2) }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($row->balance, 2) }}</td>
                            <td class="py-3 px-4">
                                @if($row->status === 'مدفوعة')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مدفوعة</span>
                                @elseif($row->status === 'متأخرة')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">متأخرة</span>
                                @elseif($row->status === 'مستحق')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">مستحق</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('sales.invoices.print', $row->id) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition" title="طباعة">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                    طباعة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-500">
                                لا توجد فواتير
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    {{-- مودال استيراد فواتير المبيعات --}}
    <div class="modal fade" id="salesInvoicesImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد فواتير المبيعات</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('sales.invoices.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-4 text-sm text-gray-700">
                        <p>
                            قم برفع ملف <strong>CSV أو Excel (XLSX / XLS)</strong> يحتوي على بنود فواتير المبيعات.
                            كل سطر يمثل <strong>بنداً</strong> داخل فاتورة، ويتم التجميع آلياً حسب العمود <code>reference</code>.
                        </p>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 space-y-1">
                            <p class="font-semibold mb-1">الأعمدة الإلزامية لكل سطر:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>reference</code> – رقم/مرجع الفاتورة (يُستخدم لتجميع البنود في نفس الفاتورة).</li>
                                <li><code>customer_code</code> – كود العميل كما في شاشة العملاء.</li>
                                <li><code>warehouse_code</code> – كود المستودع الذي سيتم خصم المخزون منه.</li>
                                <li><code>date</code> – تاريخ الفاتورة (YYYY-MM-DD).</li>
                                <li><code>item_code</code> – كود الصنف كما في شاشة الأصناف.</li>
                                <li><code>quantity</code>, <code>unit_price</code> – الكمية وسعر الوحدة أكبر من صفر.</li>
                            </ul>
                            <p class="font-semibold mt-3 mb-1">الأعمدة الاختيارية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>due_date</code> – تاريخ الاستحقاق (إلزامي في أول سطر لكل مرجع فاتورة؛ يجب أن يتطابق في كل أسطر نفس المرجع).</li>
                                <li><code>discount</code> – خصم على البند (قيمة).</li>
                                <li><code>tax_percent</code> – نسبة الضريبة على البند (افتراضياً 15%).</li>
                                <li><code>notes</code> – ملاحظات عامة على الفاتورة (يتم أخذ أول قيمة لنفس المرجع).</li>
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
                            <a href="{{ route('sales.invoices.import-template') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'تمت العملية',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false
        });
    @endif
});
</script>
@endpush
