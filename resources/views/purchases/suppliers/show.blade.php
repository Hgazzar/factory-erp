@extends('layouts.app')

@section('title', 'تفاصيل المورد - ' . ($supplier->localized_display_name ?? $supplier->code) . ' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('purchases.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المشتريات</a>
    <span>›</span>
    <a href="{{ route('purchases.suppliers.index') }}" class="text-gray-500 hover:text-indigo-600">الموردين</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تفاصيل المورد</span>
@endsection

@push('styles')
<style>
    .sup-card { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .sup-doc-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; min-width: 140px; }
    .sup-doc-card .sup-doc-icon { width: 40px; height: 40px; color: #6b7280; }
    .sup-doc-card .sup-doc-name { font-size: 0.75rem; color: #374151; text-align: center; word-break: break-all; }
</style>
@endpush

@section('content')
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="erp-alert-success-inline">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(124, 58, 237, 0.2); color: #7c3aed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $supplier->localized_display_name }}</h1>
                <p class="text-sm text-gray-500">{{ $supplier->code }} · {{ $supplier->supplier_type ?? '—' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.suppliers.edit', $supplier) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">تعديل</a>
            <a href="{{ route('purchases.suppliers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">قائمة الموردين</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="sup-card p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">معلومات الاتصال</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500 inline">الهاتف:</dt> <dd class="inline">{{ $supplier->phone ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">الجوال:</dt> <dd class="inline">{{ $supplier->mobile ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">البريد:</dt> <dd class="inline">{{ $supplier->email ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">الموقع:</dt> <dd class="inline">{{ $supplier->website ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">العنوان:</dt> <dd class="inline">{{ $supplier->address ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="sup-card p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">العمل والبنك</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500 inline">الرقم الضريبي:</dt> <dd class="inline">{{ $supplier->tax_number ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">السجل التجاري:</dt> <dd class="inline">{{ $supplier->commercial_register ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">شروط الدفع:</dt> <dd class="inline">{{ $supplier->payment_terms_days !== null ? $supplier->payment_terms_days . ' يوم' : '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">العملة:</dt> <dd class="inline">{{ $supplier->currency ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">البنك:</dt> <dd class="inline">{{ $supplier->bank_name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 inline">IBAN:</dt> <dd class="inline">{{ $supplier->iban ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- المرفقات (نظام موحد) --}}
    <div class="sup-card p-5 mb-6">
        <x-attachment-handler
            hint-field="procurement.supplier_attachments"
            title="المرفقات"
            :existing="$supplier->attachments"
            :uploadable="false"
            :allow-delete="true"
        />
    </div>

    @if($supplier->documents->isNotEmpty())
        <div class="sup-card p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-2">مستندات قديمة (قبل الترقية)</h2>
            <p class="text-xs text-gray-500 mb-4">تُعرض للمرجعية فقط؛ المرفقات الجديدة تُحفظ في القسم أعلاه.</p>
            <div class="flex flex-wrap gap-4">
                @foreach($supplier->documents as $doc)
                    <div class="sup-doc-card">
                        @if($doc->isPdf())
                            <svg class="sup-doc-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM4.5 1a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .354.146l3 3a.5.5 0 0 1-.708.708L9 2.207V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V1.5a.5.5 0 0 1 .5-.5z"/></svg>
                        @else
                            <svg class="sup-doc-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12a2 2 0 0 0-2 2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/></svg>
                        @endif
                        <span class="sup-doc-name" title="{{ $doc->file_name }}">{{ Str::limit($doc->file_name, 20) }}</span>
                        <div class="flex items-center gap-2 mt-1">
                            @php $url = asset('storage/' . $doc->file_path); @endphp
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="px-2 py-1 rounded-lg bg-white border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50">معاينة</a>
                            <a href="{{ route('purchases.suppliers.documents.download', [$supplier, $doc]) }}" class="px-2 py-1 rounded-lg bg-white border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50">تحميل</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
