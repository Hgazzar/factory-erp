@extends('layouts.app')

@section('title', 'أوامر البيع - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">أوامر البيع</span>
@endsection

@section('content')
@php
    $indexFilterCustomerOptions = collect($customers ?? [])->map(fn ($c) => [
        'value' => $c->id,
        'label' => (string) ($c->name ?? $c->display_name ?? ''),
    ])->values()->all();
@endphp
<div class="max-w-full" dir="rtl">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif

    {{-- عنوان الصفحة والأزرار --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">أوامر البيع</h1>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.2); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('sales.orders.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#salesOrdersImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('sales.orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                أمر بيع جديد
            </a>
        </div>
    </div>

    {{-- كروت الإحصائيات --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">إجمالي الأوامر</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totalOrders) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">الأوامر المعلقة</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($pendingCount) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(234, 179, 8, 0.2); color: #ca8a04;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">قيمة الأوامر المؤكدة</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($confirmedValue, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-0.5">قيمة الأوامر الملغية</p>
                <p class="text-xl font-bold text-gray-900">SAR {{ number_format($cancelledValue, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
        </div>
    </div>

    {{-- شريط الفلاتر --}}
    <form method="GET" action="{{ route('sales.orders.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex gap-2 items-center w-full mb-3">
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="من تاريخ" class="flex-1 w-full h-10 px-3 border border-gray-300 rounded-lg text-sm text-right bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="إلى تاريخ" class="flex-1 w-full h-10 px-3 border border-gray-300 rounded-lg text-sm text-right bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

            <div class="min-w-0 w-full max-w-[220px] shrink-0 flex-1">
                <x-searchable-select
                    name="customer_id"
                    id="filter_sales_orders_customer_id"
                    :options="$indexFilterCustomerOptions"
                    :value="request('customer_id')"
                    :required="false"
                    empty-label="جميع العملاء"
                    placeholder="ابحث عن عميل..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>

            <select name="status" class="flex-1 w-full h-10 px-3 border border-gray-300 rounded-lg text-sm bg-white text-right whitespace-nowrap focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">الحالة</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>

            <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث..." class="flex-1 w-full h-10 px-3 border border-gray-300 rounded-lg text-sm text-right bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="flex justify-between items-center w-full">
            <div class="flex items-center gap-2">
                <button type="submit" class="w-32 h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">تطبيق</button>
                <a href="{{ route('sales.orders.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">مسح</a>
            </div>
            <span class="text-lg font-bold text-blue-600 whitespace-nowrap">الإجمالي {{ $orders->total() }}</span>
        </div>
    </form>

    {{-- الجدول --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم الأمر</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">تاريخ الأمر</th>
                        <th class="py-3 px-4 font-medium">التسليم المتوقع</th>
                        <th class="py-3 px-4 font-medium">الإجمالي</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium w-40">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $row)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $row->order_number }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->customer_name }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->order_date }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $row->expected_delivery }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($row->total, 2) }}</td>
                            <td class="py-3 px-4">
                                @if($row->status === 'معلق')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">معلق</span>
                                @elseif($row->status === 'مكتمل')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مكتمل</span>
                                @elseif($row->status === 'ملغي')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ملغي</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap items-center gap-2 justify-end">
                                    <a href="{{ route('sales.orders.show', $row->id) }}" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition" title="عرض">عرض</a>
                                    <a href="{{ route('sales.orders.print', $row->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition" title="طباعة PDF">PDF</a>
                                    @if($row->status === 'مكتمل')
                                        <a href="#" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-sm font-medium text-white transition" style="background: #16a34a;" title="تحويل لفاتورة">تحويل لفاتورة</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-gray-500">
                                لا توجد أوامر بيع
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- مودال استيراد أوامر البيع --}}
    <div class="modal fade" id="salesOrdersImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد أوامر البيع</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('sales.orders.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-3 text-sm text-gray-700">
                        <p>ارفع ملف CSV / Excel بنفس ترويسة القالب.</p>
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm" required>
                        <a href="{{ route('sales.orders.import-template') }}" class="inline-flex items-center text-xs font-medium text-indigo-700 hover:text-indigo-900">تحميل قالب الاستيراد</a>
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
