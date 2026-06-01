@extends('layouts.app')

@section('title', 'تقرير تقييم المخزون - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">تقرير تقييم المخزون</span>
@endsection

@section('content')
<div class="max-w-full space-y-6" dir="rtl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
                تقرير تقييم المخزون
                <x-info field="inventory.valuation_report_intro" />
            </h1>
            <p class="mt-1 text-sm text-gray-500">الكمية الحالية × تكلفة الوحدة لكل صنف</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inventory.reports.valuation', array_merge(request()->query(), ['export' => 'excel'])) }}"
               class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">
                تصدير Excel
            </a>
            <a href="{{ route('inventory.reports.valuation', array_merge(request()->query(), ['export' => 'pdf'])) }}"
               class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm hover:bg-emerald-100">
                تصدير PDF
            </a>
            <a href="{{ route('inventory.dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                لوحة المخزون
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('inventory.reports.valuation') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="min-w-[14rem] flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700">بحث</label>
            <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="رمز أو اسم الصنف…"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">تطبيق</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600"><x-info field="inventory.valuation_col_code" /> الرمز</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600"><x-info field="inventory.valuation_col_name" /> اسم الصنف</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600"><x-info field="inventory.valuation_col_qty" /> الكمية</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600"><x-info field="inventory.valuation_col_unit_cost" /> تكلفة الوحدة</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600"><x-info field="inventory.valuation_col_total" /> إجمالي القيمة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-800">{{ $row->code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-left tabular-nums">{{ erp_qty($row->quantity) }}</td>
                            <td class="px-4 py-3 text-left tabular-nums">{{ number_format($row->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-left font-semibold tabular-nums text-gray-900">{{ number_format($row->total_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500">لا توجد أصناف بأرصدة مخزنية.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot class="bg-indigo-50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-bold text-indigo-900">
                            <x-info field="inventory.valuation_grand_total" /> الإجمالي
                        </td>
                        <td class="px-4 py-3 text-left text-lg font-bold tabular-nums text-indigo-900">{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
