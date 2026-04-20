@extends('layouts.app')

@section('title', 'تفاصيل الجرد ' . $audit->audit_number . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('inventory.audits.index') }}" class="text-gray-500 hover:text-indigo-600">جرد المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تفاصيل الجرد</span>
@endsection

@push('styles')
<style>
    @media print { .no-print { display: none !important; } .print-root { padding: 0; } }
</style>
@endpush

@section('content')
<div dir="rtl" class="print-root mx-auto w-full max-w-full space-y-6">
    <header class="no-print flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <h1 class="text-2xl font-bold text-gray-900">تفاصيل الجرد — {{ $audit->audit_number }}</h1>
        <div class="flex flex-wrap gap-2">
            @if($audit->isDraft())
            <form action="{{ route('inventory.audits.approve', $audit) }}" method="POST" class="inline" onsubmit="return confirm('اعتماد وتسوية الجرد سيحدّث أرصدة المستودع لتطابق الجرد ويسجّل الحركات. متابعة؟');">
                @csrf
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">اعتماد وتسوية</button>
            </form>
            @endif
            <button type="button" class="inline-flex h-10 items-center rounded-lg border border-blue-200 bg-white px-4 text-sm font-medium text-blue-700 hover:bg-blue-50" onclick="window.print();">طباعة</button>
            <a href="{{ route('inventory.audits.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">الرجوع للقائمة</a>
        </div>
    </header>

    @if(session('success'))
    <div class="no-print rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="no-print rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">بيانات الجرد</div>
        <div class="p-4 md:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_number" /> رقم الجرد</span>
                    <p class="font-semibold text-gray-900">{{ $audit->audit_number }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_date" /> التاريخ</span>
                    <p class="font-semibold text-gray-900">{{ $audit->audit_date?->format('Y-m-d') }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_warehouse" /> المستودع</span>
                    <p class="font-semibold text-gray-900">{{ $audit->warehouse?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_type" /> نوع الجرد</span>
                    <p class="font-semibold text-gray-900">{{ $audit->type === 'full' ? 'كلي' : 'جزئي' }}</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_progress" /> التقدم</span>
                    <p class="font-semibold text-gray-900">{{ $audit->progress ?? 0 }}%</p>
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_total_diff_value" /> قيمة الفروقات</span>
                    @php $tv = $audit->total_difference_value ?? 0; @endphp
                    @if($tv > 0)
                        <p class="font-semibold text-emerald-600">+{{ erp_money($tv) }} SAR</p>
                    @elseif($tv < 0)
                        <p class="font-semibold text-red-600">{{ erp_money($tv) }} SAR</p>
                    @else
                        <p class="font-semibold text-gray-900">0.00 SAR</p>
                    @endif
                </div>
                <div>
                    <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_status" /> الحالة</span>
                    @if($audit->status === 'approved')
                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800">معتمد</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">مسودة</span>
                    @endif
                </div>
            </div>
            @if($audit->notes)
            <div class="mt-4 border-t border-gray-100 pt-4">
                <span class="mb-1 block text-xs text-gray-500"><x-info field="inventory.audit_notes" /> ملاحظات</span>
                <p class="text-gray-800">{{ $audit->notes }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">بنود الجرد</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_line_index" /> #</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_line_code" /> الرمز</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_line_name" /> اسم الصنف</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_book" /> الرصيد الدفتري</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_actual" /> الرصيد الفعلي</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_diff" /> الفرق</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.audit_diff_value" /> قيمة الفرق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($audit->lines as $i => $line)
                    @php
                        $diff = (float) $line->difference;
                        $val = (float) $line->difference_value;
                    @endphp
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 text-gray-800">{{ $i + 1 }}</td>
                        <td class="px-3 py-3 font-medium text-gray-900">{{ $line->item?->code ?? '—' }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $line->item?->name_ar ?? $line->item?->name_en ?? '—' }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ erp_qty($line->book_quantity) }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ $line->actual_quantity !== null ? erp_qty($line->actual_quantity) : '—' }}</td>
                        <td class="px-3 py-3">
                            @if($diff > 0)
                                <span class="font-semibold text-emerald-600">+{{ erp_qty($diff) }}</span>
                            @elseif($diff < 0)
                                <span class="font-semibold text-red-600">{{ erp_qty($diff) }}</span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @if($val > 0)
                                <span class="font-semibold text-emerald-600">+{{ erp_money($val) }} SAR</span>
                            @elseif($val < 0)
                                <span class="font-semibold text-red-600">{{ erp_money($val) }} SAR</span>
                            @else
                                <span class="text-gray-400">0.00 SAR</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@if(request()->get('print'))
<script>window.onload = function() { window.print(); }</script>
@endif
@endsection
