@extends('layouts.app')

@section('title', $bom->name.' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-indigo-600">التصنيع</a>
    <span>›</span>
    <a href="{{ route('manufacturing.bom-lists.index') }}" class="text-gray-500 hover:text-indigo-600">قوائم المواد</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $bom->name }}</span>
@endsection

@section('content')
@php $statusLabels = \App\Models\BomList::statusLabels(); @endphp
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $bom->name }}</h1>
                <p class="text-sm text-gray-500 mt-1 inline-flex items-center gap-2">
                    <span>{{ $bom->finishedItem?->code }} — {{ $bom->finishedItem?->name_ar }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                        @if($bom->status === \App\Models\BomList::STATUS_ACTIVE) bg-emerald-100 text-emerald-800
                        @elseif($bom->status === \App\Models\BomList::STATUS_OBSOLETE) bg-gray-200 text-gray-700
                        @else bg-amber-100 text-amber-900 @endif">{{ $statusLabels[$bom->status] ?? $bom->status }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('manufacturing.bom-lists.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">القائمة</a>
                <a href="{{ route('manufacturing.bom-lists.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700">قائمة جديدة</a>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div><span class="text-gray-500 inline-flex items-center gap-1">الإصدار <x-info field="manufacturing.bom_list_field_version" /></span> <span class="font-semibold text-gray-900">{{ $bom->version }}</span></div>
            <div><span class="text-gray-500 inline-flex items-center gap-1">تكلفة العمالة <x-info field="manufacturing.bom_list_field_labor" /></span> <span class="font-semibold text-gray-900">{{ number_format((float) $bom->labor_cost, 2) }}</span></div>
            <div><span class="text-gray-500 inline-flex items-center gap-1">التكاليف العامة <x-info field="manufacturing.bom_list_field_overhead" /></span> <span class="font-semibold text-gray-900">{{ number_format((float) $bom->overhead_cost, 2) }}</span></div>
            @if($bom->header_notes)
                <div class="md:col-span-2"><span class="text-gray-500 inline-flex items-center gap-1">ملاحظات <x-info field="manufacturing.bom_list_field_header_notes" /></span> <span class="text-gray-800">{{ $bom->header_notes }}</span></div>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/80 font-bold text-gray-900 inline-flex items-center gap-2">
                المكونات
                <x-info field="manufacturing.bom_list_components_section" />
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-sm text-right">
                    <thead class="text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">الخامة <x-info field="manufacturing.bom_line_col_component" /></span></th>
                            <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">الكمية <x-info field="manufacturing.bom_line_col_qty" /></span></th>
                            <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">الوحدة <x-info field="manufacturing.bom_line_col_unit" /></span></th>
                            <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">الهدر % <x-info field="manufacturing.bom_line_col_scrap" /></span></th>
                            <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">ملاحظات <x-info field="manufacturing.bom_line_col_notes" /></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bom->lines as $line)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-2">{{ $line->componentItem?->code }} — {{ $line->componentItem?->name_ar }}</td>
                                <td class="py-2 px-2 tabular-nums">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                                <td class="py-2 px-2">{{ $line->unit ?: '—' }}</td>
                                <td class="py-2 px-2 tabular-nums">{{ rtrim(rtrim(number_format((float) $line->scrap_percent, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                                <td class="py-2 px-2 text-gray-600">{{ $line->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
