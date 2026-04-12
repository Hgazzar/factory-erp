@extends('layouts.app')

@section('title', 'مرتجعات المبيعات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">مرتجعات المبيعات</span>
@endsection

@section('content')
<div class="max-w-full">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مرتجعات المبيعات</h1>
            <p class="text-sm text-gray-500 mt-0.5">إدارة مرتجعات العملاء والمبالغ المستردة</p>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('sales.returns.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">تصدير</a>
            <button type="button" data-import-modal="1" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition" data-bs-toggle="modal" data-bs-target="#salesReturnsImportNotImplementedModal">
                استيراد
            </button>
            <a href="{{ route('sales.returns.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                مرتجع جديد
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.returns.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white min-w-[140px]">
                <option value="">جميع الحالات</option>
                <option value="معلق" {{ request('status') === 'معلق' ? 'selected' : '' }}>معلق</option>
                <option value="معتمد" {{ request('status') === 'معتمد' ? 'selected' : '' }}>معتمد</option>
                <option value="مسترد" {{ request('status') === 'مسترد' ? 'selected' : '' }}>مسترد</option>
            </select>
            <input type="text" name="reference" value="{{ request('reference') }}" placeholder="رقم المرتجع" class="py-2 px-3 border border-gray-300 rounded-lg text-sm min-w-[160px]">
            <button type="submit" class="py-2 px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">بحث</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">رقم المرتجع</th>
                        <th class="py-3 px-4 font-medium">التاريخ</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">الفاتورة الأصلية</th>
                        <th class="py-3 px-4 font-medium">السبب</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium">المبلغ</th>
                        <th class="py-3 px-4 font-medium">مسترد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $r)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $r->reference ?? 'SR-' . $r->id }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $r->date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $r->customer?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">SINV-{{ $r->sales_invoice_id }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $r->reason_type ?? $r->reason ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @if($r->status === 'معتمد')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">معتمد</span>
                                @elseif($r->status === 'مسترد')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">مسترد</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">معلق</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($r->total, 2) }}</td>
                            <td class="py-3 px-4 text-gray-900">SAR {{ number_format($r->refunded_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $returns->links() }}
            </div>
        @endif
    </div>

    {{-- مودال استيراد مرتجعات المبيعات (لم يُفعّل بعد) --}}
    <div class="modal fade" id="salesReturnsImportNotImplementedModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد مرتجعات المبيعات</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body space-y-3 text-sm text-gray-700">
                    <p>استيراد مرتجعات المبيعات غير مفعّل حالياً، ويمكن تنفيذه في خطوة لاحقة إذا رغبت.</p>
                    <p class="text-xs text-gray-500">
                        حالياً الاستيراد متوفر للموردين، الأصناف، العملاء، فواتير الموردين، وسندات الاستلام فقط.
                    </p>
                </div>
                <div class="modal-footer border-t border-gray-200">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">حسناً</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
