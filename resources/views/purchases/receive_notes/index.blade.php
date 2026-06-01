@extends('layouts.app')

@section('title', 'سندات الاستلام - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">سندات الاستلام</span>
@endsection

@push('styles')
<style>
    .rn-table-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .rn-badge { padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
    .rn-badge-completed { background: rgba(34, 197, 94, 0.15); color: #15803d; }
    .rn-badge-pending { background: rgba(245, 158, 11, 0.2); color: #b45309; }
    .rn-badge-draft { background: rgba(107, 114, 128, 0.2); color: #4b5563; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">سندات الاستلام</h1>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('purchases.receive-notes.index') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-600">بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث..." class="w-48 px-3 py-2 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="p-2 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a.5.5 0 0 0 .708-.708l-3.85-3.85a.877.877 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                </button>
            </form>
            <span class="text-sm text-gray-600">الإجمالي <span class="font-semibold text-gray-900">{{ $receiveNotes->total() }}</span></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#receiveNotesImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                استيراد
            </button>
            <a href="{{ route('purchases.receive-notes.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                تصدير
            </a>
            <a href="{{ route('purchases.receive-notes.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-white font-medium text-sm transition shadow-sm hover:opacity-90" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                سند استلام جديد
            </a>
        </div>
    </div>

    <div class="rn-table-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium text-gray-600">رقم السند</th>
                        <th class="py-3 px-4 font-medium text-gray-600">المورد</th>
                        <th class="py-3 px-4 font-medium text-gray-600">أمر الشراء</th>
                        <th class="py-3 px-4 font-medium text-gray-600">المستودع</th>
                        <th class="py-3 px-4 font-medium text-gray-600">تاريخ الاستلام</th>
                        <th class="py-3 px-4 font-medium text-gray-600">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receiveNotes as $note)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $note->code }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $note->supplier->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $note->purchaseOrder ? ($note->purchaseOrder->reference ?: '#' . $note->purchase_order_id) : '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $note->warehouse->name_ar ?? $note->warehouse->code ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $note->receive_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @if($note->status === 'completed')
                                <span class="rn-badge rn-badge-completed">{{ $note->status_label }}</span>
                            @elseif($note->status === 'pending')
                                <span class="rn-badge rn-badge-pending">{{ $note->status_label }}</span>
                            @else
                                <span class="rn-badge rn-badge-draft">{{ $note->status_label }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <p class="text-gray-500 font-medium">لا توجد سندات استلام</p>
                            <p class="text-sm text-gray-400 mt-1">يمكنك إنشاء سند استلام جديد باستخدام الزر أعلاه.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receiveNotes->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $receiveNotes->links() }}</div>
        @endif
    </div>

    {{-- مودال استيراد سندات الاستلام --}}
    <div class="modal fade" id="receiveNotesImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد سندات الاستلام من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('purchases.receive-notes.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-4">
                        <p class="text-sm text-gray-600">
                            استخدم ملف <strong>CSV أو Excel (XLSX / XLS)</strong> لإنشاء أو تحديث سندات الاستلام.
                            يتم التحديث حسب العمود <strong>code</strong> إذا كان موجوداً، وإلا يتم إنشاء سند جديد.
                        </p>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 space-y-1">
                            <p class="font-semibold mb-1">الأعمدة الإلزامية لكل سطر:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>supplier_code</code> – كود المورد كما في شاشة الموردين.</li>
                                <li><code>warehouse_code</code> – كود المستودع.</li>
                                <li><code>receive_date</code> – تاريخ الاستلام (YYYY-MM-DD).</li>
                            </ul>
                            <p class="font-semibold mt-3 mb-1">الأعمدة الاختيارية:</p>
                            <ul class="list-disc pr-5 space-y-0.5">
                                <li><code>code</code> – رقم السند (إن تركته فارغاً سيتم توليده تلقائياً).</li>
                                <li><code>status</code> – يمكن أن تكون: <code>completed</code>, <code>pending</code>, <code>draft</code>.</li>
                                <li><code>notes</code> – ملاحظات السند.</li>
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
                            <a href="{{ route('purchases.receive-notes.import-template') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
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
